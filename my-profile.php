<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['login'])==0)
{
    header('location:index.php');
}
else {
    $sid = $_SESSION['stdid'];
    $msg = "";
    try {

        $sql = "SELECT StudentId, FullName, EmailId, telegram_chat_id FROM tblstudents WHERE StudentId = :sid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $sid, PDO::PARAM_STR);
        $query->execute();
        $student = $query->fetch(PDO::FETCH_OBJ);

        $sql = "SELECT COUNT(*) AS totalBorrowed FROM tblissuedbookdetails WHERE StudentID = :sid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $sid, PDO::PARAM_STR);
        $query->execute();
        $totalBorrowed = $query->fetch(PDO::FETCH_OBJ)->totalBorrowed;

        //Modified query to get total fine
        $sql = "SELECT SUM(fine) AS totalFines FROM tblissuedbookdetails WHERE StudentID = :sid AND fine IS NOT NULL AND RetrunStatus = 0";
         $query = $dbh->prepare($sql);
         $query->bindParam(':sid', $sid, PDO::PARAM_STR);
         $query->execute();
         $totalFines = $query->fetch(PDO::FETCH_OBJ)->totalFines;

        $sql = "SELECT COUNT(*) AS totalReservations FROM tblreservations WHERE student_id = :sid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $sid, PDO::PARAM_STR);
        $query->execute();
        $totalReservations = $query->fetch(PDO::FETCH_OBJ)->totalReservations;

        $sql = "SELECT COUNT(*) AS pendingReservations FROM tblreservations WHERE student_id = :sid AND status = 'pending'";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $sid, PDO::PARAM_STR);
        $query->execute();
        $pendingReservations = $query->fetch(PDO::FETCH_OBJ)->pendingReservations;

         $sql = "SELECT b.BookName, i.ReturnDate, i.IssuesDate
                FROM tblissuedbookdetails i
                JOIN tblbooks b ON b.id = i.BookId
                WHERE i.StudentID = :sid AND i.RetrunStatus=0";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $sid, PDO::PARAM_STR);
        $query->execute();
        $borrowedBooks = $query->fetchAll(PDO::FETCH_OBJ);

        if (isset($_POST['save'])) {
            $fullName = $_POST['fullName'];
            $emailId = $_POST['emailId'];
            $telegramChatId = $_POST['telegramChatId'];
            $sql = "UPDATE tblstudents SET FullName = :fullName, EmailId = :emailId, telegram_chat_id = :telegramChatId WHERE StudentId = :sid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':sid', $sid, PDO::PARAM_STR);
            $query->bindParam(':fullName', $fullName, PDO::PARAM_STR);
            $query->bindParam(':emailId', $emailId, PDO::PARAM_STR);
            $query->bindParam(':telegramChatId', $telegramChatId, PDO::PARAM_STR);
            $query->execute();
            $msg = "Profile updated successfully!";

            $sql = "SELECT StudentId, FullName, EmailId, telegram_chat_id FROM tblstudents WHERE StudentId = :sid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':sid', $sid, PDO::PARAM_STR);
            $query->execute();
            $student = $query->fetch(PDO::FETCH_OBJ);
        }

    } catch (PDOException $e) {
        echo "<p class='text-red-500'>Database Error: " . $e->getMessage() . "</p>";
        $student = null;
        $totalBorrowed = 0;
        $totalFines = 0;
        $totalReservations = 0;
        $pendingReservations = 0;
        $borrowedBooks = [];
    }

    ?>
    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Online Library Management System | My Profile</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <script>
            function enableEdit() {
                document.getElementById('fullName').readOnly = false;
                document.getElementById('emailId').readOnly = false;
                document.getElementById('telegramChatId').readOnly = false;

                document.getElementById('editButton').classList.add('hidden');
                document.getElementById('saveButton').classList.remove('hidden');
            }
        </script>
        <style>
            body { font-family: 'Inter', sans-serif; }
            .shadow-card { box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24); }
        </style>
    </head>
    <body class="bg-gray-50">
    <?php include('includes/header.php');?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
            <div class="flex items-center space-x-4">
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">Student</span>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle h-5 w-5 text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo htmlentities($msg); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- User Info Card -->
            <div class="bg-white rounded-xl shadow-card p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Personal Information</h2>
                    <button id="editButton" onclick="enableEdit()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-edit mr-2"></i> Edit Profile
                    </button>
                </div>

                <?php if ($student) : ?>
                    <form method="post">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
                                <p class="text-gray-900 font-medium"><?php echo htmlentities($student->StudentId); ?></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" id="fullName" name="fullName"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       value="<?php echo htmlentities($student->FullName); ?>" readonly>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" id="emailId" name="emailId"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       value="<?php echo htmlentities($student->EmailId); ?>" readonly>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telegram Chat ID</label>
                                <input type="text" id="telegramChatId" name="telegramChatId"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       value="<?php echo htmlentities($student->telegram_chat_id); ?>" readonly>
                            </div>
                        </div>

                        <button type="submit" id="saveButton" name="save"
                                class="hidden mt-6 w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </form>
                <?php else: ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle h-5 w-5 text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">Could not fetch user information.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Stats Card -->
            <div class="bg-white rounded-xl shadow-card p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Library Activity</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-indigo-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-book-open text-indigo-600 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Total Borrowed</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo htmlentities($totalBorrowed); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Replaced Current Loans with total fine -->
                     <div class="bg-red-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-money-bill-wave text-red-600 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-600">Total Fines</p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo htmlentities($totalFines); ?></p>
                                </div>
                            </div>
                        </div>

                    <div class="bg-purple-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-bookmark text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Total Reservations</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo htmlentities($totalReservations); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-pink-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-hourglass-half text-pink-600 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Pending Reservations</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo htmlentities($pendingReservations); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Borrowed Books Section -->
        <div class="bg-white rounded-xl shadow-card p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Currently Borrowed Books</h2>
            <?php if($borrowedBooks && count($borrowedBooks) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($borrowedBooks as $book): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlentities($book->BookName); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlentities($book->IssuesDate); ?></td>
                                 <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlentities($book->ReturnDate); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-book-open text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">No books currently borrowed</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
    </body>
    </html>
    <?php
}
?>