<?php
session_start();
error_reporting(E_ALL); // Turn on error reporting for debugging
ini_set('display_errors', 1);
include('includes/config.php');
// --- Part 1: Handle POST request from ESP8266 ---
// Check if it's a POST request specifically for registration from ESP
// The 'register=1' flag is sent by the ESP8266 code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register']) && $_POST['register'] == '1') {
    // Optional: Basic check for the custom header sent by ESP
    // if (!isset($_SERVER['HTTP_X_ESP8266']) || $_SERVER['HTTP_X_ESP8266'] !== 'true') {
    //     http_response_code(403); // Forbidden
    //     echo "ERROR: Access Forbidden.";
    //     exit;
    // }
    if (isset($_POST['studentid']) && isset($_POST['fingerprintid'])) {
        $studentid = $_POST['studentid'];
        $fingerprintid = filter_var($_POST['fingerprintid'], FILTER_VALIDATE_INT); // Sanitize fingerprint ID
        if ($fingerprintid === false || $fingerprintid < 1 || $fingerprintid > 127) { // Validate range (adjust max if needed)
             http_response_code(400); // Bad Request
             echo "ERROR: Invalid Fingerprint ID provided.";
             error_log("Fingerprint Auth Error: Invalid Fingerprint ID received: " . $_POST['fingerprintid']);
             exit;
        }
        // Check if student ID exists
        $sql_check_student = "SELECT id FROM tblstudents WHERE StudentId = :studentid";
        $query_check_student = $dbh->prepare($sql_check_student);
        $query_check_student->bindParam(':studentid', $studentid, PDO::PARAM_STR);
        $query_check_student->execute();
        if ($query_check_student->rowCount() == 0) {
            http_response_code(404); // Not Found
            echo "ERROR: Student ID not found.";
            error_log("Fingerprint Auth Error: Student ID not found: " . $studentid);
            exit;
        }
        // Check if fingerprint ID is already taken
        $sql_check_fp = "SELECT StudentId FROM tblstudents WHERE FingerprintID = :fingerprintid";
        $query_check_fp = $dbh->prepare($sql_check_fp);
        $query_check_fp->bindParam(':fingerprintid', $fingerprintid, PDO::PARAM_INT);
        $query_check_fp->execute();
        if ($query_check_fp->rowCount() > 0) {
            $existing_student = $query_check_fp->fetch(PDO::FETCH_OBJ)->StudentId;
             if ($existing_student != $studentid) { // Check if it's taken by *another* student
                http_response_code(409); // Conflict
                echo "ERROR: Fingerprint ID already assigned to another student.";
                error_log("Fingerprint Auth Error: Fingerprint ID " . $fingerprintid . " already assigned to student " . $existing_student);
                exit;
             }
             // If it's assigned to the *same* student, allow update (or do nothing, depends on desired logic)
        }
        // Update the database
        try {
            $sql_update = "UPDATE tblstudents SET FingerprintID = :fingerprintid WHERE StudentId = :studentid";
            $query_update = $dbh->prepare($sql_update);
            $query_update->bindParam(':fingerprintid', $fingerprintid, PDO::PARAM_INT);
            $query_update->bindParam(':studentid', $studentid, PDO::PARAM_STR);
            $query_update->execute();
            if ($query_update->rowCount() > 0) {
                http_response_code(200);
                echo "OK: Fingerprint registered successfully in DB."; // Simple success message for ESP
                exit;
            } else {
                // This might happen if the FingerprintID was already set to the same value
                // Consider it a success in that case? Or investigate further.
                // Let's assume it's okay if no rows were changed but no error occurred.
                $sql_verify = "SELECT FingerprintID FROM tblstudents WHERE StudentId = :studentid";
                $query_verify = $dbh->prepare($sql_verify);
                $query_verify->bindParam(':studentid', $studentid, PDO::PARAM_STR);
                $query_verify->execute();
                $current_fp_id = $query_verify->fetchColumn();
                if ($current_fp_id == $fingerprintid) {
                    http_response_code(200);
                    echo "OK: Fingerprint ID already correctly set in DB.";
                    exit;
                } else {
                    http_response_code(500); // Internal Server Error
                    echo "ERROR: Database update failed - rowCount was 0, ID not set.";
                    error_log("Fingerprint Auth Error: DB update failed (rowCount 0) for Student: " . $studentid . ", FP ID: " . $fingerprintid);
                    exit;
                }
            }
        } catch (PDOException $e) {
            http_response_code(500); // Internal Server Error
            echo "ERROR: Database error occurred.";
            // Log detailed error to server logs, not to ESP
            error_log("Fingerprint Auth DB Error: " . $e->getMessage());
            exit;
        }
    } else {
        http_response_code(400); // Bad Request
        echo "ERROR: Missing studentid or fingerprintid.";
        error_log("Fingerprint Auth Error: Missing POST parameters from ESP.");
        exit;
    }
}
// --- Part 2: Handle request from Admin Browser (Display Form) ---
// This part requires the admin to be logged in
if (strlen($_SESSION['alogin']) == 0) {
    // If it wasn't an ESP POST request AND the admin is not logged in, redirect to login
    header('location:index.php');
    exit; // Stop script execution after redirect
} else {
    // Admin is logged in, proceed to display the page content
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
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
<style>
    #loaderIcon { display: none; } /* Hide loader icon by default */
    .status-box { padding: 10px; margin-top: 15px; border-radius: 4px; }
</style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">Register Student Fingerprint</h4>
                </div>
            </div>
             <?php if(isset($_SESSION['error'])){?>
            <div class="alert alert-danger">
                <strong>Error :</strong>
                <?php echo htmlentities($_SESSION['error']);?>
                <?php echo htmlentities($_SESSION['error']="");?>
            </div>
            <?php } ?>
             <?php if(isset($_SESSION['msg'])){?>
            <div class="alert alert-success">
                <strong>Success :</strong>
                <?php echo htmlentities($_SESSION['msg']);?>
                <?php echo htmlentities($_SESSION['msg']="");?>
            </div>
            <?php } ?>
            <div class="row">
                <div class="col-md-6 col-sm-8 col-xs-12 col-md-offset-3">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            Fingerprint Registration Form
                        </div>
                        <div class="panel-body">
                            <!-- The form no longer needs method="post" or name="register" as submission is handled by JS -->
                            <form role="form" id="fingerprintForm">
                                <div class="form-group">
                                    <label>Select Student <span style="color:red;">*</span></label>
                                    <select class="form-control" name="studentid" id="studentid" required>
                                        <option value="">Select Student</option>
                                        <?php
                                        // Fetch students who DO NOT have a fingerprint ID yet or allow re-registration
                                        // $sql = "SELECT StudentId,FullName FROM tblstudents WHERE FingerprintID IS NULL ORDER BY FullName ASC";
                                        $sql = "SELECT StudentId,FullName FROM tblstudents ORDER BY FullName ASC"; // Or show all
                                        $query = $dbh->prepare($sql);
                                        $query->execute();
                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                        if ($query->rowCount() > 0) {
                                            foreach ($results as $result) {
                                                echo '<option value="'.htmlentities($result->StudentId).'">'.htmlentities($result->FullName).' ('.htmlentities($result->StudentId).')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Fingerprint ID <span style="color:red;">*</span></label>
                                    <input class="form-control" type="number" name="fingerprintid" id="fingerprintid" min="1" max="127" required />
                                    <p class="help-block">Enter the ID number to store this fingerprint on the sensor (1-127).</p>
                                </div>
                                <div id="fingerprintStatus" class="alert alert-warning"> <!-- Start with warning -->
                                    <p>Select student and ID, then click Register to begin sensor enrollment.</p>
                                </div>
                                <!-- Button type is changed to "button" to prevent default form submission -->
                                <button type="button" id="registerBtn" class="btn btn-info">Register Fingerprint</button>

                                <!-- New Section for Scanning Fingerprint -->
                                <div class="form-group">
                                    <label>Verify Fingerprint</label>
                                    <div id="scanFingerprintStatus" class="alert alert-info status-box">
                                        Select a student and click "Scan Fingerprint" to verify.
                                    </div>
                                    <button type="button" id="scanFingerprintBtn" class="btn btn-warning" disabled>Scan Fingerprint</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php'); ?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script>
        $(document).ready(function() {
            // *** IMPORTANT: Replace with your ESP8266's actual IP address ***
            const esp8266IP = "192.168.1.167";
            const enrollUrl = `http://${esp8266IP}/enroll`;
            const verifyUrl = `http://${esp8266IP}/verify`;
            const statusDiv = $("#fingerprintStatus");
            const registerBtn = $("#registerBtn");
            const scanFingerprintBtn = $("#scanFingerprintBtn");
            const scanFingerprintStatus = $("#scanFingerprintStatus");

            // Enable the Scan Fingerprint button when a student is selected
            $("#studentid").on("change", function() {
                if ($(this).val()) {
                    scanFingerprintBtn.prop("disabled", false);
                } else {
                    scanFingerprintBtn.prop("disabled", true);
                }
            });

            // Handle fingerprint registration
            registerBtn.on("click", function() {
                const studentid = $("#studentid").val();
                const fingerprintid = $("#fingerprintid").val();
                // Basic validation before contacting ESP
                if (!studentid) {
                    statusDiv.removeClass('alert-info alert-success alert-warning').addClass('alert-danger');
                    statusDiv.html("<p>Please select a student.</p>");
                    return;
                }
                if (!fingerprintid || fingerprintid < 1 || fingerprintid > 127) { // Adjust max if sensor capacity differs
                    statusDiv.removeClass('alert-info alert-success alert-warning').addClass('alert-danger');
                    statusDiv.html("<p>Please enter a valid Fingerprint ID (1-127).</p>");
                    return;
                }
                statusDiv.removeClass('alert-danger alert-success alert-warning').addClass('alert-info');
                statusDiv.html("<p>Contacting fingerprint sensor... Please follow instructions on the sensor/Serial Monitor.<br/>Place finger on sensor...</p>");
                registerBtn.prop('disabled', true); // Disable button during process
                // Send request to ESP8266 to start enrollment
                $.ajax({
                    url: enrollUrl,
                    type: "POST",
                    // Data sent TO the ESP8266
                    data: {
                        id: fingerprintid, // ESP expects 'id'
                        studentid: studentid // ESP expects 'studentid'
                    },
                    dataType: "json", // Expect JSON response FROM the ESP8266
                    timeout: 120000, // Set a longer timeout (e.g., 120 seconds) for enrollment process
                    success: function(response) {
                        // Response from ESP8266
                        if (response.success) {
                            statusDiv.removeClass('alert-info alert-danger alert-warning').addClass('alert-success');
                            statusDiv.html("<p><strong>Success!</strong> " + response.message + "</p>");
                            // Set a session message for display after redirect
                             $.post(window.location.href, { set_session_msg: response.message }); // Simple way to set session via ajax if needed
                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = "manage-students.php?reg_success=1"; // Redirect to manage students page
                            }, 3000);
                        } else {
                            statusDiv.removeClass('alert-info alert-success alert-warning').addClass('alert-danger');
                            statusDiv.html("<p><strong>Error:</strong> " + response.message + "</p>");
                            registerBtn.prop('disabled', false); // Re-enable button on failure
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Error connecting TO the ESP8266
                        statusDiv.removeClass('alert-info alert-success alert-warning').addClass('alert-danger');
                        let errorMsg = "Error connecting to the fingerprint sensor.";
                        if (textStatus === "timeout") {
                             errorMsg = "Timeout: No response from fingerprint sensor. Check connection and sensor status.";
                        } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                            errorMsg = "Sensor Error: " + jqXHR.responseJSON.message;
                        } else if (jqXHR.responseText) {
                            errorMsg = "Sensor Error: " + jqXHR.responseText;
                        }
                         console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                        statusDiv.html("<p><strong>Error:</strong> " + errorMsg + "</p>");
                        registerBtn.prop('disabled', false); // Re-enable button on failure
                    }
                });
            });

            // Handle fingerprint scanning
            scanFingerprintBtn.on("click", function() {
                const studentid = $("#studentid").val();
                if (!studentid) {
                    scanFingerprintStatus.removeClass('alert-success alert-danger').addClass('alert-warning');
                    scanFingerprintStatus.html("Please select a student first.");
                    return;
                }
                scanFingerprintStatus.removeClass('alert-success alert-danger').addClass('alert-info');
                scanFingerprintStatus.html("Scanning... Place finger on sensor.");
                fetch(verifyUrl)
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(errorData => {
                                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                            }).catch(() => {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.fingerprintId) {
                            scanFingerprintStatus.removeClass('alert-info alert-danger').addClass('alert-success');
                            scanFingerprintStatus.html(`Fingerprint verified! Matched ID: ${data.fingerprintId}`);
                        } else {
                            scanFingerprintStatus.removeClass('alert-info alert-success').addClass('alert-danger');
                            scanFingerprintStatus.html(`Verification Failed: ${data.message || "Fingerprint not recognized."}`);
                        }
                    })
                    .catch(error => {
                        console.error('Fingerprint Check Error:', error);
                        scanFingerprintStatus.removeClass('alert-info alert-success').addClass('alert-danger');
                        scanFingerprintStatus.html(`Error contacting sensor: ${error.message}. Check ESP connection.`);
                    });
            });
        });
    </script>
</body>
</html>
<?php
 } // End of else block for checking admin login
?>