<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['login'])==0) {   
    header('location:index.php');
} else { 
    if(isset($_GET['del'])) {
        $id=$_GET['del'];
        $sql = "DELETE FROM tblbooks WHERE id=:id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id',$id, PDO::PARAM_STR);
        $query->execute();
        $_SESSION['delmsg'] = "Book deleted successfully";
        header('location:manage-books.php');
    }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Online Library Management System | Manage Issued Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #60a5fa;
            --success-color: #22c55e;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-bg);
        }

        .glassmorphism {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge-pending {
            background: rgba(234, 179, 8, 0.1);
            color: #eab308;
        }

        .table-hover-modern tbody tr {
            transition: all 0.2s ease;
        }

        .table-hover-modern tbody tr:hover {
            transform: translateX(4px);
            background: rgba(var(--primary-color-rgb), 0.03);
        }

        .alert-modern {
            border: none;
            border-left: 4px solid;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .page-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <main class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard.php" class="btn btn-light btn-hover">
                <i class="fas fa-chevron-left me-2"></i>Back to Dashboard
            </a>
            <h1 class="h3 mb-0 text-primary fw-bold">Manage Issued Books</h1>
        </div>

        <?php if(isset($_SESSION['delmsg'])): ?>
            <div class="alert alert-modern alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <div class="alert-icon-wrapper">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="ms-3">
                        <?php echo $_SESSION['delmsg']; unset($_SESSION['delmsg']); ?>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="glassmorphism p-4">
            <div class="table-responsive rounded-3">
                <table class="table table-hover-modern align-middle mb-0">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>#</th>
                            <th>Book Details</th>
                            <th>ISBN</th>
                            <th>Issued Date</th>
                            <th>Return Status</th>
                            <th>Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                            $sid = $_SESSION['stdid'];
                            $sql = "SELECT tblbooks.BookName, tblbooks.ISBNNumber, tblissuedbookdetails.IssuesDate, 
                                   tblissuedbookdetails.ReturnDate, tblissuedbookdetails.id as rid, 
                                   tblissuedbookdetails.fine, tblissuedbookdetails.RetrunStatus 
                                   FROM tblissuedbookdetails 
                                   JOIN tblstudents ON tblstudents.StudentId = tblissuedbookdetails.StudentId 
                                   JOIN tblbooks ON tblbooks.id = tblissuedbookdetails.BookId 
                                   WHERE tblstudents.StudentId = :sid 
                                   ORDER BY tblissuedbookdetails.id DESC";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':sid', $sid, PDO::PARAM_STR);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                            $cnt = 1;
                            
                            if($query->rowCount() > 0) {
                                foreach($results as $result) {               
                        ?>                                                                           
                        <tr>
                            <td class="fw-bold text-primary"><?php echo htmlentities($cnt); ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-40px me-3">
                                        <i class="fas fa-bookmark fs-2 text-secondary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlentities($result->BookName); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlentities($result->ISBNNumber); ?></span>
                            </td>
                            <td class="text-muted">
                                <i class="fas fa-calendar-day me-2"></i>
                                <?php echo date('M j, Y', strtotime($result->IssuesDate)); ?>
                            </td>
                            <td>
                            <?php if($result->RetrunStatus == 1): ?>
                                    <span class="status-badge bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-check-circle"></i>
                                        Returned <!-- Keep typo in display -->
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge badge-pending">
                                        <i class="fas fa-clock"></i>
                                        Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="d-inline-flex align-items-center">
                                    <span class="fw-bold <?php echo ($result->fine > 0) ? 'text-danger' : 'text-success'; ?>">
                                        $<?php echo htmlentities($result->fine); ?>
                                    </span>
                                    <?php if($result->fine > 0): ?>
                                        <i class="fas fa-exclamation-triangle ms-2 text-danger"></i>
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
                                $cnt++;
                                }
                            } else {
                        ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state">
                                    <div class="empty-state-icon bg-primary bg-opacity-10">
                                        <i class="fas fa-inbox fa-3x text-primary"></i>
                                    </div>
                                    <h3 class="mt-4 text-muted">No Issued Books Found</h3>
                                    <p class="text-muted">You haven't issued any books yet.</p>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>                                      
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php } ?>