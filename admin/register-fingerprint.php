<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])==0) {   
    header('location:index.php');
} else {
    if(isset($_POST['register'])) {
        $studentid = $_POST['studentid'];
        $fingerprintid = $_POST['fingerprintid'];
        
        $sql = "UPDATE tblstudents SET FingerprintID=:fingerprintid WHERE StudentId=:studentid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':fingerprintid', $fingerprintid, PDO::PARAM_INT);
        $query->bindParam(':studentid', $studentid, PDO::PARAM_STR);
        $query->execute();
        
        $_SESSION['msg'] = "Fingerprint registered successfully";
        header('location:manage-students.php');
    }
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Library Management System | Register Fingerprint</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
</head>
<body>
<?php include('includes/header.php');?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h4 class="header-line">Register Student Fingerprint</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            Fingerprint Registration Form
                        </div>
                        <div class="panel-body">
                            <form role="form" method="post">
                                <div class="form-group">
                                    <label>Select Student</label>
                                    <select class="form-control" name="studentid" required>
                                        <option value="">Select Student</option>
                                        <?php
                                        $sql = "SELECT StudentId,FullName from tblstudents";
                                        $query = $dbh->prepare($sql);
                                        $query->execute();
                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                        if($query->rowCount() > 0) {
                                            foreach($results as $result) {
                                                echo '<option value="'.htmlentities($result->StudentId).'">'.htmlentities($result->FullName).' ('.htmlentities($result->StudentId).')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Fingerprint ID</label>
                                    <input class="form-control" type="number" name="fingerprintid" required />
                                    <p class="help-block">Enter the ID number to be used for this fingerprint (1-127)</p>
                                </div>
                                <div id="fingerprintStatus">
                                    <p>Waiting for fingerprint sensor...</p>
                                </div>
                                <button type="submit" name="register" class="btn btn-info">Register Fingerprint</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script>
        // Websocket connection to ESP32 can be implemented here for real-time feedback
        // This is a placeholder for the actual implementation
    </script>
</body>
</html>
<?php } ?>
