<?php
session_start();
include('includes/config.php');

if (isset($_GET['book_id'])) {
    $bookId = $_GET['book_id'];
    $studentId = $_SESSION['stdid'];

    try {
        $sql = "UPDATE tblreservations SET status = 'cancelled' WHERE book_id = :bookId AND student_id = :studentId AND status != 'collected'";
        $query = $dbh->prepare($sql);
        $query->bindParam(':bookId', $bookId, PDO::PARAM_INT);
        $query->bindParam(':studentId', $studentId, PDO::PARAM_STR);
        $query->execute();

        if ($query->rowCount() > 0) {
            echo "Reservation cancelled successfully.";
        } else {
            echo "Failed to cancel reservation or reservation already collected.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>