<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])==0)
  {
header('location:index.php');
}
else{?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <!--[if IE]>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <![endif]-->
    <title>Smart Library Management System | LIBRARIAN DASHBOARD</title>
    <!-- BOOTSTRAP CORE STYLE  -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE  -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLE  -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
     <style>
        .tile-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
             margin-bottom: 20px;
        }
        .tile {
            background-color: #f9f9f9;
            padding: 20px;
            margin: 10px;
            text-align: center;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 300px; /* Fixed width for better layout */
        }
        .tile h3{
            margin-top: 10px;
        }
         .tile i, .tile svg{
            margin-bottom: 10px;
        }
        .svg-5x {
            width: 128px;
            height: 128px;
          }
    </style>

</head>
<body>
      <!------MENU SECTION START-->
<?php include('includes/header.php');?>
<!-- MENU SECTION END-->
    <div class="content-wrapper">
         <div class="container">
        <div class="row pad-botm">
            <div class="col-md-12">
                <h4 class="header-line">LIBRARIAN DASHBOARD</h4>

                            </div>

        </div>

             <div class="row">
                <div class="tile-container">
                     <div class="tile">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 128 128"><path fill="#01579b" d="M118.03 102.32L72.29 123c-2.82 1.33-5.76 1.2-8.46-.36L6.09 93.32c-1.65-1.06-2.14-2.61-2.04-3.69s.35-2.25 3.25-3.09l4.28-1.58l57.92 31.57l41.16-16.82z"/><path fill="#f5f5f5" d="M71.74 119.69a7.95 7.95 0 0 1-7.26-.26L8.11 91.03c-.8-.44-1.04-1.45-.56-2.23c1.24-2.05 3.52-8.53-.24-13.91l63.66 30.65z"/><path fill="#94c6d6" d="m115.59 99.98l-43.85 19.71c-1.45.63-4.34 1.75-7.67-.49c2.63.19 4.48-.9 5.43-2.67c.93-1.72.65-4.54-.48-6.13c-.69-.96-2.54-2.49-3.35-3.35L113.1 88.5c4.2-1.73 8.14.86 8.77 4.01c.7 3.56-3.84 6.47-6.28 7.47"/><path fill="#01579b" d="m117.78 86.96l-45.27 20.2c-2.85 1.13-6.04.98-8.77-.4L5.9 77.38c-.56-.28-1.39-1.05-1.72-2.1c-.54-1.75.14-3.95 2.19-4.65l62.68 31.95l42.92-18.37z"/><path fill="#0091ea" d="m121.19 89.89l-4.93-1.79l-10.16.59l-33.58 14.99c-2.85 1.13-6.04.98-8.77-.4L5.9 73.91c-1.49-.76-1.17-2.97.47-3.28l41.69-18.65c1.19-.22 2.41-.09 3.52.38l59.49 28.36s9.45 6.47 10.12 9.17"/><path fill="#616161" d="M105.53 88.98s6.26-2.45 11.18-2.23s6.63 3.67 6.63 3.67c-.93-4.23-5.3-6.39-5.3-6.39l-65-32.73c-.45-.19-2.11-.58-4.66.47c-2.06.85-8.79 4-8.79 4z"/><path fill="#424242" d="M123.62 91.22c-.47-1.87-1.63-3.87-3.77-4.84c-2.82-1.27-6.84-.94-9.41.4l-4.91 2.18v3.46l6.21-2.76c6.04-2.69 8.72 1.34 8.95 2.29c.96 3.87-.9 6.11-6.39 8.63l-8.92 4.02v3.48l10.26-4.57c4.54-1.82 9.72-5.24 7.98-12.29"/><path fill="#01579b" d="M33.01 90.31L15.74 66.44l2.71-1.21l19.43 26.7zm22.15 11l-3.08-2.44l53.45-10.91v1.75l-7.49 2.84z"/><path fill="#9ccc65" d="M14.8 46.18L82.31 34.9l29.49 32.47c1.49 1.57.68 4.17-1.44 4.6l-69.7 14.3z"/><path fill="#689f38" d="M110.36 69.17L41.14 83.19l-.22 3.3l69.44-14.24c1.96-.41 2.78-2.65 1.71-4.23c-.38.56-.96 1-1.71 1.15m3.73 15.13c.73 1.16.07 2.69-1.27 2.96L49.1 100.18c-3.83.79-7.59-1.72-7.93-5.62c-.29-3.3 1.94-6.29 5.19-6.97l61.28-13.76z"/><path fill="#616161" d="M55.59 80.1L30.21 43.78l-14.48 3.83c-3.35 3.33-2.1 8.8-2.1 8.8S35.8 91.99 39.3 96.54s8.61 3.84 8.61 3.84l8.63-1.74l-.9-16.1z"/><path fill="#424242" d="M55.59 80.34L43.4 82.86c-3.33.75-3.93 3.88-3.93 3.88L10.04 44.57s-4.19 5.07-1.41 9.38L39.3 96.54c3.35 4.77 8.61 3.88 8.61 3.88l8.63-1.74l-.89-15.78z"/><path fill="#b9e4ea" d="M110.25 83c.31.68-.09 1.47-.82 1.62L48.5 97.12c-3.83.79-6.54-1.75-6.4-5.21c.18-4.37 2.63-6.22 5.87-6.89l61.23-12.51s-2.08 2.34-.49 6.72c.54 1.51 1.12 2.85 1.54 3.77"/><path fill="none" stroke="#424242" stroke-miterlimit="10" stroke-width="2.071" d="M45.21 83.7L19.1 46.76"/><path fill="#424242" d="M47.26 67.95L13.68 51.03l-1.36 2.68l38.8 19.77z"/><path fill="#689f38" d="m108.79 64.03l-2.46-2.7L68.5 78.69L47.26 68.18l3.62 5.18l14.07 7.19l10.48-1.61z"/><path fill="#c62828" d="M118.02 57.35L72.29 78.03c-2.82 1.33-5.76 1.2-8.46-.36L6.09 48.35c-1.65-1.06-2.14-2.61-2.04-3.69s.35-2.25 3.25-3.09l2.71-1l59.32 29.11l48.17-19.93z"/><path fill="#f5f5f5" d="M71.73 74.72a7.95 7.95 0 0 1-7.26-.26L8.1 46.06c-.8-.44-1.04-1.45-.56-2.23c1.24-2.05 3.52-8.53-.24-13.91l62.24 31.66z"/><path fill="#94c6d6" d="M115.58 55.01L71.73 74.72c-1.45.63-4.34 1.75-7.67-.49c2.63.19 4.48-.9 5.43-2.67c.93-1.72.65-4.54-.48-6.13c-.69-.96-2.54-2.49-3.35-3.35l47.43-18.55c4.2-1.73 8.14.86 8.77 4.01c.7 3.56-3.84 6.47-6.28 7.47"/><path fill="#c62828" d="m117.78 41.99l-45.27 20.2c-2.85 1.13-6.04.98-8.77-.4L5.89 32.41c-.6-.3-1.5-1.07-1.79-2.16c-.43-1.62.13-3.75 2.26-4.59l53.01-11.23z"/><path fill="#f44336" d="m121.18 44.92l-4.93-1.79l-10.16.59l-33.58 14.99c-2.85 1.13-6.04.98-8.77-.4L5.89 28.93c-1.49-.76-.96-2.77.47-3.28l41.7-18.64c1.19-.22 2.41-.09 3.52.38l59.49 28.36s9.44 6.46 10.11 9.17"/><path fill="#616161" d="M105.53 44s5.21-1.83 10.13-1.61s7.69 3.05 7.69 3.05c-1.01-4.52-5.3-6.39-5.3-6.39l-65-32.73c-.45-.19-2.11-.58-4.66.47c-2.06.85-8.79 4-8.79 4z"/><path fill="#424242" d="M111.48 41.86L44.97 8.31l2.2-.99l67.64 33.9z"/><path fill="#424242" d="M123.61 46.25c-.47-1.87-1.26-3.68-3.49-4.62c-2.85-1.2-5.45-1.45-9.69.18l-4.91 2.18v3.46l6.21-2.76c3.15-1.48 7.79-1.16 8.95 2.29c1.27 3.78-.9 6.11-6.39 8.63l-8.92 4.02v3.48l10.26-4.57c4.55-1.82 9.73-5.24 7.98-12.29"/></svg>
                            <?php
                            $sql ="SELECT id from tblbooks ";
                            $query = $dbh -> prepare($sql);
                            $query->execute();
                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                            $listdbooks=$query->rowCount();
                            ?>
                            <h3><?php echo htmlentities($listdbooks);?></h3>
                            Books Listed
                    </div>

                    <div class="tile">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 32 32"><g fill="none"><path fill="#00a6ed" d="M17.045 27.286H30V13a2 2 0 0 0-2-2H17.045z"/><path fill="#d3d3d3" d="M15.682 27.964H30v1.357H15.682z"/><path fill="#0074ba" d="M16.023 11A1.02 1.02 0 0 0 15 12.018v16.625h.682a.68.68 0 0 1 .682-.679h.681V11z"/><path fill="#0074ba" d="M16.023 27.286A1.02 1.02 0 0 0 15 28.304v.678A1.02 1.02 0 0 0 16.023 30h12.954c.446 0 .824-.283.965-.678H16.364a.68.68 0 0 1-.682-.68a.68.68 0 0 1 .682-.678H30v-.678z"/><path fill="#ca0b4a" d="M10.045 23.286H23V9a2 2 0 0 0-2-2H10.045z"/><path fill="#d3d3d3" d="M8.682 23.964H23v1.357H8.682z"/><path fill="#990838" d="M9.023 7A1.02 1.02 0 0 0 8 8.018v16.625h.682a.68.68 0 0 1 .682-.679h.681V7z"/><path fill="#990838" d="M9.023 23.286A1.02 1.02 0 0 0 8 24.304v.678A1.02 1.02 0 0 0 9.023 26h12.954c.446 0 .824-.283.965-.678H9.364a.68.68 0 0 1-.682-.68a.68.68 0 0 1 .682-.678H23v-.678z"/><path fill="#86d72f" d="M4.045 20.286H17V6a2 2 0 0 0-2-2H4.045z"/><path fill="#d3d3d3" d="M2.682 20.964H17v1.357H2.682z"/><path fill="#44911b" d="M3.023 4A1.02 1.02 0 0 0 2 5.018v16.625h.682a.68.68 0 0 1 .682-.679h.681V4z"/><path fill="#008463" d="M3.023 20.286A1.02 1.02 0 0 0 2 21.304v.678A1.02 1.02 0 0 0 3.023 23h12.954c.446 0 .824-.283.965-.678H3.364a.68.68 0 0 1-.682-.68a.68.68 0 0 1 .682-.678H17v-.678z"/></g></svg>
                            <?php
                            $sql1 ="SELECT id from tblissuedbookdetails ";
                            $query1 = $dbh -> prepare($sql1);
                            $query1->execute();
                            $results1=$query1->fetchAll(PDO::FETCH_OBJ);
                            $issuedbooks=$query1->rowCount();
                            ?>
                            <h3><?php echo htmlentities($issuedbooks);?> </h3>
                           Times Book Issued
                    </div>

                   <div class="tile">
                   <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 32 32"><path fill="#00fffc" d="m22 27.18l-2.59-2.59L18 26l4 4l8-8l-1.41-1.41z"/><path fill="#00fffc" d="M25 5h-3V4a2.006 2.006 0 0 0-2-2h-8a2.006 2.006 0 0 0-2 2v1H7a2.006 2.006 0 0 0-2 2v21a2.006 2.006 0 0 0 2 2h9v-2H7V7h3v3h12V7h3v11h2V7a2.006 2.006 0 0 0-2-2m-5 3h-8V4h8Z"/></svg>
                            <?php
                            $status=1;
                            $sql2 ="SELECT id from tblissuedbookdetails where RetrunStatus=:status";
                            $query2 = $dbh -> prepare($sql2);
                            $query2->bindParam(':status',$status,PDO::PARAM_STR);
                            $query2->execute();
                            $results2=$query2->fetchAll(PDO::FETCH_OBJ);
                            $returnedbooks=$query2->rowCount();
                            ?>

                            <h3><?php echo htmlentities($returnedbooks);?></h3>
                         Times  Books Returned
                    </div>
                    <div class="tile">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 128 128"><path fill="#e59600" d="M73.76 89.08H54.23v19.33c0 4.85 3.98 8.78 8.88 8.78h1.77c4.9 0 8.88-3.93 8.88-8.78zm17.57-38.67H36.67c-5.89 0-10.71 5.14-10.71 11.41c0 6.28 4.82 11.41 10.71 11.41h54.65c5.89 0 10.71-5.14 10.71-11.41c.01-6.27-4.81-11.41-10.7-11.41"/><path fill="#ffca28" d="M64 11.05c-17.4 0-33.52 18.61-33.52 45.39c0 26.64 16.61 39.81 33.52 39.81s33.52-13.17 33.52-39.81c0-26.78-16.12-45.39-33.52-45.39"/><g fill="#404040"><ellipse cx="47.56" cy="58.79" rx="4.93" ry="5.1"/><ellipse cx="80.44" cy="58.79" rx="4.93" ry="5.1"/></g><path fill="#e59600" d="M67.86 68.04c-.11-.04-.21-.07-.32-.08h-7.07c-.11.01-.22.04-.32.08c-.64.26-.99.92-.69 1.63s1.71 2.69 4.55 2.69s4.25-1.99 4.55-2.69c.29-.71-.06-1.37-.7-1.63"/><path fill="#795548" d="M72.42 76.12c-3.19 1.89-13.63 1.89-16.81 0c-1.83-1.09-3.7.58-2.94 2.24c.75 1.63 6.45 5.42 11.37 5.42s10.55-3.79 11.3-5.42c.75-1.66-1.09-3.33-2.92-2.24"/><path fill="#543930" d="M64.02 5.03h-.04c-45.44.24-36.13 50.14-36.13 50.14s2.04 5.35 2.97 7.71c.13.34.63.3.71-.05c.97-4.34 4.46-19.73 6.22-24.41a6.075 6.075 0 0 1 6.79-3.83c4.46.81 11.55 1.81 19.38 1.81h.16c7.82 0 14.92-1 19.37-1.81c2.9-.53 5.76 1.08 6.79 3.83c1.75 4.66 5.22 19.96 6.2 24.36c.08.36.58.39.71.05l2.98-7.67c.02.01 9.32-49.89-36.11-50.13"/><radialGradient id="notoManStudent0" cx="64.001" cy="81.221" r="37.873" gradientTransform="matrix(1 0 0 -1.1282 0 138.298)" gradientUnits="userSpaceOnUse"><stop offset=".794" stop-color="#6d4c41" stop-opacity="0"/><stop offset="1" stop-color="#6d4c41"/></radialGradient><path fill="url(#notoManStudent0)" d="M100.15 55.17s9.31-49.9-36.13-50.14h-.04c-.71 0-1.4.02-2.08.05c-1.35.06-2.66.16-3.92.31h-.04c-.09.01-.17.03-.26.04c-38.25 4.81-29.84 49.74-29.84 49.74l2.98 7.68c.13.34.62.31.7-.05c.98-4.39 4.46-19.71 6.22-24.37a6.08 6.08 0 0 1 6.8-3.83c4.46.8 11.55 1.8 19.38 1.8h.16c7.82 0 14.92-1 19.37-1.81c2.9-.53 5.76 1.08 6.79 3.83c1.76 4.68 5.25 20.1 6.21 24.42c.08.36.57.39.7.05c.94-2.35 3-7.72 3-7.72"/><path fill="#e8ad00" d="M116.5 54.28c-1.24 0-2.25.96-2.25 2.14v9.2c0 1.18 1.01 2.14 2.25 2.14s2.25-.96 2.25-2.14v-9.2c0-1.18-1.01-2.14-2.25-2.14m-4.5 0c-1.24 0-2.25.96-2.25 2.14v9.2c0 1.18 1.01 2.14 2.25 2.14s2.25-.96 2.25-2.14v-9.2c0-1.18-1.01-2.14-2.25-2.14"/><path fill="#ffca28" d="M114.25 54.28c-1.24 0-2.25.96-2.25 2.14v11.19c0 1.18 1.01 2.14 2.25 2.14s2.25-.96 2.25-2.14V56.42c0-1.18-1.01-2.14-2.25-2.14"/><ellipse cx="114.25" cy="53.05" fill="#ffca28" rx="2.76" ry="2.63"/><path fill="#504f4f" d="M114.25 53.02c-.55 0-1-.45-1-1v-38c0-.55.45-1 1-1s1 .45 1 1v38c0 .56-.45 1-1 1"/><linearGradient id="notoManStudent1" x1="64" x2="64" y1="127.351" y2="98.71" gradientTransform="matrix(1 0 0 -1 0 128)" gradientUnits="userSpaceOnUse"><stop offset=".003" stop-color="#424242"/><stop offset=".472" stop-color="#353535"/><stop offset="1" stop-color="#212121"/></linearGradient><path fill="url(#notoManStudent1)" d="M116 12.98c-30.83-7.75-52-8-52-8s-21.17.25-52 8v.77c0 1.33.87 2.5 2.14 2.87c3.72 1.1 13.13 3.53 18.18 4.54c-.08.08-1.1 1.87-1.83 3.53c0 0 8.14 5.72 33.52 8.28c25.38-2.56 33.76-7.58 33.76-7.58c-.88-1.81-1.79-3.49-1.79-3.49c4.5-.74 14.23-4.07 17.95-5.26c1.25-.4 2.09-1.55 2.09-2.86v-.8z"/><linearGradient id="notoManStudent2" x1="64" x2="64" y1="127.184" y2="96.184" gradientTransform="matrix(1 0 0 -1 0 128)" gradientUnits="userSpaceOnUse"><stop offset=".003" stop-color="#616161"/><stop offset=".324" stop-color="#505050"/><stop offset=".955" stop-color="#242424"/><stop offset="1" stop-color="#212121"/></linearGradient><path fill="url(#notoManStudent2)" d="M64 4.98s-21.17.25-52 8c0 0 35.41 9.67 52 9.38c16.59.29 52-9.38 52-9.38c-30.83-7.75-52-8-52-8"/><linearGradient id="notoManStudent3" x1="13.893" x2="114.721" y1="109.017" y2="109.017" gradientTransform="matrix(1 0 0 -1 0 128)" gradientUnits="userSpaceOnUse"><stop offset=".001" stop-color="#bfbebe"/><stop offset=".3" stop-color="#212121" stop-opacity="0"/><stop offset=".7" stop-color="#212121" stop-opacity="0"/><stop offset="1" stop-color="#bfbebe"/></linearGradient><path fill="url(#notoManStudent3)" d="M116 12.98c-30.83-7.75-52-8-52-8s-21.17.25-52 8v.77c0 1.33.87 2.5 2.14 2.87c3.72 1.1 13.13 3.69 18.18 4.71c0 0-.96 1.56-1.83 3.53c0 0 8.14 5.55 33.52 8.12c25.38-2.56 33.76-7.58 33.76-7.58c-.88-1.81-1.79-3.49-1.79-3.49c4.5-.74 14.23-4.07 17.95-5.26c1.25-.4 2.09-1.55 2.09-2.86v-.81z" opacity="0.4"/><path fill="#6d4c41" d="M40.01 50.72c2.99-4.23 9.78-4.63 13.67-1.48c.62.5 1.44 1.2 1.68 1.98c.4 1.27-.82 2.26-2.01 1.96c-.76-.19-1.47-.6-2.22-.83c-1.37-.43-2.36-.55-3.59-.55c-1.82-.01-2.99.22-4.72.92c-.71.29-1.29.75-2.1.41c-.93-.39-1.27-1.57-.71-2.41m46.06 2.4c-.29-.13-.57-.29-.86-.41c-1.78-.74-2.79-.93-4.72-.92c-1.7.01-2.71.24-4.04.69c-.81.28-1.84.98-2.74.71c-1.32-.4-1.28-1.84-.56-2.76c.86-1.08 2.04-1.9 3.29-2.44c2.9-1.26 6.44-1.08 9.17.55c.89.53 1.86 1.26 2.4 2.18c.78 1.31-.4 3.03-1.94 2.4"/><path fill="#212121" d="M114.5 120.99c0-14.61-21.75-21.54-40.72-23.1l-8.6 11.03c-.28.36-.72.58-1.18.58s-.9-.21-1.18-.58L54.2 97.87c-10.55.81-40.71 4.75-40.71 23.12V124h101z"/><radialGradient id="notoManStudent4" cx="64" cy="5.397" r="54.167" gradientTransform="matrix(1 0 0 -.5247 0 125.435)" gradientUnits="userSpaceOnUse"><stop offset=".598" stop-color="#212121"/><stop offset="1" stop-color="#616161"/></radialGradient><path fill="url(#notoManStudent4)" d="M114.5 120.99c0-14.61-21.75-21.54-40.72-23.1l-8.6 11.03c-.28.36-.72.58-1.18.58s-.9-.21-1.18-.58L54.2 97.87c-10.55.81-40.71 4.75-40.71 23.12V124h101z"/></svg>
                           <?php
                            $sql3 ="SELECT id from tblstudents ";
                            $query3 = $dbh -> prepare($sql3);
                            $query3->execute();
                            $results3=$query3->fetchAll(PDO::FETCH_OBJ);
                            $regstds=$query3->rowCount();
                            ?>
                            <h3><?php echo htmlentities($regstds);?></h3>
                            Registered Users
                    </div>
                    <div class="tile">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"><path fill="#00fffc" d="M12 14.27L10.64 13A11.24 11.24 0 0 0 5 10.18v6.95c2.61.34 5 1.34 7 2.82c2-1.48 4.39-2.48 7-2.82v-6.95c-2.16.39-4.09 1.39-5.64 2.82M19 8.15c.65-.1 1.32-.15 2-.15v11c-3.5 0-6.64 1.35-9 3.54C9.64 20.35 6.5 19 3 19V8c.68 0 1.35.05 2 .15c2.69.41 5.1 1.63 7 3.39c1.9-1.76 4.31-2.98 7-3.39M12 6c.27 0 .5-.1.71-.29c.19-.21.29-.44.29-.71s-.1-.5-.29-.71C12.5 4.11 12.27 4 12 4s-.5.11-.71.29c-.18.21-.29.45-.29.71s.11.5.29.71c.21.19.45.29.71.29m2.12 1.12a2.997 2.997 0 1 1-4.24-4.24a2.997 2.997 0 1 1 4.24 4.24"/></svg>
                            <?php
                            $sql4 ="SELECT id from tblauthors ";
                            $query4 = $dbh -> prepare($sql4);
                            $query4->execute();
                            $results4=$query4->fetchAll(PDO::FETCH_OBJ);
                            $listdathrs=$query4->rowCount();
                            ?>
                            <h3><?php echo htmlentities($listdathrs);?></h3>
                           Authors Listed
                     </div>
                     <div class="tile">
                     <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 36 36"><path fill="#553788" d="M15 31c0 2.209-.791 4-3 4H5c-4 0-4-14 0-14h7c2.209 0 3 1.791 3 4z"/><path fill="#9266cc" d="M34 33h-1V23h1a1 1 0 1 0 0-2H10c-4 0-4 14 0 14h24a1 1 0 1 0 0-2"/><path fill="#ccd6dd" d="M34.172 33H11c-2 0-2-10 0-10h23.172c1.104 0 1.104 10 0 10"/><path fill="#99aab5" d="M11.5 25h23.35c-.135-1.175-.36-2-.678-2H11c-1.651 0-1.938 6.808-.863 9.188C9.745 29.229 10.199 25 11.5 25"/><path fill="#269" d="M12 8a4 4 0 0 1-4 4H4C0 12 0 1 4 1h4a4 4 0 0 1 4 4z"/><path fill="#55acee" d="M31 10h-1V3h1a1 1 0 1 0 0-2H7C3 1 3 12 7 12h24a1 1 0 1 0 0-2"/><path fill="#ccd6dd" d="M31.172 10H8c-2 0-2-7 0-7h23.172c1.104 0 1.104 7 0 7"/><path fill="#99aab5" d="M8 5h23.925c-.114-1.125-.364-2-.753-2H8C6.807 3 6.331 5.489 6.562 7.5C6.718 6.142 7.193 5 8 5"/><path fill="#f4900c" d="M20 17a4 4 0 0 1-4 4H6c-4 0-4-9 0-9h10a4 4 0 0 1 4 4z"/><path fill="#ffac33" d="M35 19h-1v-5h1a1 1 0 1 0 0-2H15c-4 0-4 9 0 9h20a1 1 0 1 0 0-2"/><path fill="#ccd6dd" d="M35.172 19H16c-2 0-2-5 0-5h19.172c1.104 0 1.104 5 0 5"/><path fill="#99aab5" d="M16 16h19.984c-.065-1.062-.334-2-.812-2H16c-1.274 0-1.733 2.027-1.383 3.5c.198-.839.657-1.5 1.383-1.5"/></svg>
                            <?php
                            $sql5 ="SELECT id from tblcategory ";
                            $query5 = $dbh -> prepare($sql5);
                            $query5->execute();
                            $results5=$query5->fetchAll(PDO::FETCH_OBJ);
                            $listdcats=$query5->rowCount();
                            ?>
                            <h3><?php echo htmlentities($listdcats);?> </h3>
                            Listed Categories
                     </div>

                    <div class="tile">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"><path fill="#0ce0ff" d="M15 7v12.97l-4.21-1.81l-.79-.34l-.79.34L5 19.97V7zm4-6H8.99C7.89 1 7 1.9 7 3h10c1.1 0 2 .9 2 2v13l2 1V3c0-1.1-.9-2-2-2m-4 4H5c-1.1 0-2 .9-2 2v16l7-3l7 3V7c0-1.1-.9-2-2-2"/></svg>
                        <h3>Manage Reservations</h3>
                         <p> <a href="librarian-reservations.php">Click Here</a></p>
                    </div>
                </div>
        </div>
           <div class="row">
              <div class="col-md-10 col-sm-8 col-xs-12 col-md-offset-1">
                    <div id="carousel-example" class="carousel slide slide-bdr" data-ride="carousel" >
                    <div class="carousel-inner">
                        <div class="item active">

                            <img src="assets/img/1.jpg" alt="" />

                        </div>
                        <div class="item">
                            <img src="assets/img/2.jpg" alt="" />

                        </div>
                        <div class="item">
                            <img src="assets/img/3.jpg" alt="" />

                        </div>
                    </div>
                    <!--INDICATORS-->
                     <ol class="carousel-indicators">
                        <li data-target="#carousel-example" data-slide-to="0" class="active"></li>
                        <li data-target="#carousel-example" data-slide-to="1"></li>
                        <li data-target="#carousel-example" data-slide-to="2"></li>
                    </ol>
                    <!--PREVIUS-NEXT BUTTONS-->
                     <a class="left carousel-control" href="#carousel-example" data-slide="prev">
                        <span class="glyphicon glyphicon-chevron-left"></span>
                     </a>
                     <a class="right carousel-control" href="#carousel-example" data-slide="next">
                        <span class="glyphicon glyphicon-chevron-right"></span>
                     </a>
                </div>
              </div>
           </div>
    </div>
    </div>
     <!-- CONTENT-WRAPPER SECTION END-->
<?php include('includes/footer.php');?>
      <!-- FOOTER SECTION END-->
    <!-- JAVASCRIPT FILES PLACED AT THE BOTTOM TO REDUCE THE LOADING TIME  -->
    <!-- CORE JQUERY  -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- BOOTSTRAP SCRIPTS  -->
    <script src="assets/js/bootstrap.js"></script>
      <!-- CUSTOM SCRIPTS  -->
    <script src="assets/js/custom.js"></script>
</body>
</html>
<?php } ?>