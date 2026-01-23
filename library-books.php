<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('includes/config.php');

$bookDetails = [];
$categories = [];
$selectedCategory = '';
$studentId = $_SESSION['stdid'];

try {
    $sql_categories = "SELECT id, CategoryName FROM tblcategory";
    $query_categories = $dbh->prepare($sql_categories);
    $query_categories->execute();
    $categories = $query_categories->fetchAll(PDO::FETCH_OBJ);

    if (!isset($_GET['category']) || empty($_GET['category']) || isset($_GET['fetch_books']) ) {
        
        $sql_books = "SELECT b.id, b.BookName, a.AuthorName, b.ISBNNumber
                       FROM tblbooks b
                       JOIN tblauthors a ON b.AuthorId = a.id";
         if(isset($_GET['category']) && !empty($_GET['category'])) {
             $selectedCategory = $_GET['category'];
             $sql_books .= " WHERE b.CatId = :catId";
         }
        $query_books = $dbh->prepare($sql_books);
         if(isset($_GET['category']) && !empty($_GET['category'])) {
              $query_books->bindParam(':catId', $_GET['category'], PDO::PARAM_INT);
          }
        $query_books->execute();
        $bookDetails = $query_books->fetchAll(PDO::FETCH_OBJ);

        if(isset($_GET['fetch_books'])){
            ob_start();
            ?>
            <?php if (!empty($bookDetails)): ?>
                 <?php foreach ($bookDetails as $book): ?>
                    <?php
                        $isBookIssued = false;
                        $checkQuery = $dbh->prepare("SELECT 1 FROM tblissuedbookdetails WHERE bookId = ? AND StudentID = ? AND RetrunStatus = 0");
                        $checkQuery->execute([$book->id, $studentId]);
                        if ($checkQuery->rowCount() > 0) $isBookIssued = true;

                        $isBookReserved = false;
                        $checkQuery = $dbh->prepare("SELECT 1 FROM tblreservations WHERE book_id = ? AND student_id = ? AND status IN ('pending', 'ready')");
                        $checkQuery->execute([$book->id, $studentId]);
                        if ($checkQuery->rowCount() > 0) $isBookReserved = true;
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlentities($book->BookName) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlentities($book->AuthorName) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlentities($book->ISBNNumber) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if (!$isBookReserved && !$isBookIssued): ?>
                            <button onclick="reserveBook(<?= $book->id ?>)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Reserve Now
                            </button>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                <?= $isBookIssued ? 'Checked Out' : 'Reserved' ?>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                  <?php endforeach; ?>
            <?php else: ?>
                   <tr>
                         <td colspan="4" class="px-6 py-4 text-center">
                             <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                             </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No books found</h3>
                             <p class="mt-1 text-sm text-gray-500">Try selecting a different category</p>
                        </td>
                    </tr>
            <?php endif; ?>
            <?php
            $output = ob_get_contents();
            ob_end_clean();
            echo $output;
            exit;
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Library Management System | Library Books</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .font-primary { font-family: 'Inter', sans-serif; }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="font-primary bg-gray-50">
    <!-- MENU SECTION START -->
    <?php include('includes/header.php'); ?>
    <!-- MENU SECTION END -->
    
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Explore Our Collection</h1>
                <p class="text-gray-600">Browse and reserve books from our extensive library</p>
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
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Category</label>
                            <select id="category" onchange="fetchBooks(this.value)" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlentities($category->id) ?>" <?= $selectedCategory == $category->id ? 'selected' : '' ?>>
                                    <?= htmlentities($category->CategoryName) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ISBN</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookTableBody" class="bg-white divide-y divide-gray-200">
                             <?php if (empty($bookDetails) && !isset($_GET['fetch_books'])): ?>
                                   <tr>
                                        <td colspan="4" class="px-6 py-4 text-center">
                                             <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                                </svg>
                                               <h3 class="mt-2 text-sm font-medium text-gray-900">No books found</h3>
                                               <p class="mt-1 text-sm text-gray-500">Try selecting a different category</p>
                                        </td>
                                    </tr>
                                <?php else:?>
                                    <?php if (!isset($_GET['fetch_books'])): ?>
                                            <?php foreach ($bookDetails as $book): ?>
                                            <?php
                                                $isBookIssued = false;
                                                $checkQuery = $dbh->prepare("SELECT 1 FROM tblissuedbookdetails WHERE bookId = ? AND StudentID = ? AND RetrunStatus = 0");
                                                $checkQuery->execute([$book->id, $studentId]);
                                                if ($checkQuery->rowCount() > 0) $isBookIssued = true;

                                                $isBookReserved = false;
                                                $checkQuery = $dbh->prepare("SELECT 1 FROM tblreservations WHERE book_id = ? AND student_id = ? AND status IN ('pending', 'ready')");
                                                $checkQuery->execute([$book->id, $studentId]);
                                                if ($checkQuery->rowCount() > 0) $isBookReserved = true;
                                            ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlentities($book->BookName) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlentities($book->AuthorName) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlentities($book->ISBNNumber) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <?php if (!$isBookReserved && !$isBookIssued): ?>
                                                    <button onclick="reserveBook(<?= $book->id ?>)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                        Reserve Now
                                                    </button>
                                                    <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                        <?= $isBookIssued ? 'Checked Out' : 'Reserved' ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include('includes/footer.php'); ?>

    <script>
        function fetchBooks(category) {
            const tableBody = document.getElementById('bookTableBody');
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center space-x-2 text-gray-500">
                            <div class="animate-pulse h-4 w-4 bg-indigo-600 rounded-full"></div>
                            <span>Loading books...</span>
                        </div>
                    </td>
                </tr>
            `;

            const xhr = new XMLHttpRequest();
            xhr.open('GET', `?category=${category}&fetch_books=true`);
            xhr.onload = () => {
                if (xhr.status === 200) {
                    tableBody.innerHTML = xhr.responseText;
                } else {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-red-600">
                                Error loading books. Please try again.
                            </td>
                        </tr>
                    `;
                }
            };
            xhr.send();
        }

        function reserveBook(bookId) {
            if (!confirm('Are you sure you want to reserve this book?')) return;
            
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `process-reservation.php?book_id=${bookId}`);
            xhr.onload = () => {
                if (xhr.status === 200) {
                    fetchBooks(document.getElementById('category').value);
                    const response = JSON.parse(xhr.responseText);
                    alert(response.message);
                } else {
                    alert('Error processing reservation. Please try again.');
                }
            };
            xhr.send();
        }
    </script>
</body>
</html>