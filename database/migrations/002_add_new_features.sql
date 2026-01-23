-- ============================================================================
-- New Features Database Migration Script
-- Purpose: Add RBAC, Audit Trail, Analytics, Reviews, and more
-- Run this after 001_add_indexes_and_optimize.sql
-- Author: Smart Library System
-- Date: 2025-11-11
-- ============================================================================

-- ============================================================================
-- SECTION 1: ROLE-BASED ACCESS CONTROL (RBAC)
-- ============================================================================

-- Create roles table
CREATE TABLE IF NOT EXISTS tblroles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name),
    INDEX idx_role_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles
INSERT INTO tblroles (role_name, role_description) VALUES
('super_admin', 'Super Administrator - Full system access'),
('admin', 'Administrator - Manage library operations'),
('librarian', 'Librarian - Day-to-day library operations'),
('assistant', 'Library Assistant - Limited administrative access'),
('student', 'Student - Basic user access')
ON DUPLICATE KEY UPDATE role_description = VALUES(role_description);

-- Create permissions table
CREATE TABLE IF NOT EXISTS tblpermissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    permission_category VARCHAR(50) NOT NULL,
    permission_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_permission_name (permission_name),
    INDEX idx_permission_category (permission_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default permissions
INSERT INTO tblpermissions (permission_name, permission_category, permission_description) VALUES
-- User Management
('view_users', 'users', 'View user list'),
('create_users', 'users', 'Create new users'),
('edit_users', 'users', 'Edit user information'),
('delete_users', 'users', 'Delete users'),
('block_users', 'users', 'Block/unblock users'),

-- Book Management
('view_books', 'books', 'View book catalog'),
('add_books', 'books', 'Add new books'),
('edit_books', 'books', 'Edit book information'),
('delete_books', 'books', 'Delete books'),

-- Issue/Return Books
('issue_books', 'circulation', 'Issue books to users'),
('return_books', 'circulation', 'Process book returns'),
('renew_books', 'circulation', 'Renew issued books'),
('view_issued_books', 'circulation', 'View issued books'),

-- Categories & Authors
('manage_categories', 'metadata', 'Manage book categories'),
('manage_authors', 'metadata', 'Manage authors'),

-- Reports & Analytics
('view_reports', 'reports', 'View reports'),
('export_reports', 'reports', 'Export reports to PDF/Excel'),
('view_analytics', 'analytics', 'View analytics dashboard'),

-- System Configuration
('manage_settings', 'system', 'Manage system settings'),
('manage_roles', 'system', 'Manage roles and permissions'),
('view_audit_logs', 'system', 'View audit trail'),
('manage_ip_whitelist', 'system', 'Manage IP whitelist'),

-- Reviews & Ratings
('view_reviews', 'reviews', 'View book reviews'),
('moderate_reviews', 'reviews', 'Moderate/delete reviews'),

-- Notifications
('send_notifications', 'notifications', 'Send notifications to users'),
('manage_reminders', 'notifications', 'Manage reminder settings')

ON DUPLICATE KEY UPDATE permission_description = VALUES(permission_description);

-- Create role-permission mapping table
CREATE TABLE IF NOT EXISTS tblrole_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES tblroles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES tblpermissions(id) ON DELETE CASCADE,
    INDEX idx_role_id (role_id),
    INDEX idx_permission_id (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Assign permissions to Super Admin (all permissions)
INSERT INTO tblrole_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM tblroles WHERE role_name = 'super_admin'),
    id
FROM tblpermissions
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Assign permissions to Admin (most permissions except system management)
INSERT INTO tblrole_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM tblroles WHERE role_name = 'admin'),
    id
FROM tblpermissions
WHERE permission_name NOT IN ('manage_roles', 'manage_ip_whitelist')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Assign permissions to Librarian (circulation and basic management)
INSERT INTO tblrole_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM tblroles WHERE role_name = 'librarian'),
    id
FROM tblpermissions
WHERE permission_category IN ('circulation', 'books', 'metadata')
   OR permission_name IN ('view_users', 'view_reports', 'view_analytics', 'view_reviews')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Assign permissions to Assistant (limited access)
INSERT INTO tblrole_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM tblroles WHERE role_name = 'assistant'),
    id
FROM tblpermissions
WHERE permission_name IN ('view_books', 'issue_books', 'return_books', 'view_issued_books', 'view_users')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Assign permissions to Student (basic user access)
INSERT INTO tblrole_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM tblroles WHERE role_name = 'student'),
    id
FROM tblpermissions
WHERE permission_name IN ('view_books')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Add role_id to admin table
ALTER TABLE admin
ADD COLUMN IF NOT EXISTS role_id INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) NULL,
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;

