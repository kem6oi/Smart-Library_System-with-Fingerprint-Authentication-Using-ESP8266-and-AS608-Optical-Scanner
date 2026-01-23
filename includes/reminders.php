<?php
/**
 * Smart Reminders System
 * Automated reminders for due books, overdue books, and renewal notifications
 */

require_once(__DIR__ . '/email.php');
require_once(__DIR__ . '/rbac.php');

/**
 * Send reminders for books due soon (3 days before)
 *
 * @param PDO $dbh Database handle
 * @return array Results
 */
function sendDueSoonReminders($dbh) {
    try {
        // Check if reminder is enabled
        $sql = "SELECT is_enabled, days_before FROM tblreminder_settings
                WHERE reminder_type = 'due_soon'";
        $query = $dbh->prepare($sql);
        $query->execute();
        $setting = $query->fetch(PDO::FETCH_ASSOC);

        if (!$setting || !$setting['is_enabled']) {
            return ['skipped' => true, 'reason' => 'Reminder disabled'];
        }

        $daysBefore = $setting['days_before'];

        // Get books due in X days
        $sql = "SELECT i.*, s.FullName, s.EmailId, s.telegram_chat_id,
                       s.email_notifications_enabled, s.telegram_notifications_enabled,
                       b.BookName, b.ISBNNumber,
                       DATE_FORMAT(i.ReturnDate, '%Y-%m-%d') as due_date
                FROM tblissuedbookdetails i
                INNER JOIN tblstudents s ON s.StudentId = i.StudentId
                INNER JOIN tblbooks b ON b.id = i.BookId
                WHERE i.RetrunStatus IS NULL
                AND DATE(i.ReturnDate) = DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND s.Status = 1";

        $query = $dbh->prepare($sql);
        $query->bindParam(':days', $daysBefore, PDO::PARAM_INT);
        $query->execute();
        $dueBooks = $query->fetchAll(PDO::FETCH_ASSOC);

        $results = [
            'total' => count($dueBooks),
            'sent' => 0,
            'failed' => 0
        ];

        foreach ($dueBooks as $book) {
            $variables = [
                'student_name' => $book['FullName'],
                'book_name' => $book['BookName'],
                'due_date' => date('F j, Y', strtotime($book['due_date'])),
                'days' => $daysBefore
            ];

            $subject = "Reminder: Book Due in {$daysBefore} Days";
            $message = "Hi {$book['FullName']},\n\n";
            $message .= "Your book \"{$book['BookName']}\" (ISBN: {$book['ISBNNumber']}) is due in {$daysBefore} days on {$variables['due_date']}.\n\n";
            $message .= "Please return or renew it before the due date to avoid fines.\n\n";
            $message .= "Thank you,\nSmart Library System";

            if (sendNotification($dbh, $book['StudentId'], 'due_soon', $subject, $message, 'both')) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    } catch (PDOException $e) {
        log_error("Error sending due soon reminders: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Send reminders for books due today
 *
 * @param PDO $dbh Database handle
 * @return array Results
 */
function sendDueTodayReminders($dbh) {
    try {
        // Check if reminder is enabled
        $sql = "SELECT is_enabled FROM tblreminder_settings WHERE reminder_type = 'due_today'";
        $query = $dbh->prepare($sql);
        $query->execute();
        $setting = $query->fetch(PDO::FETCH_ASSOC);

        if (!$setting || !$setting['is_enabled']) {
            return ['skipped' => true, 'reason' => 'Reminder disabled'];
        }

        // Get books due today
        $sql = "SELECT i.*, s.FullName, s.EmailId, s.telegram_chat_id,
                       b.BookName, b.ISBNNumber
                FROM tblissuedbookdetails i
                INNER JOIN tblstudents s ON s.StudentId = i.StudentId
                INNER JOIN tblbooks b ON b.id = i.BookId
                WHERE i.RetrunStatus IS NULL
                AND DATE(i.ReturnDate) = CURDATE()
                AND s.Status = 1";

        $query = $dbh->prepare($sql);
        $query->execute();
        $dueBooks = $query->fetchAll(PDO::FETCH_ASSOC);

        $results = [
            'total' => count($dueBooks),
            'sent' => 0,
            'failed' => 0
        ];

        foreach ($dueBooks as $book) {
            $subject = "URGENT: Book Due TODAY";
            $message = "Hi {$book['FullName']},\n\n";
            $message .= "Your book \"{$book['BookName']}\" (ISBN: {$book['ISBNNumber']}) is due TODAY!\n\n";
            $message .= "Please return it before end of day to avoid late fines.\n\n";
            $message .= "Thank you,\nSmart Library System";

            if (sendNotification($dbh, $book['StudentId'], 'due_today', $subject, $message, 'both')) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    } catch (PDOException $e) {
        log_error("Error sending due today reminders: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Send reminders for overdue books
 *
 * @param PDO $dbh Database handle
 * @return array Results
 */
function sendOverdueReminders($dbh) {
    try {
        // Check if reminder is enabled
        $sql = "SELECT is_enabled FROM tblreminder_settings WHERE reminder_type = 'overdue'";
        $query = $dbh->prepare($sql);
        $query->execute();
        $setting = $query->fetch(PDO::FETCH_ASSOC);

        if (!$setting || !$setting['is_enabled']) {
            return ['skipped' => true, 'reason' => 'Reminder disabled'];
        }

        // Get overdue books
        $finePerDay = getSystemConfig($dbh, 'fine_per_day', 0.50);
        $maxFine = getSystemConfig($dbh, 'max_fine_amount', 10.00);

        $sql = "SELECT i.*, s.FullName, s.EmailId, s.telegram_chat_id,
                       b.BookName, b.ISBNNumber,
                       DATEDIFF(CURDATE(), i.ReturnDate) as days_overdue,
                       LEAST(DATEDIFF(CURDATE(), i.ReturnDate) * :fine_per_day, :max_fine) as fine_amount
                FROM tblissuedbookdetails i
                INNER JOIN tblstudents s ON s.StudentId = i.StudentId
                INNER JOIN tblbooks b ON b.id = i.BookId
                WHERE i.RetrunStatus IS NULL
                AND DATE(i.ReturnDate) < CURDATE()
                AND s.Status = 1";

        $query = $dbh->prepare($sql);
        $query->bindParam(':fine_per_day', $finePerDay);
        $query->bindParam(':max_fine', $maxFine);
        $query->execute();
        $overdueBooks = $query->fetchAll(PDO::FETCH_ASSOC);

        $results = [
            'total' => count($overdueBooks),
            'sent' => 0,
            'failed' => 0
        ];

        foreach ($overdueBooks as $book) {
            $subject = "OVERDUE: Book Return Required - Fine Applied";
            $message = "Hi {$book['FullName']},\n\n";
            $message .= "Your book \"{$book['BookName']}\" (ISBN: {$book['ISBNNumber']}) is OVERDUE by {$book['days_overdue']} day(s)!\n\n";
            $message .= "Fine Amount: $" . number_format($book['fine_amount'], 2) . "\n\n";
            $message .= "Please return the book immediately to prevent additional fines.\n\n";
            $message .= "Thank you,\nSmart Library System";

            if (sendNotification($dbh, $book['StudentId'], 'overdue', $subject, $message, 'both')) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    } catch (PDOException $e) {
        log_error("Error sending overdue reminders: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Send renewal reminders
 *
 * @param PDO $dbh Database handle
 * @return array Results
 */
function sendRenewalReminders($dbh) {
    try {
        // Check if renewals are allowed
        $allowRenewals = getSystemConfig($dbh, 'allow_renewals', 'true');
        if ($allowRenewals !== 'true') {
            return ['skipped' => true, 'reason' => 'Renewals not allowed'];
        }

        // Check if reminder is enabled
        $sql = "SELECT is_enabled, days_before FROM tblreminder_settings
                WHERE reminder_type = 'renewal_reminder'";
        $query = $dbh->prepare($sql);
        $query->execute();
        $setting = $query->fetch(PDO::FETCH_ASSOC);

        if (!$setting || !$setting['is_enabled']) {
            return ['skipped' => true, 'reason' => 'Reminder disabled'];
        }

        $daysBefore = $setting['days_before'];
        $maxRenewals = getSystemConfig($dbh, 'max_renewal_count', 2);

        // Get books eligible for renewal
        $sql = "SELECT i.*, s.FullName, s.EmailId, s.telegram_chat_id,
                       b.BookName, b.ISBNNumber,
                       DATE_FORMAT(i.ReturnDate, '%Y-%m-%d') as due_date,
                       COALESCE(i.renewal_count, 0) as renewal_count
                FROM tblissuedbookdetails i
                INNER JOIN tblstudents s ON s.StudentId = i.StudentId
                INNER JOIN tblbooks b ON b.id = i.BookId
                WHERE i.RetrunStatus IS NULL
                AND DATE(i.ReturnDate) = DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND COALESCE(i.renewal_count, 0) < :max_renewals
                AND s.Status = 1";

        $query = $dbh->prepare($sql);
        $query->bindParam(':days', $daysBefore, PDO::PARAM_INT);
        $query->bindParam(':max_renewals', $maxRenewals, PDO::PARAM_INT);
        $query->execute();
        $renewableBooks = $query->fetchAll(PDO::FETCH_ASSOC);

        $results = [
            'total' => count($renewableBooks),
            'sent' => 0,
            'failed' => 0
        ];

        foreach ($renewableBooks as $book) {
            $renewalsLeft = $maxRenewals - $book['renewal_count'];

            $subject = "Reminder: You Can Renew Your Book";
            $message = "Hi {$book['FullName']},\n\n";
            $message .= "Your book \"{$book['BookName']}\" (ISBN: {$book['ISBNNumber']}) is due on " . date('F j, Y', strtotime($book['due_date'])) . ".\n\n";
            $message .= "Good news! You can renew this book up to {$renewalsLeft} more time(s).\n\n";
            $message .= "Contact the library or log in to your account to renew.\n\n";
            $message .= "Thank you,\nSmart Library System";

            if (sendNotification($dbh, $book['StudentId'], 'renewal_reminder', $subject, $message, 'both')) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    } catch (PDOException $e) {
        log_error("Error sending renewal reminders: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Send all active reminders
 *
 * @param PDO $dbh Database handle
 * @return array Combined results
 */
function sendAllReminders($dbh) {
    $results = [
        'due_soon' => sendDueSoonReminders($dbh),
        'due_today' => sendDueTodayReminders($dbh),
        'overdue' => sendOverdueReminders($dbh),
        'renewal' => sendRenewalReminders($dbh)
    ];

    return $results;
}

/**
 * Get reminder settings
 *
 * @param PDO $dbh Database handle
 * @return array Reminder settings
 */
function getReminderSettings($dbh) {
    try {
        $sql = "SELECT * FROM tblreminder_settings ORDER BY reminder_type";
        $query = $dbh->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching reminder settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Update reminder setting
 *
 * @param PDO $dbh Database handle
 * @param string $reminderType Reminder type
 * @param array $settings Settings to update
 * @return bool Success status
 */
function updateReminderSetting($dbh, $reminderType, $settings) {
    try {
        $sql = "UPDATE tblreminder_settings SET ";
        $updates = [];
        $params = [':reminder_type' => $reminderType];

        if (isset($settings['is_enabled'])) {
            $updates[] = "is_enabled = :is_enabled";
            $params[':is_enabled'] = $settings['is_enabled'] ? 1 : 0;
        }

        if (isset($settings['days_before'])) {
            $updates[] = "days_before = :days_before";
            $params[':days_before'] = $settings['days_before'];
        }

        if (isset($settings['email_template'])) {
            $updates[] = "email_template = :email_template";
            $params[':email_template'] = $settings['email_template'];
        }

        if (isset($settings['telegram_template'])) {
            $updates[] = "telegram_template = :telegram_template";
            $params[':telegram_template'] = $settings['telegram_template'];
        }

        if (empty($updates)) {
            return false;
        }

        $sql .= implode(', ', $updates) . " WHERE reminder_type = :reminder_type";

        $query = $dbh->prepare($sql);
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }

        return $query->execute();
    } catch (PDOException $e) {
        log_error("Error updating reminder setting: " . $e->getMessage());
        return false;
    }
}
?>
