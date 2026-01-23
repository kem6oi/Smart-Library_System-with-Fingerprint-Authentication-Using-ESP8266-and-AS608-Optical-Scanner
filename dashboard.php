<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['login'])==0)
  {
header('location:index.php');
}
else{
  
  $sid=$_SESSION['stdid'];
  $sql = "SELECT FullName FROM tblstudents WHERE StudentId = :sid";
  $query = $dbh -> prepare($sql);
  $query->bindParam(':sid',$sid,PDO::PARAM_STR);
  $query->execute();
  $result = $query->fetch(PDO::FETCH_OBJ);
  $studentName = $result ? $result->FullName : "User";

  
  $hour = date('H');
  if ($hour < 12) {
      $greeting = "Good Morning";
  } elseif ($hour < 18) {
      $greeting = "Good Afternoon";
  } else {
      $greeting = "Good Evening";
  }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Smart Library Management System | Student's Dashboard</title>
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />

</head>
<body class="font-primary">
    <?php include('includes/header.php');?>
    <div class="container mx-auto py-8 mt-8">
      <h4 class="text-2xl font-bold text-neutral-dark mb-6"><?php echo $greeting . " " .  explode(" ", $studentName)[0]; ?></h4>
        <div class="flex flex-wrap justify-center -mx-4">
            <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 px-4 mb-8">
                <div class="bg-neutral rounded-md shadow-md text-center h-48 flex flex-col items-center justify-center border border-blue-200">
                     <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 128 128"><path fill="#01579b" d="M118.03 102.32L72.29 123c-2.82 1.33-5.76 1.2-8.46-.36L6.09 93.32c-1.65-1.06-2.14-2.61-2.04-3.69s.35-2.25 3.25-3.09l4.28-1.58l57.92 31.57l41.16-16.82z"/><path fill="#f5f5f5" d="M71.74 119.69a7.95 7.95 0 0 1-7.26-.26L8.11 91.03c-.8-.44-1.04-1.45-.56-2.23c1.24-2.05 3.52-8.53-.24-13.91l63.66 30.65z"/><path fill="#94c6d6" d="m115.59 99.98l-43.85 19.71c-1.45.63-4.34 1.75-7.67-.49c2.63.19 4.48-.9 5.43-2.67c.93-1.72.65-4.54-.48-6.13c-.69-.96-2.54-2.49-3.35-3.35L113.1 88.5c4.2-1.73 8.14.86 8.77 4.01c.7 3.56-3.84 6.47-6.28 7.47"/><path fill="#01579b" d="m117.78 86.96l-45.27 20.2c-2.85 1.13-6.04.98-8.77-.4L5.9 77.38c-.56-.28-1.39-1.05-1.72-2.1c-.54-1.75.14-3.95 2.19-4.65l62.68 31.95l42.92-18.37z"/><path fill="#0091ea" d="m121.19 89.89l-4.93-1.79l-10.16.59l-33.58 14.99c-2.85 1.13-6.04.98-8.77-.4L5.9 73.91c-1.49-.76-1.17-2.97.47-3.28l41.69-18.65c1.19-.22 2.41-.09 3.52.38l59.49 28.36s9.45 6.47 10.12 9.17"/><path fill="#616161" d="M105.53 88.98s6.26-2.45 11.18-2.23s6.63 3.67 6.63 3.67c-.93-4.23-5.3-6.39-5.3-6.39l-65-32.73c-.45-.19-2.11-.58-4.66.47c-2.06.85-8.79 4-8.79 4z"/><path fill="#424242" d="M123.62 91.22c-.47-1.87-1.63-3.87-3.77-4.84c-2.82-1.27-6.84-.94-9.41.4l-4.91 2.18v3.46l6.21-2.76c6.04-2.69 8.72 1.34 8.95 2.29c.96 3.87-.9 6.11-6.39 8.63l-8.92 4.02v3.48l10.26-4.57c4.54-1.82 9.72-5.24 7.98-12.29"/><path fill="#01579b" d="M33.01 90.31L15.74 66.44l2.71-1.21l19.43 26.7zm22.15 11l-3.08-2.44l53.45-10.91v1.75l-7.49 2.84z"/><path fill="#9ccc65" d="M14.8 46.18L82.31 34.9l29.49 32.47c1.49 1.57.68 4.17-1.44 4.6l-69.7 14.3z"/><path fill="#689f38" d="M110.36 69.17L41.14 83.19l-.22 3.3l69.44-14.24c1.96-.41 2.78-2.65 1.71-4.23c-.38.56-.96 1-1.71 1.15m3.73 15.13c.73 1.16.07 2.69-1.27 2.96L49.1 100.18c-3.83.79-7.59-1.72-7.93-5.62c-.29-3.3 1.94-6.29 5.19-6.97l61.28-13.76z"/><path fill="#616161" d="M55.59 80.1L30.21 43.78l-14.48 3.83c-3.35 3.33-2.1 8.8-2.1 8.8S35.8 91.99 39.3 96.54s8.61 3.84 8.61 3.84l8.63-1.74l-.9-16.1z"/><path fill="#424242" d="M55.59 80.34L43.4 82.86c-3.33.75-3.93 3.88-3.93 3.88L10.04 44.57s-4.19 5.07-1.41 9.38L39.3 96.54c3.35 4.77 8.61 3.88 8.61 3.88l8.63-1.74l-.89-15.78z"/><path fill="#b9e4ea" d="M110.25 83c.31.68-.09 1.47-.82 1.62L48.5 97.12c-3.83.79-6.54-1.75-6.4-5.21c.18-4.37 2.63-6.22 5.87-6.89l61.23-12.51s-2.08 2.34-.49 6.72c.54 1.51 1.12 2.85 1.54 3.77"/><path fill="none" stroke="#424242" stroke-miterlimit="10" stroke-width="2.071" d="M45.21 83.7L19.1 46.76"/><path fill="#424242" d="M47.26 67.95L13.68 51.03l-1.36 2.68l38.8 19.77z"/><path fill="#689f38" d="m108.79 64.03l-2.46-2.7L68.5 78.69L47.26 68.18l3.62 5.18l14.07 7.19l10.48-1.61z"/><path fill="#c62828" d="M118.02 57.35L72.29 78.03c-2.82 1.33-5.76 1.2-8.46-.36L6.09 48.35c-1.65-1.06-2.14-2.61-2.04-3.69s.35-2.25 3.25-3.09l2.71-1l59.32 29.11l48.17-19.93z"/><path fill="#f5f5f5" d="M71.73 74.72a7.95 7.95 0 0 1-7.26-.26L8.1 46.06c-.8-.44-1.04-1.45-.56-2.23c1.24-2.05 3.52-8.53-.24-13.91l62.24 31.66z"/><path fill="#94c6d6" d="M115.58 55.01L71.73 74.72c-1.45.63-4.34 1.75-7.67-.49c2.63.19 4.48-.9 5.43-2.67c.93-1.72.65-4.54-.48-6.13c-.69-.96-2.54-2.49-3.35-3.35l47.43-18.55c4.2-1.73 8.14.86 8.77 4.01c.7 3.56-3.84 6.47-6.28 7.47"/><path fill="#c62828" d="m117.78 41.99l-45.27 20.2c-2.85 1.13-6.04.98-8.77-.4L5.89 32.41c-.6-.3-1.5-1.07-1.79-2.16c-.43-1.62.13-3.75 2.26-4.59l53.01-11.23z"/><path fill="#f44336" d="m121.18 44.92l-4.93-1.79l-10.16.59l-33.58 14.99c-2.85 1.13-6.04.98-8.77-.4L5.89 28.93c-1.49-.76-.96-2.77.47-3.28l41.7-18.64c1.19-.22 2.41-.09 3.52.38l59.49 28.36s9.44 6.46 10.11 9.17"/><path fill="#616161" d="M105.53 44s5.21-1.83 10.13-1.61s7.69 3.05 7.69 3.05c-1.01-4.52-5.3-6.39-5.3-6.39l-65-32.73c-.45-.19-2.11-.58-4.66.47c-2.06.85-8.79 4-8.79 4z"/><path fill="#424242" d="M111.48 41.86L44.97 8.31l2.2-.99l67.64 33.9z"/><path fill="#424242" d="M123.61 46.25c-.47-1.87-1.26-3.68-3.49-4.62c-2.85-1.2-5.45-1.45-9.69.18l-4.91 2.18v3.46l6.21-2.76c3.15-1.48 7.79-1.16 8.95 2.29c1.27 3.78-.9 6.11-6.39 8.63l-8.92 4.02v3.48l10.26-4.57c4.55-1.82 9.73-5.24 7.98-12.29"/></svg>
                       <?php
                        $sid=$_SESSION['stdid'];
                        $sql1 ="SELECT id from tblissuedbookdetails where StudentID=:sid";
                        $query1 = $dbh -> prepare($sql1);
                        $query1->bindParam(':sid',$sid,PDO::PARAM_STR);
                        $query1->execute();
                        $results1=$query1->fetchAll(PDO::FETCH_OBJ);
                        $issuedbooks=$query1->rowCount();
                    ?>
                    <h3 class="text-xl font-bold mb-2"><?php echo htmlentities($issuedbooks);?></h3>
                     <p class="text-gray-500">Books Borrowed</p>
                </div>
            </div>

                <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 px-4 mb-8">
                     <div class="bg-neutral rounded-md shadow-md text-center h-48 flex flex-col items-center justify-center border border-blue-200">
                     <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 32 32"><g fill="none"><path fill="#00a6ed" d="M17.045 27.286H30V13a2 2 0 0 0-2-2H17.045z"/><path fill="#d3d3d3" d="M15.682 27.964H30v1.357H15.682z"/><path fill="#0074ba" d="M16.023 11A1.02 1.02 0 0 0 15 12.018v16.625h.682a.68.68 0 0 1 .682-.679h.681V11z"/><path fill="#0074ba" d="M16.023 27.286A1.02 1.02 0 0 0 15 28.304v.678A1.02 1.02 0 0 0 16.023 30h12.954c.446 0 .824-.283.965-.678H16.364a.68.68 0 0 1-.682-.68a.68.68 0 0 1 .682-.678H30v-.678z"/><path fill="#ca0b4a" d="M10.045 23.286H23V9a2 2 0 0 0-2-2H10.045z"/><path fill="#d3d3d3" d="M8.682 23.964H23v1.357H8.682z"/><path fill="#990838" d="M9.023 7A1.02 1.02 0 0 0 8 8.018v16.625h.682a.68.68 0 0 1 .682-.679h.681V7z"/><path fill="#990838" d="M9.023 23.286A1.02 1.02 0 0 0 8 24.304v.678A1.02 1.02 0 0 0 9.023 26h12.954c.446 0 .824-.283.965-.678H9.364a.68.68 0 0 1-.682-.68a.68.68 0 0 1 .682-.678H23v-.678z"/><path fill="#86d72f" d="M4.045 20.286H17V6a2 2 0 0 0-2-2H4.045z"/><path fill="#d3d3d3" d="M2.682 20.964H17v1.357H2.682z"/><path fill="#44911b" d="M3.023 4A1.02 1.02 0 0 0 2 5.018v16.625h.682a.68.68 0 0 1 .682-.679h.681V4z"/><path fill="#008463" d="M3.023 20.286A1.02 1.02 0 0 0 2 21.304v.678A1.02 1.02 0 0 0 3.023 23h12.954c.446 0 .824-.283.965-.678H3.364a.68.68 0 0 1-.682-.68a.68.68 0 0 1 .682-.678H17v-.678z"/></g></svg>
                       <?php
                            $rsts=0;
                            $sql2 ="SELECT id from tblissuedbookdetails where StudentID=:sid and RetrunStatus=:rsts";
                            $query2 = $dbh -> prepare($sql2);
                            $query2->bindParam(':sid',$sid,PDO::PARAM_STR);
                            $query2->bindParam(':rsts',$rsts,PDO::PARAM_STR);
                            $query2->execute();
                            $results2=$query2->fetchAll(PDO::FETCH_OBJ);
                            $returnedbooks=$query2->rowCount();
                    ?>
                       <h3 class="text-xl font-bold mb-2"><?php echo htmlentities($returnedbooks);?></h3>
                     <p class="text-gray-500">Books Not Returned Yet</p>
                </div>
                </div>


               <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 px-4 mb-8">
                     <div class="bg-neutral rounded-md shadow-md text-center h-48 flex flex-col items-center justify-center border border-blue-200">
                     <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"><path fill="#0cff46" d="M12 16a4 4 0 1 0 0-8a4 4 0 0 0 0 8m9.005-11.997h-18a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-14a1 1 0 0 0-1-1m-17 11.643V8.354a3.51 3.51 0 0 0 2.35-2.351h11.291a3.51 3.51 0 0 0 2.359 2.353v7.288a3.51 3.51 0 0 0-2.36 2.359H6.355a3.51 3.51 0 0 0-2.351-2.357"/></svg>
                            <h3 class="text-xl font-bold mb-2">View Bills</h3>
                            <p> <a href="student-bills.php" class="text-primary hover:text-secondary">Click Here</a></p>
                    </div>
                </div>


                 <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 px-4 mb-8">
                     <div class="bg-neutral rounded-md shadow-md text-center h-48 flex flex-col items-center justify-center border border-blue-200">
                          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 128 128"><path fill="#01579b" d="M118.03 102.32L72.29 123c-2.82 1.33-5.76 1.2-8.46-.36L6.09 93.32c-1.65-1.06-2.14-2.61-2.04-3.69s.35-2.25 3.25-3.09l4.28-1.58l57.92 31.57l41.16-16.82z"/><path fill="#f5f5f5" d="M71.74 119.69a7.95 7.95 0 0 1-7.26-.26L8.11 91.03c-.8-.44-1.04-1.45-.56-2.23c1.24-2.05 3.52-8.53-.24-13.91l63.66 30.65z"/><path fill="#94c6d6" d="m115.59 99.98l-43.85 19.71c-1.45.63-4.34 1.75-7.67-.49c2.63.19 4.48-.9 5.43-2.67c.93-1.72.65-4.54-.48-6.13c-.69-.96-2.54-2.49-3.35-3.35L113.1 88.5c4.2-1.73 8.14.86 8.77 4.01c.7 3.56-3.84 6.47-6.28 7.47"/><path fill="#01579b" d="m117.78 86.96l-45.27 20.2c-2.85 1.13-6.04.98-8.77-.4L5.9 77.38c-.56-.28-1.39-1.05-1.72-2.1c-.54-1.75.14-3.95 2.19-4.65l62.68 31.95l42.92-18.37z"/><path fill="#0091ea" d="m121.19 89.89l-4.93-1.79l-10.16.59l-33.58 14.99c-2.85 1.13-6.04.98-8.77-.4L5.9 73.91c-1.49-.76-1.17-2.97.47-3.28l41.69-18.65c1.19-.22 2.41-.09 3.52.38l59.49 28.36s9.45 6.47 10.12 9.17"/><path fill="#616161" d="M105.53 88.98s6.26-2.45 11.18-2.23s6.63 3.67 6.63 3.67c-.93-4.23-5.3-6.39-5.3-6.39l-65-32.73c-.45-.19-2.11-.58-4.66.47c-2.06.85-8.79 4-8.79 4z"/><path fill="#424242" d="M123.62 91.22c-.47-1.87-1.63-3.87-3.77-4.84c-2.82-1.27-6.84-.94-9.41.4l-4.91 2.18v3.46l6.21-2.76c6.04-2.69 8.72 1.34 8.95 2.29c.96 3.87-.9 6.11-6.39 8.63l-8.92 4.02v3.48l10.26-4.57c4.54-1.82 9.72-5.24 7.98-12.29"/><path fill="#01579b" d="M33.01 90.31L15.74 66.44l2.71-1.21l19.43 26.7zm22.15 11l-3.08-2.44l53.45-10.91v1.75l-7.49 2.84z"/><path fill="#9ccc65" d="M14.8 46.18L82.31 34.9l29.49 32.47c1.49 1.57.68 4.17-1.44 4.6l-69.7 14.3z"/><path fill="#689f38" d="M110.36 69.17L41.14 83.19l-.22 3.3l69.44-14.24c1.96-.41 2.78-2.65 1.71-4.23c-.38.56-.96 1-1.71 1.15m3.73 15.13c.73 1.16.07 2.69-1.27 2.96L49.1 100.18c-3.83.79-7.59-1.72-7.93-5.62c-.29-3.3 1.94-6.29 5.19-6.97l61.28-13.76z"/><path fill="#616161" d="M55.59 80.1L30.21 43.78l-14.48 3.83c-3.35 3.33-2.1 8.8-2.1 8.8S35.8 91.99 39.3 96.54s8.61 3.84 8.61 3.84l8.63-1.74l-.9-16.1z"/><path fill="#424242" d="M55.59 80.34L43.4 82.86c-3.33.75-3.93 3.88-3.93 3.88L10.04 44.57s-4.19 5.07-1.41 9.38L39.3 96.54c3.35 4.77 8.61 3.88 8.61 3.88l8.63-1.74l-.89-15.78z"/><path fill="#b9e4ea" d="M110.25 83c.31.68-.09 1.47-.82 1.62L48.5 97.12c-3.83.79-6.54-1.75-6.4-5.21c.18-4.37 2.63-6.22 5.87-6.89l61.23-12.51s-2.08 2.34-.49 6.72c.54 1.51 1.12 2.85 1.54 3.77"/><path fill="none" stroke="#424242" stroke-miterlimit="10" stroke-width="2.071" d="M45.21 83.7L19.1 46.76"/><path fill="#424242" d="M47.26 67.95L13.68 51.03l-1.36 2.68l38.8 19.77z"/><path fill="#689f38" d="m108.79 64.03l-2.46-2.7L68.5 78.69L47.26 68.18l3.62 5.18l14.07 7.19l10.48-1.61z"/><path fill="#c62828" d="M118.02 57.35L72.29 78.03c-2.82 1.33-5.76 1.2-8.46-.36L6.09 48.35c-1.65-1.06-2.14-2.61-2.04-3.69s.35-2.25 3.25-3.09l2.71-1l59.32 29.11l48.17-19.93z"/><path fill="#f5f5f5" d="M71.73 74.72a7.95 7.95 0 0 1-7.26-.26L8.1 46.06c-.8-.44-1.04-1.45-.56-2.23c1.24-2.05 3.52-8.53-.24-13.91l62.24 31.66z"/><path fill="#94c6d6" d="M115.58 55.01L71.73 74.72c-1.45.63-4.34 1.75-7.67-.49c2.63.19 4.48-.9 5.43-2.67c.93-1.72.65-4.54-.48-6.13c-.69-.96-2.54-2.49-3.35-3.35l47.43-18.55c4.2-1.73 8.14.86 8.77 4.01c.7 3.56-3.84 6.47-6.28 7.47"/><path fill="#c62828" d="m117.78 41.99l-45.27 20.2c-2.85 1.13-6.04.98-8.77-.4L5.89 32.41c-.6-.3-1.5-1.07-1.79-2.16c-.43-1.62.13-3.75 2.26-4.59l53.01-11.23z"/><path fill="#f44336" d="m121.18 44.92l-4.93-1.79l-10.16.59l-33.58 14.99c-2.85 1.13-6.04.98-8.77-.4L5.89 28.93c-1.49-.76-.96-2.77.47-3.28l41.7-18.64c1.19-.22 2.41-.09 3.52.38l59.49 28.36s9.44 6.46 10.11 9.17"/><path fill="#616161" d="M105.53 44s5.21-1.83 10.13-1.61s7.69 3.05 7.69 3.05c-1.01-4.52-5.3-6.39-5.3-6.39l-65-32.73c-.45-.19-2.11-.58-4.66.47c-2.06.85-8.79 4-8.79 4z"/><path fill="#424242" d="M111.48 41.86L44.97 8.31l2.2-.99l67.64 33.9z"/><path fill="#424242" d="M123.61 46.25c-.47-1.87-1.26-3.68-3.49-4.62c-2.85-1.2-5.45-1.45-9.69.18l-4.91 2.18v3.46l6.21-2.76c3.15-1.48 7.79-1.16 8.95 2.29c1.27 3.78-.9 6.11-6.39 8.63l-8.92 4.02v3.48l10.26-4.57c4.55-1.82 9.73-5.24 7.98-12.29"/></svg>
                          <h3 class="text-xl font-bold mb-2">Check Books</h3>
                            <p> <a href="library-books.php" class="text-primary hover:text-secondary">Click Here</a></p>
                     </div>
                </div>


             <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/2 px-4 mb-8">
                 <div class="bg-neutral rounded-md shadow-md text-center  flex flex-col items-center border border-blue-200">
                 <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 1024 1024"><path fill="#0ce0ff" d="m960 95.888l-256.224.001V32.113c0-17.68-14.32-32-32-32s-32 14.32-32 32v63.76h-256v-63.76c0-17.68-14.32-32-32-32s-32 14.32-32 32v63.76H64c-35.344 0-64 28.656-64 64v800c0 35.343 28.656 64 64 64h896c35.344 0 64-28.657 64-64v-800c0-35.329-28.656-63.985-64-63.985m0 863.985H64v-800h255.776v32.24c0 17.679 14.32 32 32 32s32-14.321 32-32v-32.224h256v32.24c0 17.68 14.32 32 32 32s32-14.32 32-32v-32.24H960zM736 511.888h64c17.664 0 32-14.336 32-32v-64c0-17.664-14.336-32-32-32h-64c-17.664 0-32 14.336-32 32v64c0 17.664 14.336 32 32 32m0 255.984h64c17.664 0 32-14.32 32-32v-64c0-17.664-14.336-32-32-32h-64c-17.664 0-32 14.336-32 32v64c0 17.696 14.336 32 32 32m-192-128h-64c-17.664 0-32 14.336-32 32v64c0 17.68 14.336 32 32 32h64c17.664 0 32-14.32 32-32v-64c0-17.648-14.336-32-32-32m0-255.984h-64c-17.664 0-32 14.336-32 32v64c0 17.664 14.336 32 32 32h64c17.664 0 32-14.336 32-32v-64c0-17.68-14.336-32-32-32m-256 0h-64c-17.664 0-32 14.336-32 32v64c0 17.664 14.336 32 32 32h64c17.664 0 32-14.336 32-32v-64c0-17.68-14.336-32-32-32m0 255.984h-64c-17.664 0-32 14.336-32 32v64c0 17.68 14.336 32 32 32h64c17.664 0 32-14.32 32-32v-64c0-17.648-14.336-32-32-32"/></svg>
                      <h3 class="text-xl font-bold mb-2">My Due Dates</h3>
                     <?php
                        $sql = "SELECT b.BookName, i.ReturnDate
                                FROM tblissuedbookdetails i
                                JOIN tblbooks b ON b.id = i.BookId
                                WHERE i.StudentID = :sid AND i.RetrunStatus = 0";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':sid', $sid, PDO::PARAM_STR);
                        $query->execute();
                        $dueDates = $query->fetchAll(PDO::FETCH_OBJ);
                    ?>
                    <ul class="text-left">
                        <?php
                            if ($dueDates && count($dueDates) > 0) {
                                foreach ($dueDates as $dueDate) {
                                    ?>
                                    <li class="mb-2"><?php echo htmlentities($dueDate->BookName); ?> - <?php echo htmlentities($dueDate->ReturnDate); ?></li>
                                    <?php
                                }
                            } else {
                                ?>
                                <li class="mb-2">No books are due.</li>
                            <?php
                            }
                            ?>
                        </ul>
                 </div>
                </div>

              <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 px-4 mb-8">
                 <div class="bg-neutral rounded-md shadow-md text-center h-48 flex flex-col items-center justify-center border border-blue-200">
                 <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"><path fill="#0ce0ff" d="M15 7v12.97l-4.21-1.81l-.79-.34l-.79.34L5 19.97V7zm4-6H8.99C7.89 1 7 1.9 7 3h10c1.1 0 2 .9 2 2v13l2 1V3c0-1.1-.9-2-2-2m-4 4H5c-1.1 0-2 .9-2 2v16l7-3l7 3V7c0-1.1-.9-2-2-2"/></svg>
                      <h3 class="text-xl font-bold mb-2">My Reservations</h3>
                    <p> <a href="my-reservations.php" class="text-primary hover:text-secondary">Click Here</a></p>
                   </div>
                </div>

        </div>
    </div>

     <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
<?php } ?>