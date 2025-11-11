<?php
/**
 * Role-Based Access Control (RBAC) Helper Functions
 * Manages user roles, permissions, and access control
 */

require_once(__DIR__ . '/audit.php');

/**
 * Get user's role
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @param string $userType 'admin' or 'student'
 * @return array|null Role information
 */
function getUserRole($dbh, $userId, $userType = 'admin') {
    try {
        if ($userType === 'admin') {
            $sql = "SELECT r.* FROM tblroles r
                    INNER JOIN admin a ON a.role_id = r.id
                    WHERE a.id = :user_id AND r.is_active = 1";
        } else {
            $sql = "SELECT r.* FROM tblroles r
                    INNER JOIN tblstudents s ON s.role_id = r.id
                    WHERE s.StudentId = :user_id AND r.is_active = 1";
        }

        $query = $dbh->prepare($sql);
        $query->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching user role: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user's permissions
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @param string $userType 'admin' or 'student'
 * @return array List of permission names
 */
function getUserPermissions($dbh, $userId, $userType = 'admin') {
    try {
        $role = getUserRole($dbh, $userId, $userType);

        if (!$role) {
            return [];
        }

        $sql = "SELECT p.permission_name
                FROM tblpermissions p
                INNER JOIN tblrole_permissions rp ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id";

        $query = $dbh->prepare($sql);
        $query->bindParam(':role_id', $role['id'], PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        log_error("Error fetching user permissions: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user has a specific permission
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @param string $permission Permission name to check
 * @param string $userType 'admin' or 'student'
 * @return bool True if user has permission
 */
function hasPermission($dbh, $userId, $permission, $userType = 'admin') {
    $permissions = getUserPermissions($dbh, $userId, $userType);
    return in_array($permission, $permissions);
}

/**
 * Check if user has any of the specified permissions
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @param array $permissions Array of permission names
 * @param string $userType 'admin' or 'student'
 * @return bool True if user has at least one permission
 */
function hasAnyPermission($dbh, $userId, $permissions, $userType = 'admin') {
    $userPermissions = getUserPermissions($dbh, $userId, $userType);
    return count(array_intersect($permissions, $userPermissions)) > 0;
}

/**
 * Check if user has all specified permissions
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @param array $permissions Array of permission names
 * @param string $userType 'admin' or 'student'
 * @return bool True if user has all permissions
 */
function hasAllPermissions($dbh, $userId, $permissions, $userType = 'admin') {
    $userPermissions = getUserPermissions($dbh, $userId, $userType);
    return count(array_diff($permissions, $userPermissions)) === 0;
}

/**
 * Require permission (redirect if not authorized)
 *
 * @param PDO $dbh Database handle
 * @param string $permission Permission name
 * @param string $redirectUrl URL to redirect if unauthorized
 * @return void
 */
function requirePermission($dbh, $permission, $redirectUrl = 'dashboard.php') {
    $userId = $_SESSION['stdid'] ?? $_SESSION['alogin'] ?? null;
    $userType = isset($_SESSION['alogin']) ? 'admin' : 'student';

    if (!$userId || !hasPermission($dbh, $userId, $permission, $userType)) {
        logAudit($dbh, $userId, $userType, 'access_denied', 'security',
            "Attempted to access $permission without authorization", 'failed');

        $_SESSION['error'] = "You don't have permission to access this resource.";
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Check if IP is whitelisted for role
 *
 * @param PDO $dbh Database handle
 * @param string $ip IP address
 * @param int $roleId Role ID
 * @return bool True if IP is whitelisted
 */
function isIpWhitelisted($dbh, $ip, $roleId) {
    try {
        // Check if IP whitelist is enabled
        $config = getSystemConfig($dbh, 'enable_ip_whitelist');
        if ($config !== 'true') {
            return true; // IP whitelist disabled, allow all
        }

        $sql = "SELECT COUNT(*) FROM tblip_whitelist
                WHERE ip_address = :ip
                AND (role_id = :role_id OR role_id IS NULL)
                AND is_active = 1";

        $query = $dbh->prepare($sql);
        $query->bindParam(':ip', $ip, PDO::PARAM_STR);
        $query->bindParam(':role_id', $roleId, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchColumn() > 0;
    } catch (PDOException $e) {
        log_error("Error checking IP whitelist: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all roles
 *
 * @param PDO $dbh Database handle
 * @param bool $activeOnly Get only active roles
 * @return array List of roles
 */
function getAllRoles($dbh, $activeOnly = true) {
    try {
        $sql = "SELECT * FROM tblroles";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY role_name";

        $query = $dbh->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching roles: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all permissions
 *
 * @param PDO $dbh Database handle
 * @param string $category Optional category filter
 * @return array List of permissions
 */
function getAllPermissions($dbh, $category = null) {
    try {
        $sql = "SELECT * FROM tblpermissions";
        if ($category) {
            $sql .= " WHERE permission_category = :category";
        }
        $sql .= " ORDER BY permission_category, permission_name";

        $query = $dbh->prepare($sql);
        if ($category) {
            $query->bindParam(':category', $category, PDO::PARAM_STR);
        }
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching permissions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get permissions for a role
 *
 * @param PDO $dbh Database handle
 * @param int $roleId Role ID
 * @return array List of permissions
 */
function getRolePermissions($dbh, $roleId) {
    try {
        $sql = "SELECT p.*
                FROM tblpermissions p
                INNER JOIN tblrole_permissions rp ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id
                ORDER BY p.permission_category, p.permission_name";

        $query = $dbh->prepare($sql);
        $query->bindParam(':role_id', $roleId, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching role permissions: " . $e->getMessage());
        return [];
    }
}

/**
 * Assign permission to role
 *
 * @param PDO $dbh Database handle
 * @param int $roleId Role ID
 * @param int $permissionId Permission ID
 * @return bool Success status
 */
function assignPermissionToRole($dbh, $roleId, $permissionId) {
    try {
        $sql = "INSERT INTO tblrole_permissions (role_id, permission_id)
                VALUES (:role_id, :permission_id)
                ON DUPLICATE KEY UPDATE role_id = role_id";

        $query = $dbh->prepare($sql);
        $query->bindParam(':role_id', $roleId, PDO::PARAM_INT);
        $query->bindParam(':permission_id', $permissionId, PDO::PARAM_INT);

        return $query->execute();
    } catch (PDOException $e) {
        log_error("Error assigning permission to role: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove permission from role
 *
 * @param PDO $dbh Database handle
 * @param int $roleId Role ID
 * @param int $permissionId Permission ID
 * @return bool Success status
 */
function removePermissionFromRole($dbh, $roleId, $permissionId) {
    try {
        $sql = "DELETE FROM tblrole_permissions
                WHERE role_id = :role_id AND permission_id = :permission_id";

        $query = $dbh->prepare($sql);
        $query->bindParam(':role_id', $roleId, PDO::PARAM_INT);
        $query->bindParam(':permission_id', $permissionId, PDO::PARAM_INT);

        return $query->execute();
    } catch (PDOException $e) {
        log_error("Error removing permission from role: " . $e->getMessage());
        return false;
    }
}

/**
 * Get system configuration value
 *
 * @param PDO $dbh Database handle
 * @param string $key Configuration key
 * @param mixed $default Default value if not found
 * @return mixed Configuration value
 */
function getSystemConfig($dbh, $key, $default = null) {
    try {
        $sql = "SELECT config_value, config_type FROM tblsystem_config WHERE config_key = :key";
        $query = $dbh->prepare($sql);
        $query->bindParam(':key', $key, PDO::PARAM_STR);
        $query->execute();

        $result = $query->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return $default;
        }

        // Convert value based on type
        $value = $result['config_value'];
        switch ($result['config_type']) {
            case 'number':
                return is_numeric($value) ? (float)$value : $default;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true) ?: $default;
            default:
                return $value;
        }
    } catch (PDOException $e) {
        log_error("Error fetching system config: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set system configuration value
 *
 * @param PDO $dbh Database handle
 * @param string $key Configuration key
 * @param mixed $value Configuration value
 * @return bool Success status
 */
function setSystemConfig($dbh, $key, $value) {
    try {
        // Convert value to string based on type
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $value = json_encode($value);
        }

        $sql = "UPDATE tblsystem_config SET config_value = :value WHERE config_key = :key";
        $query = $dbh->prepare($sql);
        $query->bindParam(':key', $key, PDO::PARAM_STR);
        $query->bindParam(':value', $value, PDO::PARAM_STR);

        return $query->execute();
    } catch (PDOException $e) {
        log_error("Error setting system config: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's role name
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @param string $userType 'admin' or 'student'
 * @return string Role name
 */
function getUserRoleName($dbh, $userId, $userType = 'admin') {
    $role = getUserRole($dbh, $userId, $userType);
    return $role ? $role['role_name'] : 'unknown';
}

/**
 * Check if user is super admin
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @return bool True if user is super admin
 */
function isSuperAdmin($dbh, $userId) {
    $role = getUserRoleName($dbh, $userId, 'admin');
    return $role === 'super_admin';
}

/**
 * Check if user is admin (any admin role)
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @return bool True if user has any admin role
 */
function isAdmin($dbh, $userId) {
    $role = getUserRoleName($dbh, $userId, 'admin');
    return in_array($role, ['super_admin', 'admin', 'librarian', 'assistant']);
}
?>
