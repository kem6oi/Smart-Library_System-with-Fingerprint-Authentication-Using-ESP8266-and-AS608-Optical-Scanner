<?php
/**
 * Audit Trail & Activity Logging System
 * Tracks all user actions for security and compliance
 */

require_once(__DIR__ . '/geolocation.php');

/**
 * Log an audit event
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @param string $userType 'admin' or 'student'
 * @param string $action Action performed
 * @param string $category Action category
 * @param string $description Detailed description
 * @param string $status 'success', 'failed', or 'warning'
 * @param array $oldValues Old values (for updates)
 * @param array $newValues New values (for updates)
 * @return bool Success status
 */
function logAudit($dbh, $userId, $userType, $action, $category, $description, $status = 'success', $oldValues = null, $newValues = null) {
    try {
        // Get IP address and user agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Get geolocation
        $geoLocation = getGeoLocation($ipAddress);

        // Convert arrays to JSON
        $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
        $newValuesJson = $newValues ? json_encode($newValues) : null;

        $sql = "INSERT INTO tblaudit_logs
                (user_id, user_type, action, action_category, description,
                 ip_address, user_agent, geo_location, old_values, new_values, status)
                VALUES
                (:user_id, :user_type, :action, :category, :description,
                 :ip_address, :user_agent, :geo_location, :old_values, :new_values, :status)";

        $query = $dbh->prepare($sql);
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':user_type', $userType, PDO::PARAM_STR);
        $query->bindParam(':action', $action, PDO::PARAM_STR);
        $query->bindParam(':category', $category, PDO::PARAM_STR);
        $query->bindParam(':description', $description, PDO::PARAM_STR);
        $query->bindParam(':ip_address', $ipAddress, PDO::PARAM_STR);
        $query->bindParam(':user_agent', $userAgent, PDO::PARAM_STR);
        $query->bindParam(':geo_location', $geoLocation, PDO::PARAM_STR);
        $query->bindParam(':old_values', $oldValuesJson, PDO::PARAM_STR);
        $query->bindParam(':new_values', $newValuesJson, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);

        return $query->execute();
    } catch (PDOException $e) {
        log_error("Error logging audit: " . $e->getMessage());
        return false;
    }
}

/**
 * Log login attempt
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID (null for failed login)
 * @param string $userType 'admin' or 'student'
 * @param bool $success Login success status
 * @param string $reason Failure reason if applicable
 * @return bool Success status
 */
function logLogin($dbh, $userId, $userType, $success, $reason = '') {
    $action = $success ? 'login_success' : 'login_failed';
    $status = $success ? 'success' : 'failed';
    $description = $success ? "User logged in successfully" : "Login failed: $reason";

    return logAudit($dbh, $userId, $userType, $action, 'authentication', $description, $status);
}

/**
 * Log logout
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @param string $userType 'admin' or 'student'
 * @return bool Success status
 */
function logLogout($dbh, $userId, $userType) {
    return logAudit($dbh, $userId, $userType, 'logout', 'authentication', 'User logged out', 'success');
}

/**
 * Log book issue
 *
 * @param PDO $dbh Database handle
 * @param int $adminId Admin ID who issued the book
 * @param string $studentId Student ID
 * @param int $bookId Book ID
 * @param string $bookName Book name
 * @return bool Success status
 */
function logBookIssue($dbh, $adminId, $studentId, $bookId, $bookName) {
    $description = "Issued book '$bookName' (ID: $bookId) to student $studentId";
    $newValues = ['student_id' => $studentId, 'book_id' => $bookId, 'book_name' => $bookName];

    return logAudit($dbh, $adminId, 'admin', 'book_issue', 'circulation', $description, 'success', null, $newValues);
}

/**
 * Log book return
 *
 * @param PDO $dbh Database handle
 * @param int $adminId Admin ID who processed the return
 * @param string $studentId Student ID
 * @param int $bookId Book ID
 * @param string $bookName Book name
 * @param float $fine Fine amount if any
 * @return bool Success status
 */
function logBookReturn($dbh, $adminId, $studentId, $bookId, $bookName, $fine = 0) {
    $description = "Processed return of book '$bookName' (ID: $bookId) from student $studentId";
    if ($fine > 0) {
        $description .= " with fine: $$fine";
    }

    $newValues = ['student_id' => $studentId, 'book_id' => $bookId, 'book_name' => $bookName, 'fine' => $fine];

    return logAudit($dbh, $adminId, 'admin', 'book_return', 'circulation', $description, 'success', null, $newValues);
}

/**
 * Log book addition
 *
 * @param PDO $dbh Database handle
 * @param int $adminId Admin ID
 * @param int $bookId Book ID
 * @param string $bookName Book name
 * @param array $bookDetails Book details
 * @return bool Success status
 */
function logBookAdd($dbh, $adminId, $bookId, $bookName, $bookDetails) {
    $description = "Added new book '$bookName' (ID: $bookId)";
    return logAudit($dbh, $adminId, 'admin', 'book_add', 'books', $description, 'success', null, $bookDetails);
}

/**
 * Log book update
 *
 * @param PDO $dbh Database handle
 * @param int $adminId Admin ID
 * @param int $bookId Book ID
 * @param string $bookName Book name
 * @param array $oldValues Old book details
 * @param array $newValues New book details
 * @return bool Success status
 */
function logBookUpdate($dbh, $adminId, $bookId, $bookName, $oldValues, $newValues) {
    $description = "Updated book '$bookName' (ID: $bookId)";
    return logAudit($dbh, $adminId, 'admin', 'book_update', 'books', $description, 'success', $oldValues, $newValues);
}

