# New Features Implementation Guide

## 📊 **Implementation Status**

### ✅ **COMPLETED Features**

| Feature | Status | Files | Description |
|---------|--------|-------|-------------|
| **RBAC (Role-Based Access Control)** | ✅ Complete | `includes/rbac.php`<br>`002_add_new_features.sql` | 5 roles, 25+ permissions, granular access control |
| **Audit Trail** | ✅ Complete | `includes/audit.php`<br>`tblaudit_logs` table | Complete activity logging with JSON values |
| **IP Geolocation** | ✅ Complete | `includes/geolocation.php` | Track user locations, detect suspicious logins |
| **Database Schema** | ✅ Complete | `002_add_new_features.sql` | All tables for new features created |

### 🔄 **READY TO IMPLEMENT** (Schema Complete, Needs UI)

| Feature | Database | Backend | Frontend | Priority |
|---------|----------|---------|----------|----------|
| **Email Notifications** | ✅ | ⚠️ Partial | ❌ | High |
| **Smart Reminders** | ✅ | ❌ | ❌ | High |
| **Analytics Dashboard** | ✅ | ❌ | ❌ | High |
| **Book Reviews** | ✅ | ❌ | ❌ | Medium |
| **Export Reports** | ✅ | ❌ | ❌ | Medium |
| **Dark Mode** | N/A | N/A | ❌ | Low |

---

## 🎯 **Quick Start: Using Implemented Features**

### 1. **Role-Based Access Control (RBAC)**

#### Check User Permission:
```php
<?php
include('includes/config.php');
include('includes/rbac.php');

// Check if user has permission
$userId = $_SESSION['alogin']; // or $_SESSION['stdid']
$userType = 'admin'; // or 'student'

if (hasPermission($dbh, $userId, 'delete_books', $userType)) {
    echo "User can delete books";
} else {
    echo "Access denied";
}
?>
```

#### Require Permission (Auto-redirect):
```php
<?php
include('includes/config.php');
include('includes/rbac.php');

// At top of protected page
requirePermission($dbh, 'manage_settings', 'dashboard.php');

// If user doesn't have permission, automatically redirected
?>
```

#### Get User's Role:
```php
<?php
$role = getUserRole($dbh, $userId, 'admin');
echo "Role: " . $role['role_name']; // super_admin, admin, librarian, etc.
?>
```

#### Check Multiple Permissions:
```php
<?php
// Check if user has ANY of these permissions
if (hasAnyPermission($dbh, $userId, ['view_books', 'add_books'], 'admin')) {
    // Show books section
}

// Check if user has ALL of these permissions
if (hasAllPermissions($dbh, $userId, ['edit_books', 'delete_books'], 'admin')) {
    // Show advanced book management
}
?>
```

---

### 2. **Audit Trail Logging**

#### Log User Actions:
```php
<?php
include('includes/audit.php');

// Log successful login
logLogin($dbh, $userId, 'admin', true);

// Log failed login
logLogin($dbh, $userId, 'admin', false, 'Invalid password');

// Log book issue
logBookIssue($dbh, $adminId, $studentId, $bookId, $bookName);

// Log book return with fine
logBookReturn($dbh, $adminId, $studentId, $bookId, $bookName, $fineAmount);

// Log user creation
logUserCreation($dbh, $adminId, $newUserId, 'student', [
    'name' => $fullName,
    'email' => $email
]);

// Log password change
logPasswordChange($dbh, $userId, 'student', false);

// General audit log
logAudit($dbh, $userId, 'admin', 'custom_action', 'category',
    'Description of action', 'success', $oldValues, $newValues);
?>
```

#### View Audit Logs:
```php
<?php
// Get recent activities
$recentActivities = getRecentActivities($dbh, 20);

// Get audit logs with filters
$filters = [
    'user_id' => 123,
    'action' => 'login_success',
    'date_from' => '2025-01-01',
    'date_to' => '2025-12-31'
];
$logs = getAuditLogs($dbh, $filters, 100, 0);

// Get statistics
$stats = getAuditStats($dbh, 'today'); // or 'week', 'month', 'year'
echo "Total events: " . $stats['total_events'];
echo "Failed events: " . $stats['failed_events'];
?>
```

---

### 3. **IP Geolocation**

