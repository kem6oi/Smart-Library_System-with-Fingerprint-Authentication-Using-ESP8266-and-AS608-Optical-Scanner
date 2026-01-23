-- Database Optimization and Index Migration Script
-- Purpose: Add indexes for better query performance and optimize database structure
-- Run this script ONCE after deploying the security updates
-- Author: Smart Library System
-- Date: 2025-11-11

-- ============================================================================
-- SECTION 1: ADD INDEXES FOR PERFORMANCE
-- ============================================================================

-- Index on tblstudents table
-- Improves login query performance (EmailId lookup)
CREATE INDEX IF NOT EXISTS idx_students_email ON tblstudents(EmailId);

-- Index for student ID lookups (foreign key references)
CREATE INDEX IF NOT EXISTS idx_students_studentid ON tblstudents(StudentId);

-- Index for student status (active/blocked user queries)
CREATE INDEX IF NOT EXISTS idx_students_status ON tblstudents(Status);

-- Index for Telegram chat ID lookups (password recovery, notifications)
CREATE INDEX IF NOT EXISTS idx_students_telegram ON tblstudents(telegram_chat_id);

-- Index on tblissuedbookdetails table
-- Improves queries for issued books by student
CREATE INDEX IF NOT EXISTS idx_issued_studentid ON tblissuedbookdetails(StudentId);

-- Improves queries for issued books by book ID
CREATE INDEX IF NOT EXISTS idx_issued_bookid ON tblissuedbookdetails(BookId);

-- Improves queries for return status
CREATE INDEX IF NOT EXISTS idx_issued_return_status ON tblissuedbookdetails(RetrunStatus);

-- Improves queries for issue date range searches
CREATE INDEX IF NOT EXISTS idx_issued_dates ON tblissuedbookdetails(IssuesDate, ReturnDate);

-- Compound index for common query patterns (student + return status)
CREATE INDEX IF NOT EXISTS idx_issued_student_status ON tblissuedbookdetails(StudentId, RetrunStatus);

-- Index on tblbooks table
-- Improves book search by ISBN
CREATE INDEX IF NOT EXISTS idx_books_isbn ON tblbooks(ISBNNumber);

-- Improves book search by name
CREATE INDEX IF NOT EXISTS idx_books_name ON tblbooks(BookName);

-- Index for category-based searches
CREATE INDEX IF NOT EXISTS idx_books_category ON tblbooks(CatId);

-- Index for author-based searches
CREATE INDEX IF NOT EXISTS idx_books_author ON tblbooks(AuthorId);

-- Index on tblcategory table
-- Improves category lookups
CREATE INDEX IF NOT EXISTS idx_category_name ON tblcategory(CategoryName);

-- Status index for active/inactive categories
CREATE INDEX IF NOT EXISTS idx_category_status ON tblcategory(Status);

-- Index on tblauthors table
-- Improves author name lookups
CREATE INDEX IF NOT EXISTS idx_authors_name ON tblauthors(AuthorName);

-- Index on tblreservations table (if exists)
-- Note: Add these only if tblreservations table exists in your database
-- CREATE INDEX IF NOT EXISTS idx_reservations_studentid ON tblreservations(student_id);
-- CREATE INDEX IF NOT EXISTS idx_reservations_bookid ON tblreservations(book_id);
-- CREATE INDEX IF NOT EXISTS idx_reservations_status ON tblreservations(status);
-- CREATE INDEX IF NOT EXISTS idx_reservations_pickup ON tblreservations(pickup_date);

-- Index on auth_codes table
-- Improves verification code lookups
CREATE INDEX IF NOT EXISTS idx_auth_codes_student ON auth_codes(student_id);

-- Compound index for code verification (code + student_id)
CREATE INDEX IF NOT EXISTS idx_auth_codes_lookup ON auth_codes(code, student_id);

-- Index on admin table
-- Improves admin login performance
CREATE INDEX IF NOT EXISTS idx_admin_username ON admin(UserName);

-- ============================================================================
-- SECTION 2: ADD FOREIGN KEY CONSTRAINTS (Optional - Enforces Data Integrity)
-- ============================================================================
-- Uncomment these if you want to enforce referential integrity
-- WARNING: These will fail if you have orphaned records in your database
-- Check for orphaned records before enabling these constraints

-- Foreign key for tblissuedbookdetails -> tblstudents
-- ALTER TABLE tblissuedbookdetails
-- ADD CONSTRAINT fk_issued_student
-- FOREIGN KEY (StudentId) REFERENCES tblstudents(StudentId)
-- ON DELETE RESTRICT ON UPDATE CASCADE;

-- Foreign key for tblissuedbookdetails -> tblbooks
-- ALTER TABLE tblissuedbookdetails
-- ADD CONSTRAINT fk_issued_book
-- FOREIGN KEY (BookId) REFERENCES tblbooks(id)
-- ON DELETE RESTRICT ON UPDATE CASCADE;

-- Foreign key for tblbooks -> tblcategory
-- ALTER TABLE tblbooks
-- ADD CONSTRAINT fk_books_category
-- FOREIGN KEY (CatId) REFERENCES tblcategory(id)
-- ON DELETE RESTRICT ON UPDATE CASCADE;

