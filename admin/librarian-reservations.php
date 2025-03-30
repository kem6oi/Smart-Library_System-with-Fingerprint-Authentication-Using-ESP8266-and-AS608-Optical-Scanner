<?php
session_start();
error_reporting(0);
include('includes/config.php');

$pendingReservations = [];

if(strlen($_SESSION['alogin'])==0) {
    header('location:index.php');
    exit();
}

function sendTelegramMessage($chatId, $message, $botToken) {
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage?chat_id=" . $chatId . "&text=" . urlencode($message);
    file_get_contents($url);
}

if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $reservationId = $_GET['approve'];
    try {
        $sql = "SELECT s.telegram_chat_id, s.FullName, b.BookName, b.ISBNNumber, s.StudentId, b.id as BookId
                FROM tblreservations r
                JOIN tblstudents s ON s.StudentId = r.student_id
                JOIN tblbooks b ON b.id = r.book_id
                WHERE r.id = :reservationId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':reservationId', $reservationId, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_OBJ);

        if ($result && $result->telegram_chat_id) {
            $telegramChatId = $result->telegram_chat_id;
            $studentName = $result->FullName;
            $bookName = $result->BookName;
            $bookISBN = $result->ISBNNumber;
            $studentId = $result->StudentId;
            $bookId = $result->BookId;

            $authCode = rand(100000, 999999);

            $sql = "INSERT INTO auth_codes (code, student_id, book_id) VALUES (:code, :student_id, :book_id)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':code', $authCode, PDO::PARAM_STR);
            $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
            $query->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            $query->execute();

            $botToken = '7304654930:AAF2Q_is81qMPx210n-hz1DkEYVuILPQfKA';
            $message = "Dear " . $studentName . ",\n" .
                      "Your Reserved book, ISBN: " . $bookISBN. " ".$bookName." is ready to be checked out.\n" .
                      "Use this code within the next 12 hours to checkout: " . $authCode;

            sendTelegramMessage($telegramChatId, $message, $botToken);
            
            $sql = "UPDATE tblreservations SET status = 'ready', pickup_date = NOW() WHERE id = :reservationId AND status = 'pending'";
            $query = $dbh->prepare($sql);
            $query->bindParam(':reservationId', $reservationId, PDO::PARAM_INT);
            $query->execute();

            $_SESSION['msg'] = $query->rowCount() > 0 
                ? "Reservation approved successfully. A telegram message has been sent to student"
                : "Could not approve this reservation, please try again.";
        } else {
            $_SESSION['error'] = "Telegram chat ID not found for this student.";
        }
    } catch (PDOException $e) {
        error_log("Error approving reservation: " . $e->getMessage());
        $_SESSION['error'] = "Error processing approval.";
    }
    header('location:librarian-reservations.php');
    exit();
}

try {
    $sql = "SELECT r.id, b.BookName, s.FullName as StudentName, r.reservation_date, r.status
            FROM tblreservations r
            JOIN tblbooks b ON r.book_id = b.id
            JOIN tblstudents s ON r.student_id = s.StudentID
            WHERE r.status = 'pending' OR r.status='ready'
            ORDER BY r.reservation_date DESC";
    $query = $dbh->prepare($sql);
    $query->execute();
    $pendingReservations = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading reservations.";
    $pendingReservations = [];
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Library Reservations Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2A5C82;
            --secondary-color: #5BA4E6;
            --success-color: #28A745;
            --info-color: #17A2B8;
            --light-bg: #F8F9FA;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
        }

        .glassmorphism {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }

        .table-hover-modern tbody tr:hover {
            background-color: rgba(91, 164, 230, 0.05);
            transform: translateX(5px);
            transition: all 0.3s ease;
        }

        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-pending {
            background-color: #FFC10720;
            color: #FFC107;
        }

        .badge-ready {
            background-color: #17A2B820;
            color: #17A2B8;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-approve {
            background: var(--success-color);
            color: white !important;
        }

        .btn-checkout {
            background: var(--secondary-color);
            color: white !important;
        }

        .modal-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
        }

        .alert-modern {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    
    <?php include('includes/header.php'); ?>

    <main class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
            <h2 class="fw-bold text-primary mb-0">📚 Manage Reservations</h2>
        </div>

        <?php if(isset($_SESSION['msg'])): ?>
            <div class="alert alert-modern alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-modern alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="glassmorphism p-4">
            <div class="table-responsive">
                <table class="table table-hover-modern align-middle">
                    <thead class="text-primary">
                        <tr>
                            <th scope="col">Book</th>
                            <th scope="col">Student</th>
                            <th scope="col">Reservation Date</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pendingReservations)): ?>
                            <?php foreach ($pendingReservations as $reservation): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-book me-3 text-secondary"></i>
                                            <div><?php echo htmlentities($reservation->BookName); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user me-3 text-secondary"></i>
                                            <div><?php echo htmlentities($reservation->StudentName); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($reservation->reservation_date)); ?></td>
                                    <td>
                                        <span class="status-badge badge-<?php echo strtolower($reservation->status); ?>">
                                            <?php echo htmlentities($reservation->status); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <?php if($reservation->status === 'pending'): ?>
                                                <a href="librarian-reservations.php?approve=<?php echo $reservation->id; ?>" 
                                                   class="action-btn btn-approve">
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </a>
                                            <?php else: ?>
                                                <button onclick="openCheckoutModal(<?php echo $reservation->id; ?>)" 
                                                        class="action-btn btn-checkout">
                                                    <i class="fas fa-arrow-right me-1"></i>Checkout
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-3"></i><br>
                                    No pending reservations found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-glass border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-primary" id="checkoutModalLabel">Complete Checkout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openCheckoutModal(reservationId) {
            fetch('librarian-checkout.php?modal=true&reservationId=' + reservationId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('checkoutModal')).show();
                })
                .catch(error => console.error('Error:', error));
        }
    </script>

    <?php include('includes/footer.php'); ?>
</body>
</html>