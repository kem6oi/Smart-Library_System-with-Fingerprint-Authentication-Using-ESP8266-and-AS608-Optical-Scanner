<?php
session_start();
include('includes/config.php');

// Telegram Bot Configuration
define('TELEGRAM_BOT_TOKEN', '7304654930:AAF2Q_is81qMPx210n-hz1DkEYVuILPQfKA'); // Replace with your bot token
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN);

// Function to send Telegram message
function sendTelegramMessage($chatId, $message) {
    $url = TELEGRAM_API_URL . '/sendMessage';
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML', // Optional: Use HTML formatting
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                       "Accept: application/json\r\n",
            'content' => json_encode($data),
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result; // Returns the Telegram API response
}

// Handle AJAX fingerprint verification
if (isset($_POST['action']) && $_POST['action'] === 'verify_fingerprint') {
    $studentId = $_POST['studentid'];
    $fingerprintId = $_POST['fingerprintid'];

    $sql = "SELECT FingerprintID FROM tblstudents WHERE StudentId = :studentId";
    $query = $dbh->prepare($sql);
    $query->bindParam(':studentId', $studentId, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);

    $response = ['success' => false, 'message' => ''];
    if ($result) {
        $fingerprintId = (int)trim($fingerprintId);
        if ($result->FingerprintID === null || $result->FingerprintID == $fingerprintId) {
            $_SESSION['fingerprint_verified'] = true;
            $response['success'] = true;
            $response['message'] = "Fingerprint verified successfully!";
        } else {
            $response['message'] = "Fingerprint verification failed";
        }
    } else {
        $response['message'] = "Student not found";
    }
    echo json_encode($response);
    exit();
}

// Handle book issuance
if (isset($_POST['issue']) && isset($_SESSION['fingerprint_verified']) && $_SESSION['fingerprint_verified'] === true) {
    $studentId = $_POST['studentid'];
    $bookId = $_POST['bookdetails'];

    // Fetch Student's Telegram Chat ID
    $sql_student = "SELECT telegram_chat_id, FullName FROM tblstudents WHERE StudentId = :studentId";
    $query_student = $dbh->prepare($sql_student);
    $query_student->bindParam(':studentId', $studentId, PDO::PARAM_STR);
    $query_student->execute();
    $student = $query_student->fetch(PDO::FETCH_OBJ);

    $sql = "SELECT CopiesAvailable, BookName FROM tblbooks WHERE id = :bookId";
    $query = $dbh->prepare($sql);
    $query->bindParam(':bookId', $bookId, PDO::PARAM_INT);
    $query->execute();
    $book = $query->fetch(PDO::FETCH_OBJ);

    if ($book && $book->CopiesAvailable > 0) {
        $sql = "INSERT INTO tblissuedbookdetails (BookId, StudentID) VALUES (:bookId, :studentId)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':bookId', $bookId, PDO::PARAM_INT);
        $query->bindParam(':studentId', $studentId, PDO::PARAM_STR);
        $query->execute();
        $lastInsertId = $dbh->lastInsertId(); // Get the ID of the newly inserted record

        $sql = "UPDATE tblbooks SET CopiesAvailable = CopiesAvailable - 1 WHERE id = :bookId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':bookId', $bookId, PDO::PARAM_INT);
        $query->execute();

        // Fetch the issue date and calculate the return date
        $sql_issue = "SELECT IssuesDate FROM tblissuedbookdetails WHERE id = :lastInsertId";
        $query_issue = $dbh->prepare($sql_issue);
        $query_issue->bindParam(':lastInsertId', $lastInsertId, PDO::PARAM_INT);
        $query_issue->execute();
        $issue = $query_issue->fetch(PDO::FETCH_OBJ);

        $issueDate = new DateTime($issue->IssuesDate);
        $returnDate = $issueDate->modify('+7 days')->format('Y-m-d');
        $bookName = htmlentities($book->BookName);
        $studentName = htmlentities($student->FullName);

        // Send Telegram Notification
        if ($student && !empty($student->telegram_chat_id)) {
            $message = "Hello " . $studentName . "!\n" .
                      "You have successfully checked out the book: <b>" . $bookName . "</b>\n" .
                      "Please return it by: <b>" . $returnDate . "</b>";
            $telegramResult = sendTelegramMessage($student->telegram_chat_id, $message);

            // Log Telegram API response for debugging
            error_log("Telegram API Response: " . print_r($telegramResult, true));

            if ($telegramResult === false) {
                error_log("Failed to send Telegram message. Check your bot token and chat ID.");
                $_SESSION['msg'] = "Book issued successfully, but Telegram notification failed";
            } else {
                $_SESSION['msg'] = "Book issued successfully and Telegram notification sent!";
            }
        } else {
            $_SESSION['msg'] = "Book issued successfully, but no Telegram chat ID found for the student";
        }

        unset($_SESSION['fingerprint_verified']);
    } else {
        $_SESSION['error'] = "Book not available";
    }

    header('Location: issue-book.php');
    exit();
} elseif (isset($_POST['issue']) && (!isset($_SESSION['fingerprint_verified']) || $_SESSION['fingerprint_verified'] !== true)) {
    $_SESSION['error'] = "Fingerprint verification required before issuing book.";
    header('Location: issue-book.php');
    exit();
}

