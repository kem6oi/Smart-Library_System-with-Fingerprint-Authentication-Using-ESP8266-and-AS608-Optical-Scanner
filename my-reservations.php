<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['login'])==0)
{
    header('location:index.php');
}
else{
    //book pickup message
   if (isset($_GET['pickup']) && is_numeric($_GET['pickup'])) {
        $_SESSION['msg'] = "Please visit the library with your authentication code to pick up the book!";
         header('location:my-reservations.php');
          exit();
    }
     // book cancellation
    if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
        $reservationId = $_GET['cancel'];
        $sid = $_SESSION['stdid'];
        try {
             // Delete the reservation
            $sql = "DELETE FROM tblreservations WHERE id = :reservationId AND student_id=:sid AND status='pending'";
            $query = $dbh->prepare($sql);
            $query->bindParam(':reservationId', $reservationId, PDO::PARAM_INT);
            $query->bindParam(':sid', $sid, PDO::PARAM_STR);
            $query->execute();


             if ($query->rowCount() > 0) {
               $_SESSION['msg'] = "Reservation cancelled successfully!";
            } else {
               $_SESSION['error'] = "Could not cancel the reservation. Only pending reservations can be cancelled.";
            }

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error canceling reservation: " . $e->getMessage();
        }
       header('location:my-reservations.php');
       exit();
    }
   try {
    $sid=$_SESSION['stdid'];
    $sql = "SELECT r.id, b.BookName, r.reservation_date, r.status
            FROM tblreservations r
            JOIN tblbooks b ON r.book_id = b.id
            WHERE r.student_id = :sid
            ORDER BY r.reservation_date DESC";
       $query = $dbh -> prepare($sql);
        $query-> bindParam(':sid', $sid, PDO::PARAM_STR);
       $query->execute();
    $reservations = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
      echo "Database Error: " . $e->getMessage();
    $reservations = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Library Management System | My Reservations</title>
    <script src="https://cdn.tailwindcss.com"></script>
     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
     <style>
         .font-primary { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="font-primary bg-gray-50">
    <?php include('includes/header.php');?>
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                 <h1 class="text-3xl font-bold text-gray-900 mb-2">My Book Reservations</h1>
            </div>

            <?php if(isset($_SESSION['msg'])): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                 <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                 <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                        </div>
                   </div>
                </div>
            <?php endif; ?>

             <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                     <table class="min-w-full divide-y divide-gray-200">
                          <thead class="bg-gray-50">
                             <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Name</th>
                                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservation Date</th>
                                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <?php if($reservations) {?>
                           <?php foreach ($reservations as $reservation):
                                $statusText = '';
                                 $actionText = '';
                                   switch ($reservation->status) {
                                         case 'pending':
                                              $statusText = 'Pending';
                                              $actionText = '<a href="my-reservations.php?cancel=' . htmlentities($reservation->id) . '" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Cancel</a>';
                                              break;
                                        case 'ready':
                                            $statusText = 'Ready';
                                            $actionText =  '<button onclick="showPickupMessage()" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Pick Up</button>';
                                           break;
                                       case 'collected':
                                         $statusText = 'Collected';
                                        $actionText = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Book checked out</span>';
                                           break;
                                       default:
                                            $statusText = 'Unknown';
                                             $actionText = 'Unknown';
                                          break;
                                   }
                                ?>
                                 <tr>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlentities($reservation->BookName); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlentities($reservation->reservation_date); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlentities($statusText); ?></td>
                                       <td class="px-6 py-4 whitespace-nowrap text-sm"> <?php echo $actionText ?></td>
                                   </tr>
                            <?php endforeach; ?>
                        <?php } else {?>
                              <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No reservations found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                  </table>
                </div>
            </div>
        </div>
    </main>
    <?php include('includes/footer.php');?>

      <script>
          function showPickupMessage() {
              alert("Please visit the library with your authentication code to pick up the book!");
          }
    </script>
</body>
</html>
<?php } ?>