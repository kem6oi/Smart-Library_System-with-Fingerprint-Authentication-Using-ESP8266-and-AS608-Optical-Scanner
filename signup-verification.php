<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

if(!isset($_SESSION['signup_verification_required']) || $_SESSION['signup_verification_required'] != true){
    header('location:signup.php');
    exit();
}

if(isset($_POST['verify'])) {
    $studentId = $_SESSION['signup_details']['studentId'];
    $fullName = $_SESSION['signup_details']['fullName'];
    $email = $_SESSION['signup_details']['email'];
    $password = $_SESSION['signup_details']['password'];
    $telegramChatId = $_SESSION['signup_details']['telegramChatId'];

    $verificationCode = $_POST['verification_code'];

    // Verify auth code
    $sql = "SELECT * FROM auth_codes WHERE code = :code AND student_id = :student_id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':code', $verificationCode, PDO::PARAM_STR);
    $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
    $query->execute();
    $auth_results = $query->fetchAll(PDO::FETCH_OBJ);

    if ($query->rowCount() > 0) {
        $sql="INSERT INTO  tblstudents(StudentId,FullName,EmailId,Password,telegram_chat_id, Status) VALUES(:studentid,:fullname,:email,:password, :telegramChatId, :status)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentid',$studentId,PDO::PARAM_STR);
        $query->bindParam(':fullname',$fullName,PDO::PARAM_STR);
        $query->bindParam(':email',$email,PDO::PARAM_STR);
        $query->bindParam(':password',$password,PDO::PARAM_STR);
        $query->bindParam(':telegramChatId',$telegramChatId,PDO::PARAM_STR);
        $query->bindValue(':status', 1, PDO::PARAM_INT); // Set status to active
        $query->execute();


        // Delete the auth code
        $sql = "DELETE FROM auth_codes WHERE code = :code AND student_id = :student_id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':code', $verificationCode, PDO::PARAM_STR);
        $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        $query->execute();

        $_SESSION['signup_msg']="Registration Successful! Please Log in";
        unset($_SESSION['signup_verification_required']);
        unset($_SESSION['signup_details']);
        header('location:index.php');
        exit();

    } else {
        $_SESSION['signup_error'] = "Invalid verification code.";
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
    <title>Online Library Management System | Student Signup Verification</title>
     <link href="assets/css/style.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body class="font-primary">
    <?php include('includes/header.php');?>
    <div class="min-h-screen bg-neutral flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
            <h4 class="text-2xl font-bold text-neutral-dark text-center">Student Signup Verification</h4>
            <form name="signup" method="post" class="space-y-6">
                <?php if(isset($_SESSION['signup_msg'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                        <p><?php echo $_SESSION['signup_msg']; unset($_SESSION['signup_msg']); ?></p>
                    </div>
                 <?php endif; ?>
                 <?php if(isset($_SESSION['signup_error'])): ?>
                     <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                           <p><?php echo $_SESSION['signup_error']; unset($_SESSION['signup_error']); ?></p>
                     </div>
                  <?php endif; ?>
                  <div class="space-y-1">
                    <label class="block text-sm font-medium text-neutral-dark">Verification Code :</label>
                    <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="text" name="verification_code" autocomplete="off" required  />
                 </div>
                 <button type="submit" name="verify" class="w-full py-2 px-4 bg-primary text-white font-medium rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">Verify </button>
             </form>
         </div>
    </div>
    <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>