<?php
/**
 * Book Reviews and Ratings System
 * Allows users to rate and review books
 */

require_once(__DIR__ . '/rbac.php');
require_once(__DIR__ . '/audit.php');

/**
 * Add or update a book review
 *
 * @param PDO $dbh Database handle
 * @param int $bookId Book ID
 * @param string $studentId Student ID
 * @param int $rating Rating (1-5)
 * @param string $reviewText Review text (optional)
 * @return array Result with success status and message
 */
function addBookReview($dbh, $bookId, $studentId, $rating, $reviewText = '') {
    try {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
        }

        // Check if user has borrowed this book
        $sql = "SELECT COUNT(*) FROM tblissuedbookdetails
                WHERE BookId = :book_id AND StudentId = :student_id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        $query->execute();

        if ($query->fetchColumn() == 0) {
            return ['success' => false, 'message' => 'You can only review books you have borrowed'];
        }

        // Check if review already exists
        $sql = "SELECT id FROM tblbook_reviews
                WHERE book_id = :book_id AND student_id = :student_id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        $query->execute();
        $existingReview = $query->fetch(PDO::FETCH_ASSOC);

        if ($existingReview) {
            // Update existing review
            $sql = "UPDATE tblbook_reviews
                    SET rating = :rating, review_text = :review_text, updated_at = NOW()
                    WHERE id = :review_id";
            $query = $dbh->prepare($sql);
            $query->bindParam(':rating', $rating, PDO::PARAM_INT);
            $query->bindParam(':review_text', $reviewText, PDO::PARAM_STR);
            $query->bindParam(':review_id', $existingReview['id'], PDO::PARAM_INT);
            $query->execute();

            // Log audit
            logAudit($dbh, $studentId, 'student', 'review_update', 'reviews',
                "Updated review for book ID: $bookId", 'success');

            return ['success' => true, 'message' => 'Review updated successfully'];
        } else {
            // Insert new review
            $sql = "INSERT INTO tblbook_reviews (book_id, student_id, rating, review_text)
                    VALUES (:book_id, :student_id, :rating, :review_text)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
            $query->bindParam(':rating', $rating, PDO::PARAM_INT);
            $query->bindParam(':review_text', $reviewText, PDO::PARAM_STR);
            $query->execute();

            // Log audit
            logAudit($dbh, $studentId, 'student', 'review_add', 'reviews',
                "Added review for book ID: $bookId with rating: $rating", 'success');

            return ['success' => true, 'message' => 'Review added successfully'];
        }
    } catch (PDOException $e) {
        log_error("Error adding book review: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add review'];
    }
}

/**
 * Get reviews for a book
 *
 * @param PDO $dbh Database handle
 * @param int $bookId Book ID
 * @param int $limit Number of reviews to fetch
 * @param int $offset Offset for pagination
 * @return array Reviews
 */
function getBookReviews($dbh, $bookId, $limit = 10, $offset = 0) {
    try {
        $sql = "SELECT
                    r.id, r.rating, r.review_text, r.created_at, r.updated_at,
                    s.FullName as student_name,
                    (SELECT COUNT(*) FROM tblreview_votes WHERE review_id = r.id AND vote_type = 'helpful') as helpful_count,
                    (SELECT COUNT(*) FROM tblreview_votes WHERE review_id = r.id AND vote_type = 'not_helpful') as not_helpful_count
                FROM tblbook_reviews r
                INNER JOIN tblstudents s ON s.StudentId = r.student_id
                WHERE r.book_id = :book_id
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";

        $query = $dbh->prepare($sql);
        $query->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error getting book reviews: " . $e->getMessage());
        return [];
    }
}

/**
 * Get book rating summary
 *
 * @param PDO $dbh Database handle
 * @param int $bookId Book ID
 * @return array Rating summary
 */