-- Foreign key for tblbooks -> tblauthors
-- ALTER TABLE tblbooks
-- ADD CONSTRAINT fk_books_author
-- FOREIGN KEY (AuthorId) REFERENCES tblauthors(id)
-- ON DELETE RESTRICT ON UPDATE CASCADE;

-- ============================================================================
-- SECTION 3: UPDATE PASSWORD COLUMN TO SUPPORT BCRYPT
-- ============================================================================
-- Bcrypt hashes are 60 characters, MD5 hashes are 32 characters
-- This ensures the Password column can store both during migration

-- Update tblstudents password column
ALTER TABLE tblstudents
MODIFY COLUMN Password VARCHAR(255) NOT NULL;

-- Update admin password column
ALTER TABLE admin
MODIFY COLUMN Password VARCHAR(255) NOT NULL;

-- ============================================================================
-- SECTION 4: ADD AUDIT TIMESTAMPS (Optional - Best Practice)
-- ============================================================================
-- Uncomment these if you want to track record creation and updates

-- Add timestamps to tblstudents
-- ALTER TABLE tblstudents
-- ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
-- ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add timestamps to tblbooks
-- ALTER TABLE tblbooks
-- ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
-- ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add timestamps to tblissuedbookdetails
-- ALTER TABLE tblissuedbookdetails
-- ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
-- ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ============================================================================
-- SECTION 5: FIX DATABASE SCHEMA TYPO (Optional but Recommended)
-- ============================================================================
-- Fix the typo "RetrunStatus" -> "ReturnStatus"
-- WARNING: This will require updating all PHP code that references this column
-- Uncomment only if you plan to update all references in the codebase

-- ALTER TABLE tblissuedbookdetails
-- CHANGE COLUMN RetrunStatus ReturnStatus INT(1) DEFAULT NULL;

-- ============================================================================
-- SECTION 6: OPTIMIZE TABLES
-- ============================================================================
-- Rebuild tables and update statistics for better query performance

OPTIMIZE TABLE tblstudents;
OPTIMIZE TABLE tblbooks;
OPTIMIZE TABLE tblissuedbookdetails;
OPTIMIZE TABLE tblcategory;
OPTIMIZE TABLE tblauthors;
OPTIMIZE TABLE admin;
OPTIMIZE TABLE auth_codes;

-- ============================================================================
-- SECTION 7: ANALYZE TABLES
-- ============================================================================
-- Update table statistics to help the query optimizer

ANALYZE TABLE tblstudents;
ANALYZE TABLE tblbooks;
ANALYZE TABLE tblissuedbookdetails;
ANALYZE TABLE tblcategory;
ANALYZE TABLE tblauthors;
ANALYZE TABLE admin;
ANALYZE TABLE auth_codes;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
-- Run these queries after executing the migration to verify indexes were created

-- Show all indexes on tblstudents
-- SHOW INDEX FROM tblstudents;

-- Show all indexes on tblissuedbookdetails
-- SHOW INDEX FROM tblissuedbookdetails;

-- Show all indexes on tblbooks
-- SHOW INDEX FROM tblbooks;

-- Check password column size
-- DESCRIBE tblstudents;
-- DESCRIBE admin;

-- ============================================================================
-- ROLLBACK SCRIPT (if needed)
-- ============================================================================
-- Save this section in a separate file if you need to rollback changes

/*
-- Drop all indexes (if rollback needed)
DROP INDEX IF EXISTS idx_students_email ON tblstudents;
DROP INDEX IF EXISTS idx_students_studentid ON tblstudents;
DROP INDEX IF EXISTS idx_students_status ON tblstudents;
DROP INDEX IF EXISTS idx_students_telegram ON tblstudents;
DROP INDEX IF EXISTS idx_issued_studentid ON tblissuedbookdetails;
DROP INDEX IF EXISTS idx_issued_bookid ON tblissuedbookdetails;
DROP INDEX IF EXISTS idx_issued_return_status ON tblissuedbookdetails;
DROP INDEX IF EXISTS idx_issued_dates ON tblissuedbookdetails;
DROP INDEX IF EXISTS idx_issued_student_status ON tblissuedbookdetails;
DROP INDEX IF EXISTS idx_books_isbn ON tblbooks;
DROP INDEX IF EXISTS idx_books_name ON tblbooks;
DROP INDEX IF EXISTS idx_books_category ON tblbooks;
DROP INDEX IF EXISTS idx_books_author ON tblbooks;
DROP INDEX IF EXISTS idx_category_name ON tblcategory;
DROP INDEX IF EXISTS idx_category_status ON tblcategory;
DROP INDEX IF EXISTS idx_authors_name ON tblauthors;
DROP INDEX IF EXISTS idx_auth_codes_student ON auth_codes;
DROP INDEX IF EXISTS idx_auth_codes_lookup ON auth_codes;
DROP INDEX IF EXISTS idx_admin_username ON admin;
*/

-- ============================================================================
-- COMPLETION MESSAGE
-- ============================================================================
SELECT 'Database optimization completed successfully!' AS Status;
SELECT 'Please verify indexes were created using: SHOW INDEX FROM <table_name>;' AS Next_Steps;