$fingerprintVerified = isset($_SESSION['fingerprint_verified']) && $_SESSION['fingerprint_verified'] === true;
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Online Library Management System | Issue a new Book</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        #loaderIcon { display: none; }
        .status-box { padding: 10px; margin-top: 15px; border-radius: 4px; }
    </style>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script>
    function getstudent() {
        let studentid = $("#studentid").val();
        if (!studentid) {
            $("#get_student_name").html("");
            $("#fingerprintStatus").removeClass('alert-success alert-danger').addClass('alert-info').html('Enter Student ID first.');
            $('#verifyFingerprintBtn').prop('disabled', true);
            $('#submitBtn').prop('disabled', true);
            return;
        }
        $("#loaderIcon").show();
        jQuery.ajax({
            url: "get_student.php",
            data: 'studentid=' + studentid,
            type: "POST",
            success: function(data) {
                $("#get_student_name").html(data);
                $("#loaderIcon").hide();
                if (data.toLowerCase().includes("invalid") || data.trim() === "") {
                    $("#fingerprintStatus").removeClass('alert-success alert-info').addClass('alert-danger').html('Student ID not found or invalid.');
                    $('#verifyFingerprintBtn').prop('disabled', true);
                    $('#submitBtn').prop('disabled', true);
                } else {
                    $("#fingerprintStatus").removeClass('alert-danger alert-success').addClass('alert-info').html('Student found. Click "Verify Fingerprint" to proceed.');
                    $('#verifyFingerprintBtn').prop('disabled', false);
                    $('#submitBtn').prop('disabled', true);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $("#loaderIcon").hide();
                $("#get_student_name").html("<span style='color:red;'>Error fetching student details.</span>");
                $("#fingerprintStatus").removeClass('alert-success alert-info').addClass('alert-danger').html('Error fetching student details.');
                $('#verifyFingerprintBtn').prop('disabled', true);
                $('#submitBtn').prop('disabled', true);
                console.error("Get Student Error:", textStatus, errorThrown);
            }
        });
    }

    function getbook() {
        let bookid_input = $("#bookid").val();
        if (!bookid_input) {
            $("#get_book_name").html("<option value=''>Enter Book ID/ISBN first</option>").prop('disabled', true);
            return;
        }
        $("#loaderIcon").show();
        jQuery.ajax({
            url: "get_book.php",
            data: 'bookid=' + bookid_input,
            type: "POST",
            success: function(data) {
                $("#get_book_name").html(data);
                $("#loaderIcon").hide();
                if ($("#get_book_name").find('option[value!=""]').length > 0) {
                    $("#get_book_name").prop('disabled', false);
                    $("#get_book_name option:first").prop('selected', true);
                } else {
                    $("#get_book_name").prop('disabled', true);
                }
            },
            error: function() {
                $("#loaderIcon").hide();
                $("#get_book_name").html("<option value=''>Error fetching book details</option>").prop('disabled', true);
            }
        });
    }

    function checkFingerprint() {
        const esp8266IP = "192.168.1.167";
        const verifyUrl = `http://${esp8266IP}/verify`;
        const statusDiv = $("#fingerprintStatus");
        const submitButton = $('#submitBtn');
        const fingerprintInput = $('#fingerprintid');
        submitButton.prop('disabled', true);
        fingerprintInput.val('');
        statusDiv.removeClass('alert-danger alert-success').addClass('alert-info').html('Scanning... Place finger on sensor.');

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
                    fingerprintInput.val(data.fingerprintId);
                    statusDiv.removeClass('alert-info alert-danger').addClass('alert-success');
                    statusDiv.html(`Fingerprint scanned! Matched ID: ${data.fingerprintId}`);

                    // Verify with server via AJAX
                    $.ajax({
                        url: 'issue-book.php',
                        type: 'POST',
                        data: {
                            action: 'verify_fingerprint',
                            studentid: $('#studentid').val(),
                            fingerprintid: data.fingerprintId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                statusDiv.html(response.message);
                                $("#verifyFingerprintBtn").prop('disabled', true);
                                submitButton.prop('disabled', false); // Enable "Issue Book"
                            } else {
                                statusDiv.removeClass('alert-info alert-success').addClass('alert-danger');
                                statusDiv.html(`Verification Failed: ${response.message}`);
                                submitButton.prop('disabled', true);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            statusDiv.removeClass('alert-info alert-success').addClass('alert-danger');
                            statusDiv.html('Error verifying fingerprint with server.');
                            submitButton.prop('disabled', true);
                            console.error("AJAX Error:", textStatus, errorThrown);
                        }
                    });
                } else {
                    const message = data.message || "Fingerprint not recognized by sensor.";
                    statusDiv.removeClass('alert-info alert-success').addClass('alert-danger');
                    statusDiv.html(`Verification Failed: ${message}`);
                    submitButton.prop('disabled', true);
                }
            })
            .catch(error => {
                console.error('Fingerprint Check Error:', error);
                statusDiv.removeClass('alert-info alert-success').addClass('alert-danger');
                statusDiv.html(`Error contacting sensor: ${error.message}. Check ESP connection.`);
                submitButton.prop('disabled', true);
            });
    }

    $(document).ready(function() {
        if ($("#bookid").val()) {
            getbook();
        }

        <?php if (!$fingerprintVerified) { ?>
            $('#submitBtn').prop('disabled', true);
        <?php } else { ?>
            $('#submitBtn').prop('disabled', false);
            $("#fingerprintStatus").removeClass('alert-info alert-danger').addClass('alert-success').html('Fingerprint already verified. You can now issue the book.');
        <?php } ?>

        $("#bookid").on('blur change', function() {
            getbook();
        });
    });
    </script>
