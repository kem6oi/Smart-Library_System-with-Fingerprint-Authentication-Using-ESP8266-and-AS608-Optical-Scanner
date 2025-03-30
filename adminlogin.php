<?php
session_start();
error_reporting(0);
include('includes/config.php');
if($_SESSION['alogin']!=''){
$_SESSION['alogin']='';
}
if(isset($_POST['login']))
{
 
if ($_POST["vercode"] != $_SESSION["vercode"] OR $_SESSION["vercode"]=='')  {
        echo "<script>alert('Incorrect verification code');</script>" ;
    } 
        else {

$username=$_POST['username'];
$password=md5($_POST['password']);
$sql ="SELECT UserName,Password FROM admin WHERE UserName=:username and Password=:password";
$query= $dbh -> prepare($sql);
$query-> bindParam(':username', $username, PDO::PARAM_STR);
$query-> bindParam(':password', $password, PDO::PARAM_STR);
$query-> execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
if($query->rowCount() > 0)
{
$_SESSION['alogin']=$_POST['username'];
echo "<script type='text/javascript'> document.location ='admin/dashboard.php'; </script>";
} else{
echo "<script>alert('Invalid Details');</script>";
}
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
    <title>Online Library Management System | Librarian Login</title>
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />

</head>
<body class="font-primary">
    <?php include('includes/header.php');?>
    <div class="min-h-screen bg-neutral flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
            <h4 class="text-2xl font-bold text-neutral-dark text-center">LIBRARIAN LOGIN FORM</h4>
            <form role="form" method="post" class="space-y-6">
                 <div class="space-y-1">
                     <label class="block text-sm font-medium text-neutral-dark">Enter Username</label>
                    <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="text" name="username" autocomplete="off" required />
                 </div>
                <div class="space-y-1">
                     <label class="block text-sm font-medium text-neutral-dark">Password</label>
                    <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="password" name="password" autocomplete="off" required />
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-neutral-dark">Verification code : </label>
                     <div class="flex items-center space-x-2">
                        <input type="text"  name="vercode" maxlength="5" autocomplete="off" required style="height:25px;" class="border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary w-20"/>
                         <img src="captcha.php" alt="captcha" >
                     </div>
                </div>
                <button type="submit" name="login" class="w-full py-2 px-4 bg-primary text-white font-medium rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">LOGIN </button>
             </form>
        </div>
    </div>
     <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>