#### Get User Location:
```php
<?php
include('includes/geolocation.php');

// Get client IP (handles proxies)
$ip = getClientIP();

// Get location string
$location = getGeoLocation($ip);
echo "Location: $location"; // e.g., "New York, United States"

// Get detailed location
$details = getGeoLocation($ip, true);
echo "Country: " . $details['country'];
echo "City: " . $details['city'];
echo "ISP: " . $details['isp'];
?>
```

#### Detect Location Changes (Security):
```php
<?php
// Check if user is logging in from new location
$change = detectLocationChange($dbh, $userId, 'admin');

if ($change['is_new_location']) {
    // Send security alert email
    $message = "New login from {$change['current_location']}";
    $message .= "\nPrevious location: {$change['previous_location']}";

    // Log security warning
    logAudit($dbh, $userId, 'admin', 'new_location_login', 'security',
        $message, 'warning');

    // Send notification (implement email notification)
}
?>
```

#### Get Location Statistics:
```php
<?php
$locationStats = getLocationStats($dbh, 'week');

foreach ($locationStats as $stat) {
    echo "{$stat['geo_location']}: {$stat['access_count']} accesses<br>";
}
?>
```

---

## 📝 **Implementation Guides for Remaining Features**

### 4. **Email Notifications** (Backend 50% Complete)

**Database:** ✅ Complete (`tblem ail_queue`, `tblnotification_history`)
**Backend:** ⚠️ Partial (needs PHPMailer integration)
**Frontend:** ❌ Not started

#### Steps to Complete:

1. **Install PHPMailer:**
```bash
cd /path/to/library-system
composer require phpmailer/phpmailer
```

2. **Create `includes/email.php`:**
```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendEmail($to, $subject, $body, $priority = 'normal') {
    global $dbh;

    // Queue email for async sending
    $sql = "INSERT INTO tblem ail_queue
            (recipient_email, subject, body, priority, status)
            VALUES (:to, :subject, :body, :priority, 'pending')";

    $query = $dbh->prepare($sql);
    $query->bindParam(':to', $to);
    $query->bindParam(':subject', $subject);
    $query->bindParam(':body', $body);
    $query->bindParam(':priority', $priority);

    return $query->execute();
}

function processEmailQueue() {
    global $dbh;

    // Get pending emails
    $sql = "SELECT * FROM tblem ail_queue
            WHERE status = 'pending'
            AND attempts < max_attempts
            ORDER BY priority DESC, created_at ASC
            LIMIT 10";

    $query = $dbh->prepare($sql);
    $query->execute();
    $emails = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($emails as $email) {
        $mail = new PHPMailer(true);

        try {
            // SMTP configuration (from .env)
            $mail->isSMTP();
            $mail->Host = env('SMTP_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = env('SMTP_USERNAME');
            $mail->Password = env('SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = env('SMTP_PORT', 587);

            // Email content
            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress($email['recipient_email']);
            $mail->Subject = $email['subject'];
            $mail->Body = $email['body'];
            $mail->isHTML(true);

            $mail->send();

            // Mark as sent
            $updateSql = "UPDATE tblem ail_queue
                          SET status = 'sent', sent_at = NOW()
                          WHERE id = :id";
            $updateQuery = $dbh->prepare($updateSql);
            $updateQuery->bindParam(':id', $email['id']);
            $updateQuery->execute();

        } catch (Exception $e) {
            // Mark as failed, increment attempts
            $updateSql = "UPDATE tblem ail_queue
                          SET attempts = attempts + 1,
                              error_message = :error
                          WHERE id = :id";
            $updateQuery = $dbh->prepare($updateSql);
            $updateQuery->bindParam(':id', $email['id']);
            $updateQuery->bindParam(':error', $mail->ErrorInfo);
            $updateQuery->execute();
        }
    }
}
?>
```

