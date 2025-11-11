<?php
session_start();
error_reporting(0);
include('../includes/config.php');
include('../includes/export.php');
include('../includes/rbac.php');

// Check authentication
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit();
}

// Check permission
requirePermission($dbh, 'reports_export', 'dashboard.php');

// Handle export requests
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    $format = $_GET['format'] ?? 'csv';

    switch ($exportType) {
        case 'issued_books':
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $status = $_GET['status'] ?? 'all';

            if ($format === 'pdf') {
                exportIssuedBooksPDF($dbh, $startDate, $endDate, $status);
            } else {
                exportIssuedBooksCSV($dbh, $startDate, $endDate, $status);
            }
            break;

        case 'books':
            $categoryId = $_GET['category_id'] ?? null;
            exportBooksCSV($dbh, $categoryId);
            break;

        case 'students':
            $status = $_GET['status'] ?? null;
            exportStudentsCSV($dbh, $status);
            break;

        case 'overdue':
            exportOverdueBooksCSV($dbh);
            break;

        case 'audit':
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $userType = $_GET['user_type'] ?? null;
            $category = $_GET['category'] ?? null;
            exportAuditLogsCSV($dbh, $startDate, $endDate, $userType, $category);
            break;
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Export Reports | Smart Library</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        .export-card {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .export-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .export-card h4 {
            color: #337ab7;
            margin-bottom: 15px;
        }
        .export-card .description {
            color: #666;
            margin-bottom: 20px;
        }
        .export-options {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .btn-export {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .icon-export {
            font-size: 3em;
            color: #ddd;
            float: right;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">
                        <i class="fa fa-download"></i> Export Reports
                    </h4>
                </div>
            </div>

            <!-- Issued Books Report -->
            <div class="row">
                <div class="col-md-12">
                    <div class="export-card">
                        <i class="fa fa-book icon-export"></i>
                        <h4>Issued Books Report</h4>
                        <p class="description">
                            Export a comprehensive list of all book issues including student details, issue dates, and return status.
                        </p>

                        <div class="export-options">
                            <form method="get" action="" class="form-inline">
                                <input type="hidden" name="export" value="issued_books">

                                <div class="form-group">
                                    <label>Start Date:</label>
                                    <input type="date" name="start_date" class="form-control" style="margin: 0 10px;">
                                </div>

                                <div class="form-group">
                                    <label>End Date:</label>
                                    <input type="date" name="end_date" class="form-control" style="margin: 0 10px;">
                                </div>

                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="status" class="form-control" style="margin: 0 10px;">
                                        <option value="all">All</option>
                                        <option value="issued">Currently Issued</option>
                                        <option value="returned">Returned</option>
                                        <option value="overdue">Overdue</option>
                                    </select>
                                </div>

                                <button type="submit" name="format" value="csv" class="btn btn-success btn-export">
                                    <i class="fa fa-file-excel-o"></i> Export to CSV
                                </button>
                                <button type="submit" name="format" value="pdf" class="btn btn-danger btn-export">
                                    <i class="fa fa-file-pdf-o"></i> Export to PDF
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Books List Report -->
            <div class="row">
                <div class="col-md-12">
                    <div class="export-card">
                        <i class="fa fa-list icon-export"></i>
                        <h4>Books List Report</h4>
                        <p class="description">
                            Export complete list of all books in the library with details like author, category, price, and borrowing statistics.
                        </p>

                        <div class="export-options">
                            <form method="get" action="" class="form-inline">
                                <input type="hidden" name="export" value="books">
                                <input type="hidden" name="format" value="csv">

                                <div class="form-group">
                                    <label>Category:</label>
                                    <select name="category_id" class="form-control" style="margin: 0 10px;">
                                        <option value="">All Categories</option>
                                        <?php
                                        $sql = "SELECT * FROM tblcategory ORDER BY CategoryName";
                                        $query = $dbh->prepare($sql);
                                        $query->execute();
                                        $categories = $query->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($categories as $cat) {
                                            echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['CategoryName']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success btn-export">
                                    <i class="fa fa-file-excel-o"></i> Export to CSV
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students List Report -->
            <div class="row">
                <div class="col-md-12">
                    <div class="export-card">
                        <i class="fa fa-users icon-export"></i>
                        <h4>Students List Report</h4>
                        <p class="description">
                            Export list of all registered students with their contact information and borrowing statistics.
                        </p>

                        <div class="export-options">
                            <form method="get" action="" class="form-inline">
                                <input type="hidden" name="export" value="students">
                                <input type="hidden" name="format" value="csv">

                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="status" class="form-control" style="margin: 0 10px;">
                                        <option value="">All Students</option>
                                        <option value="1">Active Only</option>
                                        <option value="0">Blocked Only</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success btn-export">
                                    <i class="fa fa-file-excel-o"></i> Export to CSV
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Books Report -->
            <div class="row">
                <div class="col-md-12">
                    <div class="export-card">
                        <i class="fa fa-exclamation-triangle icon-export"></i>
                        <h4>Overdue Books Report</h4>
                        <p class="description">
                            Export list of all currently overdue books with student details, days overdue, and calculated fines.
                        </p>

                        <div class="export-options">
                            <a href="?export=overdue&format=csv" class="btn btn-success btn-export">
                                <i class="fa fa-file-excel-o"></i> Export to CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Logs Report -->
            <div class="row">
                <div class="col-md-12">
                    <div class="export-card">
                        <i class="fa fa-shield icon-export"></i>
                        <h4>Audit Logs Report</h4>
                        <p class="description">
                            Export system audit logs for security and compliance purposes. Includes all user actions with timestamps and IP addresses.
                        </p>

                        <div class="export-options">
                            <form method="get" action="" class="form-inline">
                                <input type="hidden" name="export" value="audit">
                                <input type="hidden" name="format" value="csv">

                                <div class="form-group">
                                    <label>Start Date:</label>
                                    <input type="date" name="start_date" class="form-control" style="margin: 0 10px;">
                                </div>

                                <div class="form-group">
                                    <label>End Date:</label>
                                    <input type="date" name="end_date" class="form-control" style="margin: 0 10px;">
                                </div>

                                <div class="form-group">
                                    <label>User Type:</label>
                                    <select name="user_type" class="form-control" style="margin: 0 10px;">
                                        <option value="">All</option>
                                        <option value="admin">Admin</option>
                                        <option value="student">Student</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Category:</label>
                                    <select name="category" class="form-control" style="margin: 0 10px;">
                                        <option value="">All</option>
                                        <option value="authentication">Authentication</option>
                                        <option value="circulation">Circulation</option>
                                        <option value="books">Books</option>
                                        <option value="users">Users</option>
                                        <option value="security">Security</option>
                                        <option value="system">System</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success btn-export">
                                    <i class="fa fa-file-excel-o"></i> Export to CSV (Max 5000 records)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Report -->
            <div class="row">
                <div class="col-md-12">
                    <div class="export-card">
                        <i class="fa fa-line-chart icon-export"></i>
                        <h4>Analytics Report</h4>
                        <p class="description">
                            Export daily analytics data for the specified date range.
                        </p>

                        <div class="export-options">
                            <a href="analytics.php?export=csv" class="btn btn-success btn-export">
                                <i class="fa fa-file-excel-o"></i> Export Analytics to CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include('includes/footer.php'); ?>

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