-- Set default role for existing admin (super_admin)
UPDATE admin
SET role_id = (SELECT id FROM tblroles WHERE role_name = 'super_admin')
WHERE role_id IS NULL;

-- Add foreign key for admin role
ALTER TABLE admin
ADD CONSTRAINT fk_admin_role
FOREIGN KEY (role_id) REFERENCES tblroles(id)
ON DELETE SET NULL ON UPDATE CASCADE;

-- Add role_id to tblstudents (always student role)
ALTER TABLE tblstudents
ADD COLUMN IF NOT EXISTS role_id INT DEFAULT NULL;

-- Set default role for existing students
UPDATE tblstudents
SET role_id = (SELECT id FROM tblroles WHERE role_name = 'student')
WHERE role_id IS NULL;

-- Add foreign key for student role
ALTER TABLE tblstudents
ADD CONSTRAINT fk_student_role
FOREIGN KEY (role_id) REFERENCES tblroles(id)
ON DELETE SET NULL ON UPDATE CASCADE;

-- IP Whitelist table
CREATE TABLE IF NOT EXISTS tblip_whitelist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    description VARCHAR(255),
    role_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ip (ip_address),
    INDEX idx_ip_active (is_active),
    INDEX idx_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SECTION 2: AUDIT TRAIL & ACTIVITY LOGGING
-- ============================================================================

CREATE TABLE IF NOT EXISTS tblaudit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_type ENUM('admin', 'student') NOT NULL,
    action VARCHAR(100) NOT NULL,
    action_category VARCHAR(50),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    geo_location VARCHAR(255),
    old_values JSON,
    new_values JSON,
    status ENUM('success', 'failed', 'warning') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_user_type (user_type),
    INDEX idx_action (action),
    INDEX idx_action_category (action_category),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SECTION 3: BOOK REVIEWS & RATINGS
-- ============================================================================

CREATE TABLE IF NOT EXISTS tblbook_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id VARCHAR(150) NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_title VARCHAR(255),
    review_text TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    helpful_count INT DEFAULT 0,
    not_helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_book_student (book_id, student_id),
    INDEX idx_book_id (book_id),
    INDEX idx_student_id (student_id),
    INDEX idx_rating (rating),
    INDEX idx_approved (is_approved),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Review helpful votes tracking
CREATE TABLE IF NOT EXISTS tblreview_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    student_id VARCHAR(150) NOT NULL,
    vote_type ENUM('helpful', 'not_helpful') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review_student (review_id, student_id),
    FOREIGN KEY (review_id) REFERENCES tblbook_reviews(id) ON DELETE CASCADE,
    INDEX idx_review_id (review_id),
    INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SECTION 4: EMAIL NOTIFICATIONS & REMINDERS
-- ============================================================================

-- Add email to students table if not exists
ALTER TABLE tblstudents
ADD COLUMN IF NOT EXISTS notification_email VARCHAR(150),
ADD COLUMN IF NOT EXISTS email_notifications_enabled TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS telegram_notifications_enabled TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS reminder_preferences JSON;

-- Update notification_email with EmailId if null
UPDATE tblstudents SET notification_email = EmailId WHERE notification_email IS NULL;

