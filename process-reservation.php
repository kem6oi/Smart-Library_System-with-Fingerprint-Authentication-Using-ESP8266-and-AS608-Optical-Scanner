<?php
session_start();
include('includes/config.php');

if (isset($_GET['book_id'])) {
    $bookId = $_GET['book_id'];
    $studentId = $_SESSION['stdid'];

    try {
        $sql = "INSERT INTO tblreservations (student_id, book_id, reservation_date, status) VALUES (:studentId, :bookId, NOW(), 'pending')";
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentId', $studentId, PDO::PARAM_STR);
        $query->bindParam(':bookId', $bookId, PDO::PARAM_INT);
        $query->execute();

        if ($query->rowCount() > 0) {
            echo "Book successfully reserved.";
        } else {
            echo "Failed to reserve the book.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>