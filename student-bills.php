<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['login'])==0)
  { 
header('location:index.php');
}
else{
    
    if (isset($_SESSION['stdid'])) {
        $studentId = $_SESSION['stdid'];
    } elseif (isset($_GET['student_id'])) {
        $studentId = $_GET['student_id'];
    } else {
        
        echo "Student ID not provided.";
        exit();
    }

    
    $sql = "SELECT FullName FROM tblstudents WHERE StudentId = :sid";
    $query = $dbh -> prepare($sql);
    $query->bindParam(':sid',$studentId,PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);
    $studentName = $result ? $result->FullName : "User";


    // Fetch billing details
     $sql = "SELECT b.BookName, i.IssuesDate, i.ReturnDate, i.fine
             FROM tblissuedbookdetails i
             JOIN tblbooks b ON b.id = i.BookId
             WHERE i.StudentID = :sid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':sid', $studentId, PDO::PARAM_STR);
    $query->execute();
    $billingDetails = $query->fetchAll(PDO::FETCH_OBJ);

   // Calculate total fine
    $totalFine = 0;
    if ($billingDetails) {
        foreach ($billingDetails as $bill) {
            $totalFine += $bill->fine;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Library Management System | Student Bills</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .font-primary { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="font-primary bg-gray-50">
    <!-- MENU SECTION START -->
    <?php include('includes/header.php'); ?>
    <!-- MENU SECTION END -->
    
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Billing Details for <?php echo htmlentities($studentName);?></h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-800">Billing Details</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fine (USD)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($billingDetails): ?>
                                <?php foreach ($billingDetails as $bill): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlentities($bill->BookName); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlentities($bill->IssuesDate); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlentities($bill->ReturnDate); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlentities($bill->fine); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No billing information found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 text-right">
                    <p class="text-lg font-semibold text-gray-800">
                        Total Fine: USD <?php echo htmlentities(number_format($totalFine, 2)); ?>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include('includes/footer.php'); ?>
</body>
</html>
<?php } ?>