-- Email queue table for async sending
CREATE TABLE IF NOT EXISTS tblem ail_queue (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(150) NOT NULL,
    recipient_name VARCHAR(150),
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    template_name VARCHAR(100),
    priority ENUM('high', 'normal', 'low') DEFAULT 'normal',
    status ENUM('pending', 'sending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    error_message TEXT,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reminder settings table
CREATE TABLE IF NOT EXISTS tblreminder_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reminder_type VARCHAR(50) NOT NULL UNIQUE,
    days_before INT NOT NULL,
    is_enabled TINYINT(1) DEFAULT 1,
    email_template TEXT,
    telegram_template TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default reminder settings
INSERT INTO tblreminder_settings (reminder_type, days_before, email_template, telegram_template) VALUES
('due_soon', 3, 'Your book "{book_name}" is due in {days} days. Please return or renew it.', 'Hi {student_name},\n\nYour book "{book_name}" is due in {days} days on {due_date}.\n\nPlease return or renew it to avoid fines.'),
('due_today', 0, 'Your book "{book_name}" is due today! Please return it to avoid fines.', 'Hi {student_name},\n\nYour book "{book_name}" is due TODAY!\n\nPlease return it to avoid fines.'),
('overdue', -1, 'Your book "{book_name}" is overdue! Fine: ${fine}. Please return immediately.', 'Hi {student_name},\n\nYour book "{book_name}" is OVERDUE!\n\nFine: ${fine}\nPlease return it immediately.'),
('renewal_reminder', 1, 'Your book "{book_name}" can be renewed. Due date: {due_date}', 'Hi {student_name},\n\nYou can renew "{book_name}" before {due_date}.')
ON DUPLICATE KEY UPDATE email_template = VALUES(email_template);

-- Notification history table
CREATE TABLE IF NOT EXISTS tblnotification_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(150),
    notification_type VARCHAR(50),
    channel ENUM('email', 'telegram', 'both'),
    subject VARCHAR(255),
    message TEXT,
    status ENUM('sent', 'failed'),
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SECTION 5: ANALYTICS & STATISTICS
-- ============================================================================

-- Daily statistics snapshot
CREATE TABLE IF NOT EXISTS tblanalytics_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL UNIQUE,
    total_books_issued INT DEFAULT 0,
    total_books_returned INT DEFAULT 0,
    total_new_users INT DEFAULT 0,
    total_active_users INT DEFAULT 0,
    total_overdue_books INT DEFAULT 0,
    total_fines_collected DECIMAL(10,2) DEFAULT 0,
    total_reservations INT DEFAULT 0,
    total_reviews_submitted INT DEFAULT 0,
    popular_category_id INT,
    popular_book_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stat_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Popular books tracking
CREATE TABLE IF NOT EXISTS tblbook_popularity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    view_count INT DEFAULT 0,
    borrow_count INT DEFAULT 0,
    reserve_count INT DEFAULT 0,
    review_count INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_book (book_id),
    INDEX idx_borrow_count (borrow_count),
    INDEX idx_average_rating (average_rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SECTION 6: USER PREFERENCES
-- ============================================================================

-- Theme and display preferences
CREATE TABLE IF NOT EXISTS tbluser_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('admin', 'student') NOT NULL,
    theme VARCHAR(20) DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'en',
    items_per_page INT DEFAULT 25,
    dashboard_widgets JSON,
    notification_settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id, user_type),
    INDEX idx_user_id (user_id),
    INDEX idx_theme (theme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SECTION 7: EXPORT & REPORT TEMPLATES
-- ============================================================================

CREATE TABLE IF NOT EXISTS tblreport_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    template_type ENUM('pdf', 'excel', 'csv') NOT NULL,
    description TEXT,
    sql_query TEXT,
    columns JSON,
    filters JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_name (template_name),
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default report templates
INSERT INTO tblreport_templates (template_name, template_type, description, columns) VALUES
('issued_books_report', 'pdf', 'List of currently issued books', '["book_name", "student_name", "issue_date", "return_date", "status"]'),
('overdue_books_report', 'pdf', 'List of overdue books with fines', '["book_name", "student_name", "days_overdue", "fine_amount"]'),
('popular_books_report', 'excel', 'Most borrowed books', '["book_name", "author", "category", "borrow_count", "average_rating"]'),
('user_activity_report', 'excel', 'User borrowing activity', '["student_id", "student_name", "books_borrowed", "books_returned", "pending_books"]'),
('fine_collection_report', 'excel', 'Fine collection summary', '["student_id", "student_name", "total_fines", "paid_fines", "pending_fines"]')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ============================================================================
-- SECTION 8: SYSTEM CONFIGURATION
-- ============================================================================

CREATE TABLE IF NOT EXISTS tblsystem_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    config_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_editable TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default system configurations
INSERT INTO tblsystem_config (config_key, config_value, config_type, description) VALUES
('site_name', 'Smart Library System', 'string', 'Library system name'),
('max_books_per_user', '5', 'number', 'Maximum books a user can borrow'),
('loan_duration_days', '14', 'number', 'Default loan duration in days'),
('fine_per_day', '0.50', 'number', 'Fine amount per day for overdue books'),
('max_fine_amount', '10.00', 'number', 'Maximum fine cap per book'),
('allow_renewals', 'true', 'boolean', 'Allow book renewals'),
('max_renewal_count', '2', 'number', 'Maximum number of renewals per book'),
('require_email_verification', 'true', 'boolean', 'Require email verification on signup'),
('enable_book_reviews', 'true', 'boolean', 'Enable book reviews and ratings'),
('require_review_approval', 'false', 'boolean', 'Require admin approval for reviews'),
('enable_ip_whitelist', 'false', 'boolean', 'Enable IP whitelist for admin access'),
('session_timeout_minutes', '30', 'number', 'Session timeout in minutes'),
('enable_analytics', 'true', 'boolean', 'Enable analytics tracking'),
('enable_audit_logs', 'true', 'boolean', 'Enable audit trail logging'),
('reminder_email_enabled', 'true', 'boolean', 'Enable email reminders'),
('reminder_telegram_enabled', 'true', 'boolean', 'Enable Telegram reminders')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- ============================================================================
-- COMPLETION MESSAGE
-- ============================================================================
SELECT 'New features database migration completed successfully!' AS Status;
SELECT 'Tables created: RBAC, Audit Trail, Reviews, Notifications, Analytics, Reports' AS Summary;