/**
 * Log book deletion
 *
 * @param PDO $dbh Database handle
 * @param int $adminId Admin ID
 * @param int $bookId Book ID
 * @param string $bookName Book name
 * @param array $bookDetails Book details
 * @return bool Success status
 */
function logBookDelete($dbh, $adminId, $bookId, $bookName, $bookDetails) {
    $description = "Deleted book '$bookName' (ID: $bookId)";
    return logAudit($dbh, $adminId, 'admin', 'book_delete', 'books', $description, 'success', $bookDetails, null);
}

/**
 * Log user creation
 *
 * @param PDO $dbh Database handle
 * @param int $adminId Admin ID (null for self-registration)
 * @param string $newUserId New user's ID
 * @param string $userType 'admin' or 'student'
 * @param array $userDetails User details (without password)
 * @return bool Success status
 */
function logUserCreation($dbh, $adminId, $newUserId, $userType, $userDetails) {
    $description = $adminId ? "Created new $userType user: $newUserId" : "User self-registered: $newUserId";
    $actor = $adminId ? $adminId : $newUserId;
    $actorType = $adminId ? 'admin' : $userType;

    return logAudit($dbh, $actor, $actorType, 'user_create', 'users', $description, 'success', null, $userDetails);
}

/**
 * Log user update
 *
 * @param PDO $dbh Database handle
 * @param int $adminId Admin ID
 * @param string $userId User ID being updated
 * @param string $userType 'admin' or 'student'
 * @param array $oldValues Old user details
 * @param array $newValues New user details
 * @return bool Success status
 */
function logUserUpdate($dbh, $adminId, $userId, $userType, $oldValues, $newValues) {
    $description = "Updated $userType user: $userId";
    return logAudit($dbh, $adminId, 'admin', 'user_update', 'users', $description, 'success', $oldValues, $newValues);
}

/**
 * Log user block/unblock
 *
 * @param PDO $dbh Database handle
 * @param int $adminId Admin ID
 * @param string $userId User ID being blocked/unblocked
 * @param bool $blocked True if blocked, false if unblocked
 * @return bool Success status
 */
function logUserStatusChange($dbh, $adminId, $userId, $blocked) {
    $action = $blocked ? 'user_blocked' : 'user_unblocked';
    $description = $blocked ? "Blocked user: $userId" : "Unblocked user: $userId";

    return logAudit($dbh, $adminId, 'admin', $action, 'users', $description, 'success');
}

/**
 * Log password change
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @param string $userType 'admin' or 'student'
 * @param bool $forced True if forced by admin
 * @return bool Success status
 */
function logPasswordChange($dbh, $userId, $userType, $forced = false) {
    $description = $forced ? "Password reset by administrator" : "User changed password";
    return logAudit($dbh, $userId, $userType, 'password_change', 'security', $description, 'success');
}

/**
 * Log system configuration change
 *
 * @param PDO $dbh Database handle
 * @param int $adminId Admin ID
 * @param string $configKey Configuration key
 * @param mixed $oldValue Old value
 * @param mixed $newValue New value
 * @return bool Success status
 */
function logConfigChange($dbh, $adminId, $configKey, $oldValue, $newValue) {
    $description = "Changed system config: $configKey";
    $oldValues = ['config_key' => $configKey, 'value' => $oldValue];
    $newValues = ['config_key' => $configKey, 'value' => $newValue];

    return logAudit($dbh, $adminId, 'admin', 'config_change', 'system', $description, 'success', $oldValues, $newValues);
}

/**
 * Get audit logs with filters
 *
 * @param PDO $dbh Database handle
 * @param array $filters Filters (user_id, user_type, action, category, date_from, date_to, status)
 * @param int $limit Result limit
 * @param int $offset Result offset
 * @return array Audit logs
 */
function getAuditLogs($dbh, $filters = [], $limit = 100, $offset = 0) {
    try {
        $sql = "SELECT * FROM tblaudit_logs WHERE 1=1";
        $params = [];

        if (isset($filters['user_id'])) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (isset($filters['user_type'])) {
            $sql .= " AND user_type = :user_type";
            $params[':user_type'] = $filters['user_type'];
        }

        if (isset($filters['action'])) {
            $sql .= " AND action = :action";
            $params[':action'] = $filters['action'];
        }

        if (isset($filters['category'])) {
            $sql .= " AND action_category = :category";
            $params[':category'] = $filters['category'];
        }

        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (isset($filters['date_from'])) {
            $sql .= " AND created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $sql .= " AND created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        if (isset($filters['ip_address'])) {
            $sql .= " AND ip_address = :ip_address";
            $params[':ip_address'] = $filters['ip_address'];
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $query = $dbh->prepare($sql);

        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }

        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);

        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching audit logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Get audit log statistics
 *
 * @param PDO $dbh Database handle
 * @param string $period 'today', 'week', 'month', 'year'
 * @return array Statistics
 */
function getAuditStats($dbh, $period = 'today') {
    try {
        $dateFilter = "";
        switch ($period) {
            case 'today':
                $dateFilter = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $dateFilter = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateFilter = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'year':
                $dateFilter = "created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                break;
        }

        $sql = "SELECT
                    COUNT(*) as total_events,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_events,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_events,
                    SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning_events,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM tblaudit_logs
                WHERE $dateFilter";

        $query = $dbh->prepare($sql);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching audit stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent activities for dashboard
 *
 * @param PDO $dbh Database handle
 * @param int $limit Number of recent activities
 * @return array Recent activities
 */
function getRecentActivities($dbh, $limit = 10) {
    try {
        $sql = "SELECT * FROM tblaudit_logs
                ORDER BY created_at DESC
                LIMIT :limit";

        $query = $dbh->prepare($sql);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching recent activities: " . $e->getMessage());
        return [];
    }
}
?>
