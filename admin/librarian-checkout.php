<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])==0)
  {
header('location:index.php');
} else {

    // Function to send Telegram message
    function sendTelegramMessage($chatId, $message, $botToken) {
        $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage?chat_id=" . $chatId . "&text=" . urlencode($message);
        file_get_contents($url); // Consider using curl for better error handling
    }

    // Handle modal content request
   if (isset($_GET['modal']) && $_GET['modal'] === 'true' && isset($_GET['reservationId']) && is_numeric($_GET['reservationId'])) {
      $reservationId = $_GET['reservationId'];
       try {
            echo '<div id="fingerprintVerification">';
            echo '<p>Please ask the student to place their finger on the sensor for verification.</p>';
            echo '<div id="verificationStatus">Waiting for fingerprint...</div>';
            echo '<form id="verifyForm" method="post" action="librarian-checkout.php?checkout=' . htmlentities($reservationId) . '">';
            echo '<input type="hidden" name="verified_fingerprint_id" id="verified_fingerprint_id">';
            echo '<button type="submit" class="btn btn-primary" id="checkoutBtn" style="display:none;">Complete Checkout</button>';
            echo '</form>';
            echo '</div>';
            
            // Add JavaScript for fingerprint verification
            echo '<script>
                function verifyFingerprint() {
                    const esp8266IP = "192.168.43.7"; // Replace with your ESP8266\'s IP address
                    const verifyUrl = `http://${esp8266IP}/verify`;
                    
                    fetch(verifyUrl)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById("verificationStatus").innerHTML = 
                                    "<p style=\'color:green\'>Fingerprint verified successfully!</p>";
                                document.getElementById("verified_fingerprint_id").value = data.fingerprintId;
                                document.getElementById("checkoutBtn").style.display = "block";
                            } else {
                                document.getElementById("verificationStatus").innerHTML = 
                                    "<p style=\'color:red\'>Fingerprint not recognized. Please try again.</p>";
                                setTimeout(verifyFingerprint, 2000); // Try again after 2 seconds
                            }
                        })
                        .catch(error => {
                            document.getElementById("verificationStatus").innerHTML = 
                                "<p style=\'color:red\'>Error connecting to fingerprint sensor. Please try again.</p>";
                            setTimeout(verifyFingerprint, 2000); // Try again after 2 seconds
                        });
                }

                // Start fingerprint verification when modal opens
                verifyFingerprint();
            </script>';
            exit();
           } catch(PDOException $e) {
             echo "Error fetching modal content: " . $e->getMessage();
              exit();
       }
    }

    // Handle book checkout
      if (isset($_GET['checkout']) && is_numeric($_GET['checkout']) && isset($_POST['verified_fingerprint_id'])) {
            $reservationId = $_GET['checkout'];
            $verifiedFingerprintId = $_POST['verified_fingerprint_id'];

            try {
                 // Get student and book info from reservation table
                    $sql = "SELECT r.book_id, r.student_id, s.telegram_chat_id, b.BookName, b.ISBNNumber, s.FullName, s.FingerprintID
                             FROM tblreservations r
                             JOIN tblstudents s ON s.StudentId = r.student_id
                             JOIN tblbooks b ON b.id = r.book_id
                             WHERE r.id = :reservationId";
                     $query = $dbh->prepare($sql);
                    $query->bindParam(':reservationId', $reservationId, PDO::PARAM_INT);
                     $query->execute();
                     $result = $query->fetch(PDO::FETCH_OBJ);
                     
                     // Verify that the fingerprint matches the student
                     if ($result->FingerprintID != $verifiedFingerprintId) {
                         $_SESSION['error'] = "Fingerprint verification failed. Please try again.";
                         header('location:librarian-reservations.php');
                         exit();
                     }

                     $bookId = $result->book_id;
                     $studentId = $result->student_id;
                     $telegramChatId = $result->telegram_chat_id;
                     $bookName = $result->BookName;
                     $bookISBN = $result->ISBNNumber;
                     $studentName = $result->FullName;

                     // Calculate the expected return date (e.g., 1 week from today)
                     $expectedReturnDate = date('Y-m-d', strtotime('+7 days'));

                     // Insert into tblissuedbookdetails
                     $sql = "INSERT INTO tblissuedbookdetails (StudentID, BookId, ReturnDate) VALUES (:studentId, :bookId, :returndate)";
                     $query = $dbh->prepare($sql);
                     $query->bindParam(':studentId', $studentId, PDO::PARAM_STR);
                     $query->bindParam(':bookId', $bookId, PDO::PARAM_INT);
                     $query->bindParam(':returndate', $expectedReturnDate, PDO::PARAM_STR);
                     $query->execute();

                     // Update the status of the record
                     $sql = "UPDATE tblreservations SET status = 'collected' WHERE id = :reservationId";
                     $query = $dbh->prepare($sql);
                     $query->bindParam(':reservationId', $reservationId, PDO::PARAM_INT);
                     $query->execute();

                     if ($query->rowCount() > 0) {
                         $_SESSION['msg'] = "Book checked out successfully.";
                         // Format telegram message for checkout success
                         $botToken = '7304654930:AAF2Q_is81qMPx210n-hz1DkEYVuILPQfKA'; // Replace with your bot token
                         $message = "Dear " . $studentName . ",\n" .
                             "You have successfully checked out book: " . $bookName. ", ISBN: ".$bookISBN."\n".
                             "Please return it by " . $expectedReturnDate;
                         if($telegramChatId){
                             sendTelegramMessage($telegramChatId, $message, $botToken);
                         } else{
                             $_SESSION['error'] = "Telegram chat ID not found for this student.";
                         }
                     } else {
                         $_SESSION['error'] = "Could not checkout this book, please try again.";
                     }
             }  catch (PDOException $e) {
                $_SESSION['error'] = "Error checking out book: " . $e->getMessage();
            }
             header('location:librarian-reservations.php');
              exit();
      } else {
           header('location:librarian-reservations.php');
           exit();
      }
}
?>