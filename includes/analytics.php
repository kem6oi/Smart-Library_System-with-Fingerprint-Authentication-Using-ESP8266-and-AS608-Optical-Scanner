<?php
/**
 * Analytics System
 * Provides comprehensive statistics and analytics for the library system
 */

require_once(__DIR__ . '/rbac.php');

/**
 * Record daily analytics
 * Should be called from a cron job daily to aggregate statistics
 *
 * @param PDO $dbh Database handle
 * @return bool Success status
 */
function recordDailyAnalytics($dbh) {
    try {
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Check if already recorded
        $sql = "SELECT COUNT(*) FROM tblanalytics_daily WHERE analytics_date = :date";
        $query = $dbh->prepare($sql);
        $query->bindParam(':date', $yesterday, PDO::PARAM_STR);
        $query->execute();

        if ($query->fetchColumn() > 0) {
            return true; // Already recorded
        }

        // Get statistics for yesterday
        $stats = getDailyStats($dbh, $yesterday);

        $sql = "INSERT INTO tblanalytics_daily
                (analytics_date, books_issued, books_returned, new_registrations,
                 total_active_users, total_overdue_books, total_fines_collected,
                 unique_visitors, avg_return_time_days)
                VALUES
                (:date, :issued, :returned, :new_reg, :active_users, :overdue,
                 :fines, :visitors, :avg_return)";

        $query = $dbh->prepare($sql);
        $query->bindParam(':date', $yesterday, PDO::PARAM_STR);
        $query->bindParam(':issued', $stats['books_issued'], PDO::PARAM_INT);
        $query->bindParam(':returned', $stats['books_returned'], PDO::PARAM_INT);
        $query->bindParam(':new_reg', $stats['new_registrations'], PDO::PARAM_INT);
        $query->bindParam(':active_users', $stats['total_active_users'], PDO::PARAM_INT);
        $query->bindParam(':overdue', $stats['total_overdue_books'], PDO::PARAM_INT);
        $query->bindParam(':fines', $stats['total_fines_collected']);
        $query->bindParam(':visitors', $stats['unique_visitors'], PDO::PARAM_INT);
        $query->bindParam(':avg_return', $stats['avg_return_time_days']);

        return $query->execute();
    } catch (PDOException $e) {
        log_error("Error recording daily analytics: " . $e->getMessage());
        return false;
    }
}

/**
 * Get daily statistics for a specific date
 *
 * @param PDO $dbh Database handle
 * @param string $date Date in Y-m-d format
 * @return array Statistics
 */