3. **Add to `.env`:**
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=library@yourdomain.com
MAIL_FROM_NAME=Smart Library System
```

4. **Create cron job** to process queue:
```bash
*/5 * * * * php /path/to/library/cron/process_emails.php
```

---

### 5. **Smart Reminders** (Database Complete)

**Database:** ✅ Complete (`tblreminder_settings`)
**Backend:** ❌ Not started
**Frontend:** ❌ Not started

#### Implementation Steps:

1. **Create `includes/reminders.php`:**
```php
<?php
function sendDueReminders() {
    global $dbh;

    // Get books due in 3 days
    $sql = "SELECT i.*, s.FullName, s.EmailId, s.telegram_chat_id,
                   b.BookName, i.ReturnDate
            FROM tblissuedbookdetails i
            INNER JOIN tblstudents s ON s.StudentId = i.StudentId
            INNER JOIN tblbooks b ON b.id = i.BookId
            WHERE i.RetrunStatus IS NULL
            AND DATE(i.ReturnDate) = DATE_ADD(CURDATE(), INTERVAL 3 DAY)";

    $query = $dbh->prepare($sql);
    $query->execute();
    $dueBooks = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dueBooks as $book) {
        $message = "Your book '{$book['BookName']}' is due in 3 days on {$book['ReturnDate']}. Please return or renew it.";

        // Send email
        sendEmail($book['EmailId'], 'Book Due Soon', $message);

        // Send Telegram
        sendTelegramMessage($book['telegram_chat_id'], $message, env('TELEGRAM_BOT_TOKEN'));

        // Log notification
        logNotification($dbh, $book['StudentId'], 'due_soon', 'both', $message);
    }
}

function sendOverdueReminders() {
    global $dbh;

    // Get overdue books
    $sql = "SELECT i.*, s.FullName, s.EmailId, s.telegram_chat_id,
                   b.BookName, i.ReturnDate,
                   DATEDIFF(CURDATE(), i.ReturnDate) as days_overdue
            FROM tblissuedbookdetails i
            INNER JOIN tblstudents s ON s.StudentId = i.StudentId
            INNER JOIN tblbooks b ON b.id = i.BookId
            WHERE i.RetrunStatus IS NULL
            AND DATE(i.ReturnDate) < CURDATE()";

    $query = $dbh->prepare($sql);
    $query->execute();
    $overdueBooks = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($overdueBooks as $book) {
        $fine = $book['days_overdue'] * getSystemConfig($dbh, 'fine_per_day', 0.50);

        $message = "Your book '{$book['BookName']}' is {$book['days_overdue']} days overdue! Fine: $$fine. Please return it immediately.";

        send Email($book['EmailId'], 'Book Overdue - Fine Applied', $message);
        sendTelegramMessage($book['telegram_chat_id'], $message, env('TELEGRAM_BOT_TOKEN'));

        logNotification($dbh, $book['StudentId'], 'overdue', 'both', $message);
    }
}
?>
```

2. **Create cron job** `/cron/send_reminders.php`:
```php
<?php
include('../includes/config.php');
include('../includes/reminders.php');

sendDueReminders();
sendOverdueReminders();

echo "Reminders sent successfully\n";
?>
```

3. **Add to crontab:**
```bash
# Run daily at 9 AM
0 9 * * * php /path/to/library/cron/send_reminders.php
```

---

### 6. **Analytics Dashboard** (Database Complete)

**Database:** ✅ Complete (`tblanalytics_daily`, `tblbook_popularity`)
**Backend:** ❌ Not started
**Frontend:** ❌ Not started

#### Implementation Steps:

1. **Create `admin/analytics.php`:**
```php
<?php
session_start();
include('../includes/config.php');
include('../includes/rbac.php');
include('../includes/security.php');

requirePermission($dbh, 'view_analytics');

// Get statistics
$today = date('Y-m-d');

// Books issued today
$sql = "SELECT COUNT(*) FROM tblissuedbookdetails WHERE DATE(IssuesDate) = :today";
$query = $dbh->prepare($sql);
$query->bindParam(':today', $today);
$query->execute();
$issuedToday = $query->fetchColumn();

// Books returned today
$sql = "SELECT COUNT(*) FROM tblissuedbookdetails WHERE DATE(ReturnDate) = :today AND RetrunStatus = 1";
$query = $dbh->prepare($sql);
$query->bindParam(':today', $today);
$query->execute();
$returnedToday = $query->fetchColumn();

// Overdue books
$sql = "SELECT COUNT(*) FROM tblissuedbookdetails
        WHERE RetrunStatus IS NULL AND DATE(ReturnDate) < CURDATE()";
$query = $dbh->prepare($sql);
$query->execute();
$overdueBooks = $query->fetchColumn();

// Popular books (top 10)
$sql = "SELECT b.BookName, bp.borrow_count, bp.average_rating
        FROM tblbook_popularity bp
        INNER JOIN tblbooks b ON b.id = bp.book_id
        ORDER BY bp.borrow_count DESC
        LIMIT 10";
