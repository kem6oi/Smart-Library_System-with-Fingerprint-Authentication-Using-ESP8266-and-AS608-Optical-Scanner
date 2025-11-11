<?php
session_start();
error_reporting(0);
include('includes/config.php');

// Check if user is logged in
if (strlen($_SESSION['login']) == 0 && strlen($_SESSION['alogin']) == 0) {
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

// Determine user ID and type
$userId = $_SESSION['stdid'] ?? $_SESSION['alogin'] ?? null;
$userType = isset($_SESSION['alogin']) ? 'admin' : 'student';

if (!$userId) {
    die(json_encode(['success' => false, 'message' => 'Invalid user']));
}

// Get POST data
$preferenceKey = $_POST['preference_key'] ?? '';
$preferenceValue = $_POST['preference_value'] ?? '';

if (empty($preferenceKey)) {
    die(json_encode(['success' => false, 'message' => 'Invalid preference key']));
}

try {
    // Check if preference exists
    $sql = "SELECT id FROM tbluser_preferences
            WHERE user_id = :user_id AND user_type = :user_type AND preference_key = :key";
    $query = $dbh->prepare($sql);
    $query->bindParam(':user_id', $userId, PDO::PARAM_STR);
    $query->bindParam(':user_type', $userType, PDO::PARAM_STR);
    $query->bindParam(':key', $preferenceKey, PDO::PARAM_STR);
    $query->execute();
    $existing = $query->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing preference
        $sql = "UPDATE tbluser_preferences
                SET preference_value = :value, updated_at = NOW()
                WHERE id = :id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':value', $preferenceValue, PDO::PARAM_STR);
        $query->bindParam(':id', $existing['id'], PDO::PARAM_INT);
        $query->execute();
    } else {
        // Insert new preference
        $sql = "INSERT INTO tbluser_preferences (user_id, user_type, preference_key, preference_value)
                VALUES (:user_id, :user_type, :key, :value)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $query->bindParam(':user_type', $userType, PDO::PARAM_STR);
        $query->bindParam(':key', $preferenceKey, PDO::PARAM_STR);
        $query->bindParam(':value', $preferenceValue, PDO::PARAM_STR);
        $query->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Preference saved']);

} catch (PDOException $e) {
    log_error("Error saving user preference: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
