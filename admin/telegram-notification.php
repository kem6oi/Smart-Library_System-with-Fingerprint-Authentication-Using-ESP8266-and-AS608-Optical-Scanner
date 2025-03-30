<?php
include('includes/config.php'); // Include database configuration

function sendTelegramMessage($telegramId, $message, $botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $telegramId,
        'text' => $message,
        'parse_mode' => 'HTML',
    ];
    $options = [
        'http' => [
            'method'  => 'POST',
            'content' => json_encode($data),
            'header'  => "Content-Type: application/json\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    return $result;
}

// Get Telegram bot token. You can configure it in your config file.
$telegramBotToken =  $telegramBotToken; // This should be in your config.php file

try {
     $sql = "SELECT r.id, b.BookName, s.FullName, s.telegram_id, r.pickup_time
        FROM tblreservations r
        JOIN tblbooks b ON r.book_id = b.id
        JOIN tblstudents s ON r.student_id = s.StudentID
        WHERE r.status = 'approved'
        AND r.pickup_time > NOW() - INTERVAL 1 MINUTE
    ";

    $query = $dbh->prepare($sql);
    $query->execute();
    $approvedReservations = $query->fetchAll(PDO::FETCH_OBJ);

    foreach ($approvedReservations as $reservation) {
        $bookName = $reservation->BookName;
        $studentName = $reservation->FullName;
        $telegramId = $reservation->telegram_id;
        $pickupTime = new DateTime($reservation->pickup_time);
        $formattedPickupTime = $pickupTime->format('Y-m-d H:i:s');

        $message = "Hello " . $studentName . ", your reservation for book " . $bookName . " has been approved. Please pick it up within 3 hours from  ".$formattedPickupTime;

        if ($telegramId) {
            $telegramResult = sendTelegramMessage($telegramId, $message, $telegramBotToken);
           if($telegramResult)
           {
              // update the pickup_time
             $updateSql = "UPDATE tblreservations SET pickup_time = NULL WHERE id = :reservationId";
            $updateQuery = $dbh->prepare($updateSql);
            $updateQuery->bindParam(':reservationId', $reservation->id, PDO::PARAM_INT);
            $updateQuery->execute();
           }
            if ($telegramResult === false) {
               error_log("Error sending Telegram message to: " . $telegramId);
           }
        }else {
              error_log("Telegram ID not found for: " . $studentName);
        }
    }
} catch (PDOException $e) {
    error_log("Database error in telegram notification script: " . $e->getMessage());
}
?>