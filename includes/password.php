<?php
/**
 * Password Utility Functions
 * Handles secure password hashing and verification using bcrypt
 */

/**
 * Hash a password using bcrypt
 *
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against a hash
 * Supports both new bcrypt hashes and legacy MD5 hashes during migration
 *
 * @param string $password Plain text password
 * @param string $hash Stored password hash
 * @return bool True if password matches, false otherwise
 */
function verifyPassword($password, $hash) {
    // Check if it's a bcrypt hash (starts with $2y$ or $2a$)
    if (substr($hash, 0, 4) === '$2y$' || substr($hash, 0, 4) === '$2a$') {
        return password_verify($password, $hash);
    }

    // Legacy MD5 support during migration
    // If the hash is 32 characters, it's likely MD5
    if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        return md5($password) === $hash;
    }

    return false;
}

/**
 * Check if a password hash needs to be rehashed
 * Returns true if using legacy MD5 or if bcrypt needs upgrading
 *
 * @param string $hash Stored password hash
 * @return bool True if needs rehashing, false otherwise
 */
function needsRehash($hash) {
    // If it's MD5 (32 hex characters), it needs rehashing
    if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        return true;
    }

    // Check if bcrypt needs upgrading (cost factor changed)
    if (substr($hash, 0, 4) === '$2y$' || substr($hash, 0, 4) === '$2a$') {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    return false;
}

/**
 * Validate password strength
 *
 * @param string $password Plain text password
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePasswordStrength($password) {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Rehash password if needed and update database
 * Call this after successful login to automatically migrate from MD5 to bcrypt
 *
 * @param PDO $dbh Database handle
 * @param string $password Plain text password (just verified)
 * @param string $currentHash Current hash in database
 * @param string $userId User identifier
 * @param string $tableName Table name (tblstudents or tbladmin)
 * @param string $userIdColumn Column name for user ID
 * @param string $passwordColumn Column name for password
 * @return bool True if rehashed, false if not needed
 */
function rehashPasswordIfNeeded($dbh, $password, $currentHash, $userId, $tableName, $userIdColumn = 'id', $passwordColumn = 'Password') {
    if (needsRehash($currentHash)) {
        $newHash = hashPassword($password);

        $sql = "UPDATE {$tableName} SET {$passwordColumn} = :password WHERE {$userIdColumn} = :userId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':password', $newHash, PDO::PARAM_STR);
        $query->bindParam(':userId', $userId, PDO::PARAM_STR);

        return $query->execute();
    }

    return false;
}
?>
