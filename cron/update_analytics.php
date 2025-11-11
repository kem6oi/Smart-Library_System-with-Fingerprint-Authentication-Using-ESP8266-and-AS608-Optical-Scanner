<?php
/**
 * Cron Job: Update Analytics Data
 *
 * This script should be run daily to:
 * - Record daily analytics statistics
 * - Update book popularity scores
 * - Send scheduled reminders
 *
 * Add to crontab:
 * 0 1 * * * php /path/to/library/cron/update_analytics.php >> /path/to/library/logs/cron.log 2>&1
 */

// Set execution time limit
set_time_limit(300); // 5 minutes

// Include required files
require_once(__DIR__ . '/../includes/config.php');
require_once(__DIR__ . '/../includes/analytics.php');
require_once(__DIR__ . '/../includes/reminders.php');
require_once(__DIR__ . '/../includes/email.php');

echo "[" . date('Y-m-d H:i:s') . "] Starting analytics update cron job\n";

try {
    // 1. Record yesterday's analytics
    echo "Recording daily analytics...\n";
    if (recordDailyAnalytics($dbh)) {
        echo "✓ Daily analytics recorded successfully\n";
    } else {
        echo "✗ Failed to record daily analytics\n";
    }

    // 2. Update book popularity scores
    echo "Updating book popularity scores...\n";
    if (updateBookPopularity($dbh)) {
        echo "✓ Book popularity scores updated successfully\n";
    } else {
        echo "✗ Failed to update book popularity scores\n";
    }

    // 3. Send automated reminders
    echo "Sending automated reminders...\n";
    $reminderResults = sendAllReminders($dbh);

    echo "  - Due Soon Reminders: ";
    if (isset($reminderResults['due_soon']['sent'])) {
        echo $reminderResults['due_soon']['sent'] . " sent, " .
             $reminderResults['due_soon']['failed'] . " failed\n";
    } else {
        echo "Skipped\n";
    }

    echo "  - Due Today Reminders: ";
    if (isset($reminderResults['due_today']['sent'])) {
        echo $reminderResults['due_today']['sent'] . " sent, " .
             $reminderResults['due_today']['failed'] . " failed\n";
    } else {
        echo "Skipped\n";
    }

    echo "  - Overdue Reminders: ";
    if (isset($reminderResults['overdue']['sent'])) {
        echo $reminderResults['overdue']['sent'] . " sent, " .
             $reminderResults['overdue']['failed'] . " failed\n";
    } else {
        echo "Skipped\n";
    }

    echo "  - Renewal Reminders: ";
    if (isset($reminderResults['renewal']['sent'])) {
        echo $reminderResults['renewal']['sent'] . " sent, " .
             $reminderResults['renewal']['failed'] . " failed\n";
    } else {
        echo "Skipped\n";
    }

    // 4. Process email queue
    echo "Processing email queue...\n";
    $emailsProcessed = processEmailQueue(50); // Process up to 50 emails
    echo "✓ Processed $emailsProcessed emails from queue\n";

    // 5. Cleanup old logs (optional - keep last 90 days)
    echo "Cleaning up old audit logs...\n";
    $sql = "DELETE FROM tblaudit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
    $deleted = $dbh->exec($sql);
    echo "✓ Deleted $deleted old audit log entries\n";

    echo "[" . date('Y-m-d H:i:s') . "] Analytics update cron job completed successfully\n";
    echo "----------------------------------------\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    log_error("Cron job error: " . $e->getMessage());
    exit(1);
}
?>