function getBookRatingSummary($dbh, $bookId) {
    try {
        $sql = "SELECT
                    COUNT(*) as total_reviews,
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM tblbook_reviews
                WHERE book_id = :book_id";

        $query = $dbh->prepare($sql);
        $query->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $query->execute();

        $summary = $query->fetch(PDO::FETCH_ASSOC);

        // Calculate percentages
        if ($summary['total_reviews'] > 0) {
            $summary['five_star_pct'] = ($summary['five_star'] / $summary['total_reviews']) * 100;
            $summary['four_star_pct'] = ($summary['four_star'] / $summary['total_reviews']) * 100;
            $summary['three_star_pct'] = ($summary['three_star'] / $summary['total_reviews']) * 100;
            $summary['two_star_pct'] = ($summary['two_star'] / $summary['total_reviews']) * 100;
            $summary['one_star_pct'] = ($summary['one_star'] / $summary['total_reviews']) * 100;
        } else {
            $summary['five_star_pct'] = 0;
            $summary['four_star_pct'] = 0;
            $summary['three_star_pct'] = 0;
            $summary['two_star_pct'] = 0;
            $summary['one_star_pct'] = 0;
        }

        return $summary;
    } catch (PDOException $e) {
        log_error("Error getting book rating summary: " . $e->getMessage());
        return [];
    }
}

/**
 * Vote on a review (helpful/not helpful)
 *
 * @param PDO $dbh Database handle
 * @param int $reviewId Review ID
 * @param string $studentId Student ID
 * @param string $voteType 'helpful' or 'not_helpful'
 * @return array Result
 */
function voteOnReview($dbh, $reviewId, $studentId, $voteType) {
    try {
        if (!in_array($voteType, ['helpful', 'not_helpful'])) {
            return ['success' => false, 'message' => 'Invalid vote type'];
        }

        // Check if already voted
        $sql = "SELECT id, vote_type FROM tblreview_votes
                WHERE review_id = :review_id AND student_id = :student_id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':review_id', $reviewId, PDO::PARAM_INT);
        $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        $query->execute();
        $existingVote = $query->fetch(PDO::FETCH_ASSOC);

        if ($existingVote) {
            if ($existingVote['vote_type'] === $voteType) {
                // Remove vote if clicking same button
                $sql = "DELETE FROM tblreview_votes WHERE id = :vote_id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':vote_id', $existingVote['id'], PDO::PARAM_INT);
                $query->execute();
                return ['success' => true, 'message' => 'Vote removed'];
            } else {
                // Update vote
                $sql = "UPDATE tblreview_votes SET vote_type = :vote_type WHERE id = :vote_id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':vote_type', $voteType, PDO::PARAM_STR);
                $query->bindParam(':vote_id', $existingVote['id'], PDO::PARAM_INT);
                $query->execute();
                return ['success' => true, 'message' => 'Vote updated'];
            }
        } else {
            // Insert new vote
            $sql = "INSERT INTO tblreview_votes (review_id, student_id, vote_type)
                    VALUES (:review_id, :student_id, :vote_type)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':review_id', $reviewId, PDO::PARAM_INT);
            $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
            $query->bindParam(':vote_type', $voteType, PDO::PARAM_STR);
            $query->execute();
            return ['success' => true, 'message' => 'Vote recorded'];
        }
    } catch (PDOException $e) {
        log_error("Error voting on review: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to record vote'];
    }
}

/**
 * Check if student has reviewed a book
 *
 * @param PDO $dbh Database handle
 * @param int $bookId Book ID
 * @param string $studentId Student ID
 * @return array|null Review if exists
 */
function getStudentReview($dbh, $bookId, $studentId) {
    try {
        $sql = "SELECT * FROM tblbook_reviews
                WHERE book_id = :book_id AND student_id = :student_id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error getting student review: " . $e->getMessage());
        return null;
    }
}