$query = $dbh->prepare($sql);
$query->execute();
$popularBooks = $query->fetchAll(PDO::FETCH_ASSOC);

// Weekly trend
$sql = "SELECT DATE(IssuesDate) as date, COUNT(*) as count
        FROM tblissuedbookdetails
        WHERE IssuesDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(IssuesDate)
        ORDER BY date ASC";
$query = $dbh->prepare($sql);
$query->execute();
$weeklyTrend = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="../assets/css/style.css" rel="stylesheet" />
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="container mx-auto p-6">
        <h2 class="text-3xl font-bold mb-6">Analytics Dashboard</h2>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-500 text-sm">Books Issued Today</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo $issuedToday; ?></p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-500 text-sm">Books Returned Today</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo $returnedToday; ?></p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-500 text-sm">Overdue Books</h3>
                <p class="text-3xl font-bold text-red-600"><?php echo $overdueBooks; ?></p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-500 text-sm">Active Users</h3>
                <p class="text-3xl font-bold text-purple-600">
                    <?php
                    $sql = "SELECT COUNT(*) FROM tblstudents WHERE Status = 1";
                    $query = $dbh->prepare($sql);
                    $query->execute();
                    echo $query->fetchColumn();
                    ?>
                </p>
            </div>
        </div>

        <!-- Weekly Trend Chart -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h3 class="text-xl font-bold mb-4">Weekly Borrowing Trend</h3>
            <canvas id="weeklyTrendChart"></canvas>
        </div>

        <!-- Popular Books -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-xl font-bold mb-4">Most Popular Books</h3>
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left p-2">Book Name</th>
                        <th class="text-left p-2">Times Borrowed</th>
                        <th class="text-left p-2">Average Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popularBooks as $book): ?>
                    <tr class="border-b">
                        <td class="p-2"><?php echo sanitize_output($book['BookName']); ?></td>
                        <td class="p-2"><?php echo $book['borrow_count']; ?></td>
                        <td class="p-2">⭐ <?php echo number_format($book['average_rating'], 1); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Weekly Trend Chart
    const ctx = document.getElementById('weeklyTrendChart').getContext('2d');
    const weeklyData = <?php echo json_encode($weeklyTrend); ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: weeklyData.map(d => d.date),
            datasets: [{
                label: 'Books Issued',
                data: weeklyData.map(d => d.count),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: false
                }
            }
        }
    });
    </script>
</body>
</html>
```

---

### 7. **Book Reviews & Ratings** (Database Complete)

**Database:** ✅ Complete (`tblbook_reviews`, `tblreview_votes`)
**Backend:** ❌ Not started
**Frontend:** ❌ Not started

See `FEATURES_GUIDE.md` section 7 for implementation...

---

### 8. **Export Reports** (Database Complete)

See `FEATURES_GUIDE.md` section 8 for PDF/Excel export...

---

### 9. **Dark Mode** (Frontend Only)

See `FEATURES_GUIDE.md` section 9 for CSS implementation...

---

## 🚀 **Deployment Instructions**

1. **Run database migration:**
```bash
mysql -u root -p library < database/migrations/002_add_new_features.sql
```

2. **Verify tables created:**
```sql
SHOW TABLES LIKE 'tbl%';
```

3. **Test RBAC:**
```php
// In any admin page
include('includes/rbac.php');
$role = getUserRole($dbh, $_SESSION['alogin'], 'admin');
echo "Your role: " . $role['role_name'];
```

4. **Test Audit Trail:**
```php
// Log test event
logAudit($dbh, 1, 'admin', 'test_action', 'test', 'Testing audit trail', 'success');

// View in database
SELECT * FROM tblaudit_logs ORDER BY created_at DESC LIMIT 10;
```

---

## 📖 **Next Steps**

Priority order for completing remaining features:

1. ✅ **Email Notifications** - High impact, moderate effort
2. ✅ **Smart Reminders** - High impact, low effort (uses email)
3. ✅ **Analytics Dashboard** - High value, moderate effort
4. **Book Reviews** - User engagement, moderate effort
5. **Export Reports** - Admin convenience, low effort
6. **Dark Mode** - UI polish, very low effort

**Estimated Total Time:** 3-5 days for all remaining features

---

This guide provides everything needed to complete the implementation. All database schemas are ready, and code examples show exactly how to integrate each feature!