</head>
<body>
    <?php include('includes/header.php');?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">Issue a New Book</h4>
                </div>
            </div>
            <?php if(isset($_SESSION['error']) && $_SESSION['error']!=''){?>
            <div class="alert alert-danger">
                <strong>Error :</strong>
                <?php echo htmlentities($_SESSION['error']);?>
                <?php unset($_SESSION['error']);?>
            </div>
            <?php } ?>
            <?php if(isset($_SESSION['msg']) && $_SESSION['msg']!=''){?>
            <div class="alert alert-success">
                <strong>Success :</strong>
                <?php echo htmlentities($_SESSION['msg']);?>
                <?php unset($_SESSION['msg']);?>
            </div>
            <?php } ?>
            <div class="row">
                <div class="col-md-10 col-sm-10 col-xs-12 col-md-offset-1">
                    <div class="panel panel-info">
                        <div class="panel-heading">Issue a New Book</div>
                        <div class="panel-body">
                            <form role="form" method="post" id="form">
                                <div class="form-group">
                                    <label>Student ID<span style="color:red;">*</span></label>
                                    <input class="form-control" type="text" name="studentid" id="studentid" onBlur="getstudent()" autocomplete="off" required value="<?php echo isset($_POST['studentid']) ? $_POST['studentid'] : ''; ?>" />
                                </div>
                                <div class="form-group">
                                    <span id="get_student_name" style="font-size:16px;"></span>
                                    <img src="assets/img/loader.gif" id="loaderIcon" />
                                </div>
                                <div class="form-group">
                                    <label>Book ISBN or ID<span style="color:red;">*</span></label>
                                    <input class="form-control" type="text" name="bookid_input" id="bookid" onBlur="getbook()" required="required" placeholder="Enter ISBN or Book ID"/>
                                </div>
                                <div class="form-group">
                                    <label>Select Book<span style="color:red;">*</span></label>
                                    <select class="form-control" name="bookdetails" id="get_book_name" required="required" disabled>
                                        <option value="">Enter Book ISBN/ID above first</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Fingerprint Verification</label>
                                    <div id="fingerprintStatus" class="alert alert-info status-box">
                                        Enter Student ID first...
                                    </div>
                                    <input type="hidden" name="fingerprintid" id="fingerprintid" />
                                </div>
                                <div class="form-group">
                                    <button type="button" id="verifyFingerprintBtn" class="btn btn-warning" disabled onclick="checkFingerprint()">Verify Fingerprint</button>
                                    <button type="submit" name="issue" id="submitBtn" class="btn btn-info" disabled>Issue Book</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php');?>
    <script src="assets/js/bootstrap.js"></script>
</body>
</html>