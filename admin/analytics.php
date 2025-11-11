<?php
session_start();
error_reporting(0);
include('../includes/config.php');
include('../includes/analytics.php');
include('../includes/rbac.php');

// Check authentication
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit();
}

// Check permission
requirePermission($dbh, 'analytics_view', 'dashboard.php');

// Get analytics data
$dashboardData = getAnalyticsDashboard($dbh);
$todayStats = $dashboardData['today'];
$borrowingTrend = $dashboardData['borrowing_trend'];
$popularBooks = $dashboardData['popular_books'];
$categoryStats = $dashboardData['category_stats'];
$userActivity = $dashboardData['user_activity'];
$monthlyStats = $dashboardData['monthly'];

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');

    $csv = exportAnalyticsCSV($dbh, $startDate, $endDate);

    if ($csv) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="analytics_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit();
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Analytics Dashboard | Smart Library</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .stats-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stats-card .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #337ab7;
            margin: 10px 0;
        }
        .stats-card .stat-label {
            font-size: 1em;
            color: #666;
            text-transform: uppercase;
        }
        .stats-card .stat-icon {
            font-size: 3em;
            color: #ddd;
            float: right;
        }
        .chart-container {
            position: relative;
            height: 400px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .table-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #337ab7;
        }
        .export-btn {
            float: right;
            margin-top: 10px;
        }
        .stat-card-green .stat-value { color: #5cb85c; }
        .stat-card-orange .stat-value { color: #f0ad4e; }
        .stat-card-red .stat-value { color: #d9534f; }
        .stat-card-purple .stat-value { color: #9b59b6; }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="page-header">
                        <i class="fa fa-bar-chart"></i> Analytics Dashboard
                        <a href="?export=csv" class="btn btn-success btn-sm export-btn">
                            <i class="fa fa-download"></i> Export CSV
                        </a>
                    </h4>
                </div>
            </div>

            <!-- Today's Statistics -->
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <i class="fa fa-book stat-icon"></i>
                        <div class="stat-value"><?php echo $todayStats['books_issued']; ?></div>
                        <div class="stat-label">Books Issued Today</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card stat-card-green">
                        <i class="fa fa-check-circle stat-icon"></i>
                        <div class="stat-value"><?php echo $todayStats['books_returned']; ?></div>
                        <div class="stat-label">Books Returned Today</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card stat-card-red">
                        <i class="fa fa-exclamation-triangle stat-icon"></i>
                        <div class="stat-value"><?php echo $todayStats['total_overdue_books']; ?></div>
                        <div class="stat-label">Overdue Books</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card stat-card-purple">
                        <i class="fa fa-users stat-icon"></i>
                        <div class="stat-value"><?php echo $todayStats['total_active_users']; ?></div>
                        <div class="stat-label">Active Users</div>
                    </div>
                </div>
            </div>

            <!-- Monthly Statistics -->
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <div class="stat-value"><?php echo number_format($monthlyStats['total_issued'] ?? 0); ?></div>
                        <div class="stat-label">Monthly Issues</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card stat-card-green">
                        <div class="stat-value"><?php echo number_format($monthlyStats['total_returned'] ?? 0); ?></div>
                        <div class="stat-label">Monthly Returns</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card stat-card-orange">
                        <div class="stat-value">$<?php echo number_format($monthlyStats['total_fines'] ?? 0, 2); ?></div>
                        <div class="stat-label">Fines Collected</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card stat-card-purple">
                        <div class="stat-value"><?php echo number_format($monthlyStats['total_registrations'] ?? 0); ?></div>
                        <div class="stat-label">New Registrations</div>
                    </div>
                </div>
            </div>

            <!-- Borrowing Trend Chart -->
            <div class="row">
                <div class="col-md-12">
                    <div class="chart-container">
                        <h4><i class="fa fa-line-chart"></i> Weekly Borrowing Trend</h4>
                        <canvas id="borrowingTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Popular Books & Category Stats -->
            <div class="row">
                <!-- Popular Books -->
                <div class="col-md-6">
                    <div class="table-container">
                        <h4><i class="fa fa-star"></i> Most Popular Books</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Book Name</th>
                                        <th>Author</th>
                                        <th>Borrows</th>
                                        <th>Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($popularBooks)) {
                                        $count = 1;
                                        foreach ($popularBooks as $book) {
                                            $rating = number_format($book['avg_rating'], 1);
                                            $stars = str_repeat('★', floor($book['avg_rating']));
                                            ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td><?php echo htmlspecialchars($book['BookName']); ?></td>
                                                <td><?php echo htmlspecialchars($book['AuthorName']); ?></td>
                                                <td>
                                                    <span class="badge badge-primary"><?php echo $book['total_borrows']; ?></span>
                                                </td>
                                                <td>
                                                    <span style="color: #f39c12;"><?php echo $stars; ?></span>
                                                    <?php echo $rating; ?>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">No data available</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Category Statistics -->
                <div class="col-md-6">
                    <div class="table-container">
                        <h4><i class="fa fa-list"></i> Category Statistics</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Books</th>
                                        <th>Borrows</th>
                                        <th>Avg Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($categoryStats)) {
                                        foreach ($categoryStats as $category) {
                                            $rating = number_format($category['avg_rating'], 1);
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($category['CategoryName']); ?></td>
                                                <td><?php echo $category['total_books']; ?></td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo $category['total_borrows']; ?></span>
                                                </td>
                                                <td><?php echo $rating; ?></td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center">No data available</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Activity Statistics -->
            <div class="row">
                <!-- Top Borrowers -->
                <div class="col-md-6">
                    <div class="table-container">
                        <h4><i class="fa fa-trophy"></i> Top Borrowers</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Total Borrows</th>
                                        <th>Current</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($userActivity['top_borrowers'])) {
                                        $count = 1;
                                        foreach ($userActivity['top_borrowers'] as $user) {
                                            ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td><?php echo htmlspecialchars($user['FullName']); ?></td>
                                                <td><?php echo htmlspecialchars($user['EmailId']); ?></td>
                                                <td>
                                                    <span class="badge badge-success"><?php echo $user['total_borrows']; ?></span>
                                                </td>
                                                <td><?php echo $user['current_borrows']; ?></td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">No data available</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Users with Overdue Books -->
                <div class="col-md-6">
                    <div class="table-container">
                        <h4><i class="fa fa-clock-o"></i> Users with Overdue Books</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Overdue Count</th>
                                        <th>Max Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($userActivity['overdue_users'])) {
                                        $count = 1;
                                        foreach ($userActivity['overdue_users'] as $user) {
                                            ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td><?php echo htmlspecialchars($user['FullName']); ?></td>
                                                <td><?php echo htmlspecialchars($user['EmailId']); ?></td>
                                                <td>
                                                    <span class="badge badge-danger"><?php echo $user['overdue_count']; ?></span>
                                                </td>
                                                <td><?php echo $user['max_days_overdue']; ?> days</td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">No overdue books</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
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

    <script>
        // Borrowing Trend Chart
        const borrowingCtx = document.getElementById('borrowingTrendChart').getContext('2d');
        const borrowingData = <?php echo json_encode(array_values($borrowingTrend)); ?>;
        const borrowingLabels = <?php echo json_encode(array_keys($borrowingTrend)); ?>;

        new Chart(borrowingCtx, {
            type: 'line',
            data: {
                labels: borrowingLabels.map(date => {
                    const d = new Date(date);
                    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Books Issued',
                    data: borrowingData,
                    borderColor: 'rgb(51, 122, 183)',
                    backgroundColor: 'rgba(51, 122, 183, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