function getDailyStats($dbh, $date = null) {
    try {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $stats = [];

        // Books issued on date
        $sql = "SELECT COUNT(*) FROM tblissuedbookdetails WHERE DATE(IssuesDate) = :date";
        $query = $dbh->prepare($sql);
        $query->bindParam(':date', $date, PDO::PARAM_STR);
        $query->execute();
        $stats['books_issued'] = (int)$query->fetchColumn();

        // Books returned on date
        $sql = "SELECT COUNT(*) FROM tblissuedbookdetails WHERE DATE(ReturnDate) = :date AND RetrunStatus = 1";
        $query = $dbh->prepare($sql);
        $query->bindParam(':date', $date, PDO::PARAM_STR);
        $query->execute();
        $stats['books_returned'] = (int)$query->fetchColumn();

        // New registrations on date
        $sql = "SELECT COUNT(*) FROM tblstudents WHERE DATE(RegDate) = :date";
        $query = $dbh->prepare($sql);
        $query->bindParam(':date', $date, PDO::PARAM_STR);
        $query->execute();
        $stats['new_registrations'] = (int)$query->fetchColumn();

        // Total active users
        $sql = "SELECT COUNT(*) FROM tblstudents WHERE Status = 1";
        $query = $dbh->prepare($sql);
        $query->execute();
        $stats['total_active_users'] = (int)$query->fetchColumn();

        // Total overdue books
        $sql = "SELECT COUNT(*) FROM tblissuedbookdetails
                WHERE RetrunStatus IS NULL AND DATE(ReturnDate) < :date";
        $query = $dbh->prepare($sql);
        $query->bindParam(':date', $date, PDO::PARAM_STR);
        $query->execute();
        $stats['total_overdue_books'] = (int)$query->fetchColumn();

        // Total fines collected
        $sql = "SELECT COALESCE(SUM(fine), 0) FROM tblissuedbookdetails
                WHERE DATE(ReturnDate) = :date AND fine > 0";
        $query = $dbh->prepare($sql);
        $query->bindParam(':date', $date, PDO::PARAM_STR);
        $query->execute();
        $stats['total_fines_collected'] = (float)$query->fetchColumn();

        // Unique visitors from audit logs
        $sql = "SELECT COUNT(DISTINCT user_id) FROM tblaudit_logs
                WHERE DATE(created_at) = :date";
        $query = $dbh->prepare($sql);
        $query->bindParam(':date', $date, PDO::PARAM_STR);
        $query->execute();
        $stats['unique_visitors'] = (int)$query->fetchColumn();

        // Average return time in days
        $sql = "SELECT AVG(DATEDIFF(ReturnDate, IssuesDate)) FROM tblissuedbookdetails
                WHERE DATE(ReturnDate) = :date AND RetrunStatus = 1";
        $query = $dbh->prepare($sql);
        $query->bindParam(':date', $date, PDO::PARAM_STR);
        $query->execute();
        $stats['avg_return_time_days'] = (float)$query->fetchColumn() ?: 0;

        return $stats;
    } catch (PDOException $e) {
        log_error("Error getting daily stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get statistics for today
 *
 * @param PDO $dbh Database handle
 * @return array Today's statistics
 */
function getTodayStats($dbh) {
    return getDailyStats($dbh, date('Y-m-d'));
}

/**
 * Get trend data for charts
 *
 * @param PDO $dbh Database handle
 * @param string $metric Metric name
 * @param int $days Number of days
 * @return array Array of dates and values
 */
function getTrendData($dbh, $metric, $days = 7) {
    try {
        $validMetrics = [
            'books_issued', 'books_returned', 'new_registrations',
            'total_overdue_books', 'total_fines_collected', 'unique_visitors'
        ];

        if (!in_array($metric, $validMetrics)) {
            return [];
        }

        $sql = "SELECT analytics_date, $metric as value
                FROM tblanalytics_daily
                WHERE analytics_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                ORDER BY analytics_date ASC";

        $query = $dbh->prepare($sql);
        $query->bindParam(':days', $days, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error getting trend data: " . $e->getMessage());
        return [];
    }
}

/**
 * Get borrowing trend for the last N days
 *
 * @param PDO $dbh Database handle
 * @param int $days Number of days
 * @return array Dates and counts
 */
function getBorrowingTrend($dbh, $days = 7) {
    try {
        $sql = "SELECT DATE(IssuesDate) as date, COUNT(*) as count
                FROM tblissuedbookdetails
                WHERE IssuesDate >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                GROUP BY DATE(IssuesDate)
                ORDER BY date ASC";

        $query = $dbh->prepare($sql);
        $query->bindParam(':days', $days, PDO::PARAM_INT);
        $query->execute();

        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        // Fill in missing dates with zero counts
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[$date] = 0;
        }

        foreach ($results as $row) {
            $data[$row['date']] = (int)$row['count'];
        }

        return $data;
    } catch (PDOException $e) {
        log_error("Error getting borrowing trend: " . $e->getMessage());
        return [];
    }
}

/**
 * Update book popularity scores
 * Should be called periodically to update popularity rankings
 *
 * @param PDO $dbh Database handle
 * @return bool Success status
 */
function updateBookPopularity($dbh) {
    try {
        // Clear existing popularity data
        $sql = "TRUNCATE TABLE tblbook_popularity";
        $dbh->exec($sql);

        // Calculate popularity scores
        $sql = "INSERT INTO tblbook_popularity
                (book_id, total_borrows, current_borrows, avg_rating, total_reviews)
                SELECT
                    b.id as book_id,
                    COUNT(DISTINCT i.id) as total_borrows,
                    COUNT(DISTINCT CASE WHEN i.RetrunStatus IS NULL THEN i.id END) as current_borrows,
                    COALESCE(AVG(r.rating), 0) as avg_rating,
                    COUNT(DISTINCT r.id) as total_reviews
                FROM tblbooks b
                LEFT JOIN tblissuedbookdetails i ON i.BookId = b.id
                LEFT JOIN tblbook_reviews r ON r.book_id = b.id
                GROUP BY b.id";

        return $dbh->exec($sql) !== false;
    } catch (PDOException $e) {
        log_error("Error updating book popularity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get popular books
 *
 * @param PDO $dbh Database handle
 * @param int $limit Number of books
 * @param string $sortBy Sort by: 'borrows', 'rating', 'score'
 * @return array Popular books
 */
function getPopularBooks($dbh, $limit = 10, $sortBy = 'score') {
    try {
        $orderBy = 'popularity_score';
        switch ($sortBy) {
            case 'borrows':
                $orderBy = 'total_borrows';
                break;
            case 'rating':
                $orderBy = 'avg_rating';
                break;
        }

        $sql = "SELECT
                    b.id, b.BookName, b.ISBNNumber, b.BookPrice,
                    a.AuthorName, c.CategoryName,
                    p.total_borrows, p.current_borrows, p.avg_rating,
                    p.total_reviews, p.popularity_score
                FROM tblbook_popularity p
                INNER JOIN tblbooks b ON b.id = p.book_id
                LEFT JOIN tblauthors a ON a.id = b.AuthorId
                LEFT JOIN tblcategory c ON c.id = b.CatId
                ORDER BY $orderBy DESC
                LIMIT :limit";

        $query = $dbh->prepare($sql);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error getting popular books: " . $e->getMessage());
        return [];
    }
}

/**
 * Get category statistics
 *
 * @param PDO $dbh Database handle
 * @return array Category statistics
 */
function getCategoryStats($dbh) {
    try {
        $sql = "SELECT
                    c.CategoryName,
                    COUNT(DISTINCT b.id) as total_books,
                    COUNT(DISTINCT i.id) as total_borrows,
                    COALESCE(AVG(r.rating), 0) as avg_rating
                FROM tblcategory c
                LEFT JOIN tblbooks b ON b.CatId = c.id
                LEFT JOIN tblissuedbookdetails i ON i.BookId = b.id
                LEFT JOIN tblbook_reviews r ON r.book_id = b.id
                GROUP BY c.id, c.CategoryName
                HAVING total_books > 0
                ORDER BY total_borrows DESC";

        $query = $dbh->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error getting category stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user activity statistics
 *
 * @param PDO $dbh Database handle
 * @return array User activity stats
 */
function getUserActivityStats($dbh) {
    try {
        $stats = [];

        // Most active borrowers
        $sql = "SELECT
                    s.FullName, s.EmailId,
                    COUNT(i.id) as total_borrows,
                    COUNT(CASE WHEN i.RetrunStatus IS NULL THEN 1 END) as current_borrows
                FROM tblstudents s
                INNER JOIN tblissuedbookdetails i ON i.StudentId = s.StudentId
                WHERE s.Status = 1
                GROUP BY s.StudentId
                ORDER BY total_borrows DESC
                LIMIT 10";

        $query = $dbh->prepare($sql);
        $query->execute();
        $stats['top_borrowers'] = $query->fetchAll(PDO::FETCH_ASSOC);

        // Users with overdue books
        $sql = "SELECT
                    s.FullName, s.EmailId,
                    COUNT(i.id) as overdue_count,
                    MAX(DATEDIFF(CURDATE(), i.ReturnDate)) as max_days_overdue
                FROM tblstudents s
                INNER JOIN tblissuedbookdetails i ON i.StudentId = s.StudentId
                WHERE i.RetrunStatus IS NULL
                AND DATE(i.ReturnDate) < CURDATE()
                GROUP BY s.StudentId
                ORDER BY overdue_count DESC, max_days_overdue DESC
                LIMIT 10";

        $query = $dbh->prepare($sql);
        $query->execute();
        $stats['overdue_users'] = $query->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    } catch (PDOException $e) {
        log_error("Error getting user activity stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get monthly statistics summary
 *
 * @param PDO $dbh Database handle
 * @param string $month Month in Y-m format
 * @return array Monthly statistics
 */
function getMonthlyStats($dbh, $month = null) {
    try {
        if (!$month) {
            $month = date('Y-m');
        }

        $sql = "SELECT
                    SUM(books_issued) as total_issued,
                    SUM(books_returned) as total_returned,
                    SUM(new_registrations) as total_registrations,
                    AVG(total_overdue_books) as avg_overdue,
                    SUM(total_fines_collected) as total_fines,
                    SUM(unique_visitors) as total_visitors,
                    AVG(avg_return_time_days) as avg_return_time
                FROM tblanalytics_daily
                WHERE DATE_FORMAT(analytics_date, '%Y-%m') = :month";

        $query = $dbh->prepare($sql);
        $query->bindParam(':month', $month, PDO::PARAM_STR);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error getting monthly stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get analytics dashboard data
 * Combines multiple statistics for dashboard display
 *
 * @param PDO $dbh Database handle
 * @return array Dashboard data
 */
function getAnalyticsDashboard($dbh) {
    return [
        'today' => getTodayStats($dbh),
        'borrowing_trend' => getBorrowingTrend($dbh, 7),
        'popular_books' => getPopularBooks($dbh, 10),
        'category_stats' => getCategoryStats($dbh),
        'user_activity' => getUserActivityStats($dbh),
        'monthly' => getMonthlyStats($dbh)
    ];
}

/**
 * Export analytics data to CSV
 *
 * @param PDO $dbh Database handle
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return string CSV data
 */
function exportAnalyticsCSV($dbh, $startDate, $endDate) {
    try {
        $sql = "SELECT * FROM tblanalytics_daily
                WHERE analytics_date BETWEEN :start AND :end
                ORDER BY analytics_date ASC";

        $query = $dbh->prepare($sql);
        $query->bindParam(':start', $startDate, PDO::PARAM_STR);
        $query->bindParam(':end', $endDate, PDO::PARAM_STR);
        $query->execute();

        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            return '';
        }

        // Build CSV
        $csv = '';

        // Header row
        $csv .= implode(',', array_keys($results[0])) . "\n";

        // Data rows
        foreach ($results as $row) {
            $csv .= implode(',', array_values($row)) . "\n";
        }

        return $csv;
    } catch (PDOException $e) {
        log_error("Error exporting analytics CSV: " . $e->getMessage());
        return '';
    }
}
?>
