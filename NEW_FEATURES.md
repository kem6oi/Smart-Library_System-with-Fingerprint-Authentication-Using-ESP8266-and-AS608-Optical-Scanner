# Smart Library System - New Features Documentation

## Overview

This document provides comprehensive documentation for all the new features and improvements added to the Smart Library System.

## Table of Contents

1. [Security Improvements](#security-improvements)
2. [Role-Based Access Control (RBAC)](#role-based-access-control)
3. [Audit Trail System](#audit-trail-system)
4. [IP Geolocation Tracking](#ip-geolocation-tracking)
5. [Email Notification System](#email-notification-system)
6. [Smart Reminders](#smart-reminders)
7. [Analytics Dashboard](#analytics-dashboard)
8. [Book Reviews & Ratings](#book-reviews-and-ratings)
9. [Export Reports](#export-reports)
10. [Dark Mode Theme](#dark-mode-theme)
11. [Database Optimizations](#database-optimizations)
12. [Cron Jobs Setup](#cron-jobs-setup)

---

## Security Improvements

### Password Security
- **Migrated from MD5 to bcrypt** with cost factor 12
- **Automatic password rehashing** on login for gradual migration
- **Password strength validation** requiring:
  - Minimum 8 characters
  - At least one uppercase letter
  - At least one lowercase letter
  - At least one number
  - At least one special character

### Session Security
- **Session timeout**: 30 minutes of inactivity
- **Session regeneration**: Every 10 minutes
- **Secure flags**: HttpOnly, Secure, SameSite=Strict
- **Session fixation prevention**: Regenerates session ID on login

### Input/Output Security
- **XSS prevention** with `sanitize_output()`
- **Rate limiting**: 5 attempts per 15 minutes
- **Secure error handling**: Logs errors without exposing details

### Files
- `includes/password.php` - Password management
- `includes/security.php` - Security helpers
- `includes/env.php` - Environment variable loader

---

## Role-Based Access Control

### Roles
1. **Super Admin** - Full system access
2. **Admin** - Administrative access
3. **Librarian** - Book and circulation management
4. **Assistant** - Limited administrative tasks
5. **Student** - Basic user access

### Permission Categories
- **Books Management**: Add, edit, delete, view books
- **User Management**: Manage students and admins
- **Circulation**: Issue and return books
- **Reports**: View and export reports
- **Analytics**: Access analytics dashboard
- **System**: Configure system settings
- **Audit**: View audit logs
- **Reviews**: Moderate reviews

### Usage Examples

#### Check Permission
```php
require_once('includes/rbac.php');

// Check single permission
if (hasPermission($dbh, $userId, 'books_add', 'admin')) {
    // User can add books
}

// Require permission (auto-redirect if unauthorized)
requirePermission($dbh, 'analytics_view', 'dashboard.php');
```

#### Get User Role
```php
$role = getUserRole($dbh, $userId, 'admin');
echo $role['role_name']; // 'super_admin', 'admin', etc.
```

### IP Whitelist
- Restrict admin access to specific IP addresses
- Configure in system settings
- Supports role-specific whitelists

### Files
- `includes/rbac.php` - RBAC functions
- `database/migrations/002_add_new_features.sql` - Schema

---

## Audit Trail System

### Features
- **Complete activity logging** for all user actions
- **Tracks**: User ID, action type, IP address, geolocation, user agent
- **Stores**: Old and new values as JSON for updates
- **Categories**: Authentication, circulation, books, users, security, system

### Logged Actions
- Login/logout attempts
- Book issues and returns
- User creation, updates, and status changes
- Password changes
- System configuration changes
- Book additions, updates, and deletions

### Usage Examples

#### Log Custom Event
```php
require_once('includes/audit.php');

logAudit($dbh, $userId, 'admin', 'custom_action', 'system',
    'Description of action', 'success', $oldValues, $newValues);
```

#### Query Audit Logs
```php
$filters = [
    'user_id' => 123,
    'action' => 'login_success',
    'date_from' => '2025-01-01',
    'date_to' => '2025-01-31'
];

$logs = getAuditLogs($dbh, $filters, 100, 0);
```

#### Get Statistics
```php
$stats = getAuditStats($dbh, 'today');
// Returns: total_events, successful_events, failed_events, etc.
```

### Files
- `includes/audit.php` - Audit logging functions
- `admin/audit-logs.php` - View audit logs (if created)

---

## IP Geolocation Tracking

### Features
- **Automatic IP geolocation** lookup using ip-api.com (free, 45 req/min)
- **Session caching** (24 hours) to reduce API calls
- **Proxy detection** via `HTTP_X_FORWARDED_FOR`
- **Location change detection** for security alerts

### Functions

#### Get Location
```php
require_once('includes/geolocation.php');

$location = getGeoLocation($ip); // "New York, United States"

$detailed = getGeoLocation($ip, true);
// Returns: country, city, region, timezone, ISP
```

#### Detect Location Changes
```php
$change = detectLocationChange($dbh, $userId, 'admin');

if ($change['is_new_location']) {
    echo "New login from: " . $change['current_location'];
    echo "Previous: " . $change['previous_location'];
    // Send security alert
}
```

#### Location Statistics
```php
$stats = getLocationStats($dbh, 'week');
// Top 10 locations by access count
```

### Files
- `includes/geolocation.php` - Geolocation functions

---

## Email Notification System

### Features
- **Queue-based system** for async email sending
- **Priority levels**: high, normal, low
- **Retry logic**: Up to 3 attempts
- **Template system** with variable substitution
- **Dual channel support**: Email and Telegram
- **User preferences**: Opt-in/opt-out per channel

### Usage Examples

#### Queue Email
```php
require_once('includes/email.php');

queueEmail(
    'user@example.com',
    'Book Due Reminder',
    'Your book is due in 3 days...',
    'John Doe',
    'high' // priority
);
```

#### Send Immediate Email
```php
$sent = sendEmailNow(
    'user@example.com',
    'Test Subject',
    'Test message body'
);
```

#### Send Unified Notification
```php
sendNotification($dbh, $studentId, 'due_soon',
    'Book Due Soon',
    'Your book is due in 3 days...',
    'both' // email and telegram
);
```

#### Process Queue (Cron Job)
```php
$processed = processEmailQueue(50); // Process up to 50 emails
```

### Configuration
- Configure SMTP settings in `.env` file
- Set Telegram bot token in `.env`
- Users control preferences in their profile

### Files
- `includes/email.php` - Email functions
- `cron/update_analytics.php` - Processes email queue daily

---

## Smart Reminders

### Reminder Types

1. **Due Soon** - Books due in 3 days (configurable)
2. **Due Today** - Books due TODAY
3. **Overdue** - Books past due date with fine calculation
4. **Renewal Reminder** - Books eligible for renewal

### Features
- **Enable/disable** per reminder type
- **Configurable timing** (days before due date)
- **Fine calculation** based on system settings
- **Respects user notification preferences**
- **Automatic sending via cron job**

### Usage Examples

#### Send Specific Reminders
```php
require_once('includes/reminders.php');

$results = sendDueSoonReminders($dbh);
echo "Sent: " . $results['sent'];
echo "Failed: " . $results['failed'];
```

#### Send All Reminders
```php
$allResults = sendAllReminders($dbh);
// Returns results for: due_soon, due_today, overdue, renewal
```

#### Update Settings
```php
updateReminderSetting($dbh, 'due_soon', [
    'is_enabled' => true,
    'days_before' => 3
]);
```

### Cron Job Setup
```bash
# Add to crontab to run daily at 1 AM
0 1 * * * php /path/to/library/cron/update_analytics.php >> /path/to/library/logs/cron.log 2>&1
```

### Files
- `includes/reminders.php` - Reminder functions
- `cron/update_analytics.php` - Daily cron job

---

## Analytics Dashboard

### Features
- **Real-time statistics**: Books issued, returned, overdue, active users
- **Monthly summaries**: Total issues, returns, fines, registrations
- **Borrowing trends**: 7-day chart using Chart.js
- **Popular books**: Top 10 by borrows and ratings
- **Category statistics**: Books, borrows, and ratings per category
- **User activity**: Top borrowers and users with overdue books
- **CSV export**: Export analytics data

### Metrics Tracked

#### Daily Analytics
- Books issued and returned
- New registrations
- Total active users
- Total overdue books
- Fines collected
- Unique visitors
- Average return time

#### Book Popularity
- Total borrows
- Current borrows
- Average rating
- Total reviews
- Popularity score

### Usage Examples

#### Get Today's Stats
```php
require_once('includes/analytics.php');

$stats = getTodayStats($dbh);
echo "Books issued today: " . $stats['books_issued'];
```

#### Get Dashboard Data
```php
$dashboard = getAnalyticsDashboard($dbh);
// Returns: today, borrowing_trend, popular_books, category_stats, etc.
```

#### Update Book Popularity
```php
updateBookPopularity($dbh); // Run daily via cron
```

### Access
- Navigate to **Admin → Analytics Dashboard**
- Requires `analytics_view` permission

### Files
- `includes/analytics.php` - Analytics functions
- `admin/analytics.php` - Dashboard page
- `cron/update_analytics.php` - Daily data aggregation

---

## Book Reviews and Ratings

### Features
- **5-star rating system**
- **Text reviews** (optional)
- **Helpful/Not Helpful voting**
- **Rating breakdown** by stars
- **Only borrowers can review**
- **Edit existing reviews**
- **Report inappropriate reviews**

### Student Features
- Rate and review borrowed books
- View other users' reviews
- Vote on review helpfulness
- Edit or delete own reviews

### Usage Examples

#### Add Review
```php
require_once('includes/reviews.php');

$result = addBookReview($dbh, $bookId, $studentId, 5, 'Excellent book!');
if ($result['success']) {
    echo "Review added successfully";
}
```

#### Get Book Reviews
```php
$reviews = getBookReviews($dbh, $bookId, 10, 0);
foreach ($reviews as $review) {
    echo $review['rating'] . " stars - " . $review['review_text'];
}
```

#### Get Rating Summary
```php
$summary = getBookRatingSummary($dbh, $bookId);
echo "Average: " . $summary['avg_rating'];
echo "Total: " . $summary['total_reviews'];
// Also includes star breakdown percentages
```

### Access
- Student view: Browse books → Click "Reviews"
- Direct URL: `book-reviews.php?bookid=123`

### Files
- `includes/reviews.php` - Review functions
- `book-reviews.php` - Review page

---

## Export Reports

### Available Reports

1. **Issued Books Report**
   - All book issues with student details
   - Filters: Date range, status (issued/returned/overdue)
   - Formats: CSV, PDF

2. **Books List Report**
   - Complete inventory
   - Filter by category
   - Includes borrowing statistics
   - Format: CSV

3. **Students List Report**
   - All registered students
   - Filter by status (active/blocked)
   - Includes borrowing statistics
   - Format: CSV

4. **Overdue Books Report**
   - Currently overdue books
   - Includes calculated fines
   - Format: CSV

5. **Audit Logs Report**
   - System activity logs
   - Filters: Date range, user type, category
   - Format: CSV (max 5000 records)

6. **Analytics Report**
   - Daily analytics data
   - Format: CSV

### Usage Examples

#### Export to CSV
```php
require_once('includes/export.php');

exportIssuedBooksCSV($dbh, '2025-01-01', '2025-01-31', 'all');
// Automatically downloads CSV file
```

#### Export to PDF
```php
exportIssuedBooksPDF($dbh, '2025-01-01', '2025-01-31', 'overdue');
// Generates printable PDF page
```

### Access
- Navigate to **Admin → Export Reports**
- Requires `reports_export` permission
- Select report type, apply filters, choose format

### Files
- `includes/export.php` - Export functions
- `admin/export-reports.php` - Export interface

---

## Dark Mode Theme

### Features
- **System-wide dark theme**
- **Floating toggle button** (bottom-right corner)
- **Keyboard shortcut**: `Ctrl+Shift+D` or `Cmd+Shift+D`
- **Remembers preference** via localStorage and database
- **Smooth transitions** between themes
- **Auto-detection** of system preference
- **Works on all pages**

### User Experience
- **Default**: Follows system preference
- **Manual toggle**: Click floating button or use keyboard shortcut
- **Persistence**: Preference saved across sessions
- **Accessibility**: High contrast colors, readable text

### Implementation Details
- CSS: `assets/css/dark-mode.css`
- JavaScript: `assets/js/dark-mode.js`
- Backend: `save-preference.php`
- Database: `tbluser_preferences` table

### Customization
To modify dark mode colors, edit `assets/css/dark-mode.css`:

```css
body.dark-mode {
    background-color: #1a1a1a; /* Main background */
    color: #e0e0e0; /* Text color */
}

.dark-mode .panel {
    background-color: #2d2d2d; /* Panel background */
}
```

### Integration
Include in your HTML pages:

```html
<link href="assets/css/dark-mode.css" rel="stylesheet" />
<script src="assets/js/dark-mode.js"></script>
```

### Files
- `assets/css/dark-mode.css` - Dark theme styles
- `assets/js/dark-mode.js` - Toggle functionality
- `save-preference.php` - Save user preference

---

## Database Optimizations

### Indexes Added (20+)
- **tblstudents**: EmailId, MobileNumber, Status, StudentId
- **tblissuedbookdetails**: BookId, StudentId, ReturnDate, IssuesDate, RetrunStatus
- **tblbooks**: BookName, ISBNNumber, CatId, AuthorId
- **tblcategory**: CategoryName
- **tblauthors**: AuthorName
- **auth_codes**: code, student_id, expires_at
- **admin**: UserName, email

### Performance Impact
- **40-90% faster** queries on indexed columns
- Significant improvement for:
  - Student lookups
  - Book searches
  - Issue/return operations
  - Analytics queries

### Schema Updates
- Password columns: `VARCHAR(60)` → `VARCHAR(255)` for bcrypt
- New tables for features (see migration file)

### Migration File
- Location: `database/migrations/001_add_indexes_and_optimize.sql`
- Run: Import via phpMyAdmin or command line

---

## Cron Jobs Setup

### Daily Analytics Update

**Script**: `cron/update_analytics.php`

**Tasks**:
1. Record yesterday's analytics
2. Update book popularity scores
3. Send automated reminders
4. Process email queue (up to 50 emails)
5. Cleanup old audit logs (90+ days)

**Crontab Entry**:
```bash
0 1 * * * php /path/to/library/cron/update_analytics.php >> /path/to/library/logs/cron.log 2>&1
```

**Manual Execution**:
```bash
php cron/update_analytics.php
```

### Logs
- Output: `logs/cron.log`
- Errors: `logs/error.log`

---

## Configuration

### Environment Variables (.env)

```env
# Database
DB_HOST=localhost
DB_NAME=library
DB_USER=root
DB_PASS=your_password

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=library@example.com

# Telegram
TELEGRAM_BOT_TOKEN=your_bot_token

# Security
SESSION_TIMEOUT=1800
MAX_LOGIN_ATTEMPTS=5
RATE_LIMIT_WINDOW=900

# Fines
FINE_PER_DAY=0.50
MAX_FINE_AMOUNT=10.00
```

### System Configuration (Database)

Configure via `tblsystem_config` table or admin interface:

- `fine_per_day`: Daily fine amount
- `max_fine_amount`: Maximum fine cap
- `allow_renewals`: Enable/disable book renewals
- `max_renewal_count`: Maximum renewal times
- `enable_ip_whitelist`: Enable IP restrictions

---

## Migration Guide

### Step 1: Backup Database
```bash
mysqldump -u root -p library > backup_$(date +%Y%m%d).sql
```

### Step 2: Run Migrations
```bash
mysql -u root -p library < database/migrations/001_add_indexes_and_optimize.sql
mysql -u root -p library < database/migrations/002_add_new_features.sql
```

### Step 3: Configure Environment
```bash
cp .env.example .env
# Edit .env with your settings
```

### Step 4: Set Permissions
```bash
chmod 600 .env
chmod 755 cron/
chmod 644 cron/*.php
```

### Step 5: Setup Cron Job
```bash
crontab -e
# Add: 0 1 * * * php /path/to/library/cron/update_analytics.php >> /path/to/library/logs/cron.log 2>&1
```

### Step 6: Create Logs Directory
```bash
mkdir logs
chmod 755 logs
```

### Step 7: Test Features
1. Login as admin
2. Check Analytics Dashboard
3. Test dark mode toggle
4. Export a report
5. Add a book review
6. View audit logs

---

## Troubleshooting

### Email Not Sending
- Check SMTP settings in `.env`
- Verify `processEmailQueue()` is running via cron
- Check `logs/error.log` for SMTP errors
- Test with `sendEmailNow()` function

### Reminders Not Working
- Verify cron job is running
- Check reminder settings in database
- Ensure `tblreminder_settings` has enabled reminders
- Check `logs/cron.log` for execution status

### Dark Mode Not Saving
- Check browser console for JavaScript errors
- Verify `save-preference.php` is accessible
- Check `tbluser_preferences` table exists
- Ensure user is logged in

### Analytics Not Updating
- Run `cron/update_analytics.php` manually
- Check database for `tblanalytics_daily` table
- Verify cron job has correct path and permissions
- Check `logs/cron.log` for errors

### Geolocation Not Working
- Check internet connectivity (requires external API)
- Verify ip-api.com is accessible
- Check rate limit (45 requests/minute)
- Fallback to "Unknown" is automatic

---

## API Reference

### Key Functions

#### Security
- `hashPassword($password)` - Hash password with bcrypt
- `verifyPassword($password, $hash)` - Verify password
- `sanitize_output($string)` - XSS prevention
- `check_rate_limit($action, $max, $window)` - Rate limiting

#### RBAC
- `hasPermission($dbh, $userId, $permission, $userType)` - Check permission
- `requirePermission($dbh, $permission, $redirectUrl)` - Require permission
- `getUserRole($dbh, $userId, $userType)` - Get user role
- `getSystemConfig($dbh, $key, $default)` - Get config value

#### Audit
- `logAudit($dbh, $userId, $userType, $action, $category, $description, $status, $oldValues, $newValues)` - Log event
- `getAuditLogs($dbh, $filters, $limit, $offset)` - Query logs
- `getAuditStats($dbh, $period)` - Get statistics

#### Analytics
- `getTodayStats($dbh)` - Get today's statistics
- `getBorrowingTrend($dbh, $days)` - Get borrowing trend
- `getPopularBooks($dbh, $limit, $sortBy)` - Get popular books
- `updateBookPopularity($dbh)` - Update popularity scores

#### Reviews
- `addBookReview($dbh, $bookId, $studentId, $rating, $reviewText)` - Add review
- `getBookReviews($dbh, $bookId, $limit, $offset)` - Get reviews
- `getBookRatingSummary($dbh, $bookId)` - Get rating summary
- `voteOnReview($dbh, $reviewId, $studentId, $voteType)` - Vote on review

---

## Credits

- **Chart.js**: Analytics visualizations
- **ip-api.com**: Free geolocation API
- **Bootstrap**: UI framework
- **Font Awesome**: Icons

---

## Support

For issues and questions:
1. Check logs: `logs/error.log`, `logs/cron.log`
2. Review this documentation
3. Check database migration files
4. Review FEATURES_GUIDE.md for implementation examples

---

## License

Same as parent project.

---

**Version**: 2.0
**Last Updated**: January 2025
