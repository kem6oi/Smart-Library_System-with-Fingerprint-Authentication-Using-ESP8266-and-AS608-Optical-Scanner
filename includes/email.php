<?php
/**
 * Email Notification System
 * Handles email sending with queue support for async delivery
 */

/**
 * Send email (adds to queue for processing)
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML supported)
 * @param string $recipientName Recipient name (optional)
 * @param string $priority Email priority (high, normal, low)
 * @param string $template Template name (optional)
 * @return bool Success status
 */
function queueEmail($to, $subject, $body, $recipientName = null, $priority = 'normal', $template = null) {
    global $dbh;

    try {
        $sql = "INSERT INTO tblemail_queue
                (recipient_email, recipient_name, subject, body, template_name, priority, status)
                VALUES (:to, :name, :subject, :body, :template, :priority, 'pending')";

        $query = $dbh->prepare($sql);
        $query->bindParam(':to', $to, PDO::PARAM_STR);
        $query->bindParam(':name', $recipientName, PDO::PARAM_STR);
        $query->bindParam(':subject', $subject, PDO::PARAM_STR);
        $query->bindParam(':body', $body, PDO::PARAM_STR);
        $query->bindParam(':template', $template, PDO::PARAM_STR);
        $query->bindParam(':priority', $priority, PDO::PARAM_STR);

        return $query->execute();
    } catch (PDOException $e) {
        log_error("Error queueing email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email immediately (without queue)
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @return bool Success status
 */
function sendEmailNow($to, $subject, $body) {
    // Check if mail function is available
    if (!function_exists('mail')) {
        log_error("PHP mail function not available");
        return false;
    }

    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . env('MAIL_FROM_NAME', 'Smart Library System') . " <" . env('MAIL_FROM_ADDRESS', 'noreply@library.com') . ">" . "\r\n";

    // Send email
    $success = mail($to, $subject, $body, $headers);

    if (!$success) {
        log_error("Failed to send email to: $to");
    }

    return $success;
}

/**
 * Process email queue (call from cron job)
 *
 * @param int $limit Number of emails to process per run
 * @return array Processing results
 */
function processEmailQueue($limit = 10) {
    global $dbh;

    try {
        // Get pending emails
        $sql = "SELECT * FROM tblemail_queue
                WHERE status = 'pending'
                AND attempts < max_attempts
                AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                ORDER BY priority DESC, created_at ASC
                LIMIT :limit";

        $query = $dbh->prepare($sql);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        $emails = $query->fetchAll(PDO::FETCH_ASSOC);

        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0
        ];

        foreach ($emails as $email) {
            $results['processed']++;

            // Update status to sending
            $updateSql = "UPDATE tblemail_queue SET status = 'sending' WHERE id = :id";
            $updateQuery = $dbh->prepare($updateSql);
            $updateQuery->bindParam(':id', $email['id']);
            $updateQuery->execute();

            // Send email
            $success = sendEmailNow($email['recipient_email'], $email['subject'], $email['body']);

            if ($success) {
                // Mark as sent
                $updateSql = "UPDATE tblemail_queue
                              SET status = 'sent', sent_at = NOW()
                              WHERE id = :id";
                $updateQuery = $dbh->prepare($updateSql);
                $updateQuery->bindParam(':id', $email['id']);
                $updateQuery->execute();

                $results['sent']++;
            } else {
                // Increment attempts
                $updateSql = "UPDATE tblemail_queue
                              SET status = 'pending',
                                  attempts = attempts + 1,
                                  error_message = 'Failed to send'
                              WHERE id = :id";
                $updateQuery = $dbh->prepare($updateSql);
                $updateQuery->bindParam(':id', $email['id']);
                $updateQuery->execute();

                $results['failed']++;
            }
        }

        // Mark emails that exceeded max attempts as failed
        $failSql = "UPDATE tblemail_queue
                    SET status = 'failed'
                    WHERE status = 'pending'
                    AND attempts >= max_attempts";
        $dbh->exec($failSql);

        return $results;
    } catch (PDOException $e) {
        log_error("Error processing email queue: " . $e->getMessage());
        return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'error' => $e->getMessage()];
    }
}

/**
 * Get email template with variable substitution
 *
 * @param string $templateName Template name
 * @param array $variables Variables to substitute
 * @return string Email body
 */
function getEmailTemplate($templateName, $variables = []) {
    global $dbh;

    try {
        $sql = "SELECT email_template FROM tblreminder_settings WHERE reminder_type = :template";
        $query = $dbh->prepare($sql);
        $query->bindParam(':template', $templateName, PDO::PARAM_STR);
        $query->execute();

        $template = $query->fetchColumn();

        if (!$template) {
            return null;
        }

        // Replace variables
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    } catch (PDOException $e) {
        log_error("Error fetching email template: " . $e->getMessage());
        return null;
    }
}

/**
 * Send notification via email and/or Telegram
 *
 * @param PDO $dbh Database handle
 * @param string $studentId Student ID
 * @param string $notificationType Notification type
 * @param string $subject Email subject
 * @param string $message Message content
 * @param string $channel Channel (email, telegram, both)
 * @return bool Success status
 */
function sendNotification($dbh, $studentId, $notificationType, $subject, $message, $channel = 'both') {
    try {
        // Get student details
        $sql = "SELECT FullName, EmailId, telegram_chat_id,
                       email_notifications_enabled, telegram_notifications_enabled
                FROM tblstudents WHERE StudentId = :student_id";

        $query = $dbh->prepare($sql);
        $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        $query->execute();
        $student = $query->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            return false;
        }

        $emailSent = false;
        $telegramSent = false;

        // Send email
        if (($channel === 'email' || $channel === 'both') && $student['email_notifications_enabled']) {
            $htmlMessage = nl2br(htmlspecialchars($message));
            $emailSent = queueEmail($student['EmailId'], $subject, $htmlMessage, $student['FullName']);
        }

        // Send Telegram
        if (($channel === 'telegram' || $channel === 'both') && $student['telegram_notifications_enabled'] && $student['telegram_chat_id']) {
            $botToken = env('TELEGRAM_BOT_TOKEN');
            $telegramSent = sendTelegramMessage($student['telegram_chat_id'], $message, $botToken);
        }

        // Log notification
        $status = ($emailSent || $telegramSent) ? 'sent' : 'failed';
        $logSql = "INSERT INTO tblnotification_history
                   (student_id, notification_type, channel, subject, message, status)
                   VALUES (:student_id, :type, :channel, :subject, :message, :status)";

        $logQuery = $dbh->prepare($logSql);
        $logQuery->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        $logQuery->bindParam(':type', $notificationType, PDO::PARAM_STR);
        $logQuery->bindParam(':channel', $channel, PDO::PARAM_STR);
        $logQuery->bindParam(':subject', $subject, PDO::PARAM_STR);
        $logQuery->bindParam(':message', $message, PDO::PARAM_STR);
        $logQuery->bindParam(':status', $status, PDO::PARAM_STR);
        $logQuery->execute();

        return ($emailSent || $telegramSent);
    } catch (PDOException $e) {
        log_error("Error sending notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send bulk notifications to multiple users
 *
 * @param PDO $dbh Database handle
 * @param array $studentIds Array of student IDs
 * @param string $notificationType Notification type
 * @param string $subject Email subject
 * @param string $message Message content
 * @param string $channel Channel (email, telegram, both)
 * @return array Results
 */
function sendBulkNotifications($dbh, $studentIds, $notificationType, $subject, $message, $channel = 'both') {
    $results = [
        'total' => count($studentIds),
        'sent' => 0,
        'failed' => 0
    ];

    foreach ($studentIds as $studentId) {
        if (sendNotification($dbh, $studentId, $notificationType, $subject, $message, $channel)) {
            $results['sent']++;
        } else {
            $results['failed']++;
        }
    }

    return $results;
}

/**
 * Get notification history for a student
 *
 * @param PDO $dbh Database handle
 * @param string $studentId Student ID
 * @param int $limit Number of records
 * @return array Notification history
 */
function getNotificationHistory($dbh, $studentId, $limit = 50) {
    try {
        $sql = "SELECT * FROM tblnotification_history
                WHERE student_id = :student_id
                ORDER BY sent_at DESC
                LIMIT :limit";

        $query = $dbh->prepare($sql);
        $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching notification history: " . $e->getMessage());
        return [];
    }
}
?>