/**
 * Delete a review
 *
 * @param PDO $dbh Database handle
 * @param int $reviewId Review ID
 * @param string $userId User ID (student or admin)
 * @param string $userType 'student' or 'admin'
 * @return array Result
 */
function deleteReview($dbh, $reviewId, $userId, $userType) {
    try {
        // Get review details for audit
        $sql = "SELECT * FROM tblbook_reviews WHERE id = :review_id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':review_id', $reviewId, PDO::PARAM_INT);
        $query->execute();
        $review = $query->fetch(PDO::FETCH_ASSOC);

        if (!$review) {
            return ['success' => false, 'message' => 'Review not found'];
        }

        // Check permissions
        if ($userType === 'student' && $review['student_id'] !== $userId) {
            return ['success' => false, 'message' => 'You can only delete your own reviews'];
        }

        // Delete review (cascade will delete votes)
        $sql = "DELETE FROM tblbook_reviews WHERE id = :review_id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':review_id', $reviewId, PDO::PARAM_INT);
        $query->execute();

        // Log audit
        logAudit($dbh, $userId, $userType, 'review_delete', 'reviews',
            "Deleted review ID: $reviewId for book ID: {$review['book_id']}", 'success');

        return ['success' => true, 'message' => 'Review deleted successfully'];
    } catch (PDOException $e) {
        log_error("Error deleting review: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete review'];
    }
}

/**
 * Get recent reviews across all books
 *
 * @param PDO $dbh Database handle
 * @param int $limit Number of reviews
 * @return array Reviews
 */
function getRecentReviews($dbh, $limit = 10) {
    try {
        $sql = "SELECT
                    r.id, r.rating, r.review_text, r.created_at,
                    s.FullName as student_name,
                    b.BookName, b.id as book_id
                FROM tblbook_reviews r
                INNER JOIN tblstudents s ON s.StudentId = r.student_id
                INNER JOIN tblbooks b ON b.id = r.book_id
                ORDER BY r.created_at DESC
                LIMIT :limit";

        $query = $dbh->prepare($sql);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error getting recent reviews: " . $e->getMessage());
        return [];
    }
}

/**
 * Get top-rated books
 *
 * @param PDO $dbh Database handle
 * @param int $limit Number of books
 * @param int $minReviews Minimum reviews required
 * @return array Top-rated books
 */
function getTopRatedBooks($dbh, $limit = 10, $minReviews = 3) {
    try {
        $sql = "SELECT
                    b.id, b.BookName, b.ISBNNumber,
                    a.AuthorName, c.CategoryName,
                    AVG(r.rating) as avg_rating,
                    COUNT(r.id) as review_count
                FROM tblbooks b
                INNER JOIN tblbook_reviews r ON r.book_id = b.id
                LEFT JOIN tblauthors a ON a.id = b.AuthorId
                LEFT JOIN tblcategory c ON c.id = b.CatId
                GROUP BY b.id
                HAVING review_count >= :min_reviews
                ORDER BY avg_rating DESC, review_count DESC
                LIMIT :limit";

        $query = $dbh->prepare($sql);
        $query->bindValue(':min_reviews', $minReviews, PDO::PARAM_INT);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error getting top-rated books: " . $e->getMessage());
        return [];
    }
}

/**
 * Report a review as inappropriate
 *
 * @param PDO $dbh Database handle
 * @param int $reviewId Review ID
 * @param string $studentId Student ID who reported
 * @param string $reason Report reason
 * @return array Result
 */
function reportReview($dbh, $reviewId, $studentId, $reason) {
    try {
        // Mark review as reported (could add a tblreview_reports table)
        // For now, we'll log it in audit
        logAudit($dbh, $studentId, 'student', 'review_report', 'reviews',
            "Reported review ID: $reviewId. Reason: $reason", 'warning');

        return ['success' => true, 'message' => 'Review reported successfully'];
    } catch (Exception $e) {
        log_error("Error reporting review: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to report review'];
    }
}
?>
