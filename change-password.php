<?php
session_start();
include('includes/config.php');
error_reporting(0);
if(strlen($_SESSION['login'])==0)
    {   
header('location:index.php');
}
else{ 
if(isset($_POST['change']))
  {
$password=md5($_POST['password']);
$newpassword=md5($_POST['newpassword']);
$email=$_SESSION['login'];
  $sql ="SELECT Password FROM tblstudents WHERE EmailId=:email and Password=:password";
$query= $dbh -> prepare($sql);
$query-> bindParam(':email', $email, PDO::PARAM_STR);
$query-> bindParam(':password', $password, PDO::PARAM_STR);
$query-> execute();
$results = $query -> fetchAll(PDO::FETCH_OBJ);
if($query -> rowCount() > 0)
{
$con="update tblstudents set Password=:newpassword where EmailId=:email";
$chngpwd1 = $dbh->prepare($con);
$chngpwd1-> bindParam(':email', $email, PDO::PARAM_STR);
$chngpwd1-> bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
$chngpwd1->execute();
$msg="Your Password succesfully changed";
}
else {
$error="Your current password is wrong";  
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
    <title>Online Library Management System | Change Password</title>
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
  <style>
    .errorWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #dd3d36;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
.succWrap{
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #5cb85c;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
    </style>
</head>
<script type="text/javascript">
function valid()
{
if(document.chngpwd.newpassword.value!= document.chngpwd.confirmpassword.value)
{
alert("New Password and Confirm Password Field do not match  !!");
document.chngpwd.confirmpassword.focus();
return false;
}
return true;
}
</script>

<body class="font-primary">
    <?php include('includes/header.php');?>
    <div class="min-h-screen bg-neutral flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
            <h4 class="text-2xl font-bold text-neutral-dark text-center">User Change Password</h4>
                 <?php if($error): ?>
                         <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                             <p><?php echo htmlentities($error); ?></p>
                        </div>
                <?php endif; ?>
                  <?php if($msg): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                         <p><?php echo htmlentities($msg); ?></p>
                   </div>
                 <?php endif; ?>

                 <form role="form" method="post" class="space-y-6"  onSubmit="return valid();" name="chngpwd">
                         <div class="space-y-1">
                               <label class="block text-sm font-medium text-neutral-dark">Current Password</label>
                                <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="password" name="password" autocomplete="off" required  />
                            </div>

                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-dark">Enter Password</label>
                               <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="password" name="newpassword" autocomplete="off" required  />
                          </div>

                       <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-dark">Confirm Password</label>
                              <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary"  type="password" name="confirmpassword" autocomplete="off" required  />
                          </div>

                     <button type="submit" name="change" class="w-full py-2 px-4 bg-primary text-white font-medium rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">Change </button>
                </form>
            </div>
    </div>
     <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
<?php } ?>