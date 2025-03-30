<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include('includes/config.php');

$showSidForm = true;
$showVerificationForm = false;
$showChangePasswordForm = false;

if (isset($_POST['submitSid'])) {
    error_log(print_r($_POST, true));
    $sid = $_POST['sid'];
    
        $sql = "SELECT telegram_chat_id, EmailId FROM tblstudents WHERE StudentId = :sid";
       try {
            $query = $dbh->prepare($sql);
            $query->bindParam(':sid', $sid, PDO::PARAM_STR);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_OBJ);
            if(!$result){
                error_log("No results found for the given sid");
               }else{
                   error_log(print_r($result, true));
               }
        } catch (PDOException $e) {
           $_SESSION['error'] = "Database Error: " . $e->getMessage();
             $showSidForm = true;
            $showVerificationForm = false;
            $showChangePasswordForm = false;
            exit();
       }

    if ($result && $result->telegram_chat_id) {
        $telegramChatId = $result->telegram_chat_id;
        $email = $result->EmailId;

         
        $pythonScriptPath = 'generate_code.py';
        // Get bot token from config file (for security)
        $botToken = '7304654930:AAF2Q_is81qMPx210n-hz1DkEYVuILPQfKA';

        // Execute the Python script and capture the output
        $command = "python " . escapeshellarg($pythonScriptPath) . " " . escapeshellarg($telegramChatId) . " " . escapeshellarg($botToken);
        $output = shell_exec($command);
        $returnCode = 0;
          if (is_null($output)) {
            error_log("Python script failed");
             $_SESSION['error'] = "Failed to send verification code.";
             $showSidForm = true;
            $showVerificationForm = false;
            $showChangePasswordForm = false;
             exit();
            }
         $verificationCode = trim($output);

          // Check if the Python script returned a non-zero return code or if output is empty
         if (empty($verificationCode)) {
            error_log("Python script failed to generate verification code or send Telegram message");
            $_SESSION['error'] = "Failed to send verification code.";
             $showSidForm = true;
            $showVerificationForm = false;
            $showChangePasswordForm = false;
            exit();
        }

        // Store the verification code, email and mobile in the session
        $_SESSION['recovery_code'] = $verificationCode;
        $_SESSION['recovery_email'] = $email;

        $showSidForm = false;
        $showVerificationForm = true;
        $showChangePasswordForm = false;
       $_SESSION['msg'] = "Verification code sent to your telegram account.";
    } else {
        $_SESSION['error'] = "Invalid Student ID or Telegram chat ID not found.";
        $showSidForm = true;
        $showVerificationForm = false;
        $showChangePasswordForm = false;
    }
}

// Verify the Telegram verification code
if (isset($_POST['verifyCode'])) {
     if ($_POST["vercode"] != $_SESSION["recovery_code"] || $_SESSION["recovery_code"] == '') {
        $_SESSION['error'] = "Incorrect verification code.";
         $showSidForm = false;
        $showVerificationForm = true;
        $showChangePasswordForm = false;
      } else {
           $showSidForm = false;
           $showVerificationForm = false;
           $showChangePasswordForm = true;
         }
}

// Handle final password change
if (isset($_POST['changePassword'])) {
        $email = $_SESSION['recovery_email'];
        $newpassword = md5($_POST['newpassword']);

       $sql = "SELECT EmailId FROM tblstudents WHERE EmailId=:email";
       try {
            $query = $dbh->prepare($sql);
            $query->bindParam(':email', $email, PDO::PARAM_STR);
             $query->execute();
            $results = $query->fetchAll(PDO::FETCH_OBJ);
       }  catch (PDOException $e) {
           $_SESSION['error'] = "Database Error: " . $e->getMessage();
             $showSidForm = false;
            $showVerificationForm = false;
            $showChangePasswordForm = true;
            exit();
        }

        if ($query->rowCount() > 0) {
            $con = "update tblstudents set Password=:newpassword where EmailId=:email";
            $chngpwd1 = $dbh->prepare($con);
            $chngpwd1->bindParam(':email', $email, PDO::PARAM_STR);
            $chngpwd1->bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
            $chngpwd1->execute();
              $_SESSION['msg'] = "Your password has been successfully changed.";
            // Clear session data after password change
            unset($_SESSION['recovery_code']);
            unset($_SESSION['recovery_email']);
             // Redirect to login page
             echo "<script type='text/javascript'> document.location ='index.php'; </script>";
            exit();

         } else {
               $_SESSION['error'] = "Invalid Email id.";
               $showSidForm = false;
               $showVerificationForm = false;
               $showChangePasswordForm = true;
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
    <title>Online Library Management System | Password Recovery</title>
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
      <style>
        .msg {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
        }
           .msg-success {
                background-color: #d4edda;
                 color: #155724;
               border: 1px solid #c3e6cb;
            }
             .msg-error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
             }

    </style>
    <script type="text/javascript">
        function valid() {
            if (document.changePasswordForm.newpassword.value != document.changePasswordForm.confirmpassword.value) {
                alert("New Password and Confirm Password Field do not match  !!");
                document.changePasswordForm.confirmpassword.focus();
                return false;
            }
            return true;
        }
    </script>
</head>
<body class="font-primary">
    <?php include('includes/header.php');?>
    <div class="min-h-screen bg-neutral flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
            <h4 class="text-2xl font-bold text-neutral-dark text-center">PASSWORD RECOVERY FORM</h4>
              <?php if(isset($_SESSION['msg'])): ?>
                        <div class="msg msg-success"><?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['error'])): ?>
                         <div class="msg msg-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>

            <?php if ($showSidForm): ?>
                 <form method="post" class="space-y-6">
                     <div class="space-y-1">
                        <label class="block text-sm font-medium text-neutral-dark">Student ID</label>
                        <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="text" name="sid"  required />
                     </div>
                      <button type="submit" name="submitSid" class="w-full py-2 px-4 bg-primary text-white font-medium rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">Get Verification Code</button>
                </form>
            <?php endif; ?>

            <?php if ($showVerificationForm): ?>
                <form method="post" class="space-y-6">
                  <div class="space-y-1">
                     <label class="block text-sm font-medium text-neutral-dark">Enter Verification Code </label>
                     <input type="text" name="vercode" maxlength="6" autocomplete="off" required class="border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary w-full" />
                  </div>
                  <button type="submit" name="verifyCode" class="w-full py-2 px-4 bg-primary text-white font-medium rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">Verify Code</button>
              </form>
           <?php endif; ?>

            <?php if ($showChangePasswordForm): ?>
               <form role="form" method="post" class="space-y-6" name="changePasswordForm"  onSubmit="return valid();">
                     <div class="space-y-1">
                         <label class="block text-sm font-medium text-neutral-dark">New Password</label>
                        <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="password" name="newpassword" autocomplete="off" required />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-neutral-dark">Confirm New Password</label>
                        <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="password" name="confirmpassword" autocomplete="off" required />
                     </div>

                    <button type="submit" name="changePassword" class="w-full py-2 px-4 bg-primary text-white font-medium rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">Change Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
     <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>