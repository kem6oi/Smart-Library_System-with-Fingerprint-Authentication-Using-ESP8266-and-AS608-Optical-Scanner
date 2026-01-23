<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['login'])==0)
    {   
header('location:index.php');
}
else{ 
if(isset($_GET['del']))
{
$id=$_GET['del'];
$sql = "delete from tblbooks  WHERE id=:id";
$query = $dbh->prepare($sql);
$query -> bindParam(':id',$id, PDO::PARAM_STR);
$query -> execute();
$_SESSION['delmsg']="Category deleted scuccessfully ";
header('location:manage-books.php');

}


    ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Online Library Management System | Manage Issued Books</title>
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />

</head>
<body class="font-primary">
      <?php include('includes/header.php');?>
    <div class="container mx-auto py-8">
            <h4 class="text-2xl font-bold text-neutral-dark mb-6">Manage Issued Books</h4>
        <div class="bg-white p-8 rounded-lg shadow-md">
                 <table class="w-full border-collapse shadow-md" id="dataTables-example">
                        <thead>
                            <tr class="bg-primary text-white font-bold">
                                 <th class="border border-gray-200 p-2 text-left">#</th>
                                <th class="border border-gray-200 p-2 text-left">Book Name</th>
                                <th class="border border-gray-200 p-2 text-left">ISBN</th>
                                 <th class="border border-gray-200 p-2 text-left">Issued Date</th>
                                <th class="border border-gray-200 p-2 text-left">Return Date</th>
                                <th class="border border-gray-200 p-2 text-left">Fine in (USD)</th>
                           </tr>
                        </thead>
                        <tbody>
                        <?php 
                            $sid=$_SESSION['stdid'];
                            $sql="SELECT tblbooks.BookName,tblbooks.ISBNNumber,tblissuedbookdetails.IssuesDate,tblissuedbookdetails.ReturnDate,tblissuedbookdetails.id as rid,tblissuedbookdetails.fine from  tblissuedbookdetails join tblstudents on tblstudents.StudentId=tblissuedbookdetails.StudentId join tblbooks on tblbooks.id=tblissuedbookdetails.BookId where tblstudents.StudentId=:sid order by tblissuedbookdetails.id desc";
                            $query = $dbh -> prepare($sql);
                            $query-> bindParam(':sid', $sid, PDO::PARAM_STR);
                            $query->execute();
                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                            $cnt=1;
                            if($query->rowCount() > 0)
                            {
                                foreach($results as $result)
                                {               
                        ?>                                      
                                        <tr class="odd:bg-gray-100">
                                            <td class="border border-gray-200 p-2"><?php echo htmlentities($cnt);?></td>
                                           <td class="border border-gray-200 p-2"><?php echo htmlentities($result->BookName);?></td>
                                            <td class="border border-gray-200 p-2"><?php echo htmlentities($result->ISBNNumber);?></td>
                                            <td class="border border-gray-200 p-2"><?php echo htmlentities($result->IssuesDate);?></td>
                                              <td class="border border-gray-200 p-2"><?php if($result->ReturnDate=="")
                                                {?>
                                                <span style="color:red">
                                                <?php   echo htmlentities("Not Return Yet"); ?>
                                                    </span>
                                                <?php } else {
                                                echo htmlentities($result->ReturnDate);
                                            }
                                                ?></td>
                                            <td class="border border-gray-200 p-2"><?php echo htmlentities($result->fine);?></td>
                                        </tr>
                        <?php $cnt=$cnt+1;}} ?>                                      
                         </tbody>
                    </table>
                </div>
    </div>
     <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
<?php } ?>
