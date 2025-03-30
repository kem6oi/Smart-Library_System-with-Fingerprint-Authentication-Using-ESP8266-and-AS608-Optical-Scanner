<?php
session_start();
error_reporting(0); // Change to E_ALL for development if needed
include('includes/config.php');

// Check if admin is logged in
if(strlen($_SESSION['alogin'])==0) {
    header('location:index.php');
    exit;
} else {
    // Optional: Handle actions like deleting or blocking students if needed (not implemented here)
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Library Management System | Manage Students</title>
    <!-- BOOTSTRAP CORE STYLE  -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE  -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- DATATABLE STYLE  -->
    <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
    <!-- CUSTOM STYLE  -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .status-active { color: green; font-weight: bold; }
        .status-blocked { color: red; font-weight: bold; }
        .fingerprint-yes { color: green; }
        .fingerprint-no { color: orange; }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">Manage Students</h4>
                </div>
            </div>

            <!-- Display Session Messages -->
            <?php if(isset($_SESSION['error']) && $_SESSION['error']!=''){?>
            <div class="alert alert-danger alert-dismissable">
                 <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <strong>Error :</strong> <?php echo htmlentities($_SESSION['error']);?>
                <?php unset($_SESSION['error']);?>
            </div>
            <?php } ?>
            <?php if(isset($_SESSION['msg']) && $_SESSION['msg']!=''){?>
            <div class="alert alert-success alert-dismissable">
                 <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <strong>Success :</strong> <?php echo htmlentities($_SESSION['msg']);?>
                <?php unset($_SESSION['msg']);?>
            </div>
            <?php } ?>
            <!-- Display Message from Fingerprint Registration Redirect -->
            <?php if(isset($_GET['reg_success']) && $_GET['reg_success'] == '1'){?>
             <div class="alert alert-success alert-dismissable">
                 <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                 <strong>Success :</strong> Fingerprint registered successfully!
             </div>
            <?php } ?>

             <div class="row">
                 <div class="col-md-12">
                     <a href="fingerprint_auth.php" class="btn btn-primary" style="margin-bottom: 15px;">
                         <i class="fa fa-user-plus"></i> Register New Fingerprint
                     </a>
                 </div>
             </div>

            <div class="row">
                <div class="col-md-12">
                    <!-- Advanced Tables -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Registered Students List
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="dataTables-example">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Email ID</th>
                                            <th>Reg. Date</th>
                                            <th>Status</th>
                                            <th>Fingerprint Registered</th>
                                            <th>FP Sensor ID</th>
                                            <!-- Add<th>Action</th> if needed for edit/delete -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    try {
                                        $sql = "SELECT StudentId, FullName, EmailId, RegDate, Status, FingerprintID FROM tblstudents ORDER BY RegDate DESC";
                                        $query = $dbh->prepare($sql);
                                        $query->execute();
                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                        $cnt = 1;
                                        if ($query->rowCount() > 0) {
                                            foreach ($results as $result) {
                                    ?>
                                        <tr class="odd gradeX">
                                            <td class="center"><?php echo htmlentities($cnt); ?></td>
                                            <td class="center"><?php echo htmlentities($result->StudentId); ?></td>
                                            <td class="center"><?php echo htmlentities($result->FullName); ?></td>
                                            <td class="center"><?php echo htmlentities($result->EmailId); ?></td>
                                            <td class="center"><?php echo htmlentities(date('d-M-Y', strtotime($result->RegDate))); ?></td>
                                            <td class="center">
                                                <?php if ($result->Status == 1) { ?>
                                                    <span class="status-active">Active</span>
                                                <?php } else { ?>
                                                    <span class="status-blocked">Blocked</span>
                                                <?php } ?>
                                            </td>
                                            <td class="center">
                                                <?php if ($result->FingerprintID !== null && $result->FingerprintID > 0) { ?>
                                                    <span class="fingerprint-yes"><i class="fa fa-check-circle"></i> Yes</span>
                                                <?php } else { ?>
                                                    <span class="fingerprint-no"><i class="fa fa-times-circle"></i> No</span>
                                                <?php } ?>
                                            </td>
                                             <td class="center">
                                                <?php echo ($result->FingerprintID !== null && $result->FingerprintID > 0) ? htmlentities($result->FingerprintID) : 'N/A'; ?>
                                             </td>
                                            <!-- Example Action Column (Uncomment and modify if needed)
                                            <td class="center">
                                                <a href="edit-student.php?id=<?php //echo htmlentities($result->StudentId); ?>" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> Edit</a>
                                                <a href="manage-students.php?delid=<?php //echo htmlentities($result->StudentId); ?>" onclick="return confirm('Are you sure you want to delete?');" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete</a>
                                            </td>
                                             -->
                                        </tr>
                                    <?php
                                            $cnt++;
                                            } // end foreach
                                        } else { // If no students found
                                            echo '<tr><td colspan="8" class="text-center">No students found in the database.</td></tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<tr><td colspan="8" class="text-center text-danger">Database Error: Could not retrieve student data. ' . $e->getMessage() . '</td></tr>'; // Display error
                                        error_log("Database Error in manage-students.php: " . $e->getMessage());
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!--End Advanced Tables -->
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php'); ?>
    <!-- CORE JQUERY  -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- BOOTSTRAP SCRIPTS  -->
    <script src="assets/js/bootstrap.js"></script>
    <!-- DATATABLE SCRIPTS  -->
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
    <!-- CUSTOM SCRIPTS  -->
    <script>
        $(document).ready(function () {
            $('#dataTables-example').dataTable({
                // Optional: Add configuration options here
                 "order": [[ 4, "desc" ]] // Order by registration date descending by default
            });
        });
    </script>
</body>
</html>
<?php
} // End else for admin login check
?>