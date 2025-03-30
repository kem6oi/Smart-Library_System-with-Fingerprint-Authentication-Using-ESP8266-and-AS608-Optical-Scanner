<?php
    include_once('includes/init.php');

    function generateVerificationCode() {
        return rand(100000, 999999);
    }

    function sendTelegramMessage($chatId, $message, $botToken) {
        $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage?chat_id=" . $chatId . "&text=" . urlencode($message);
        file_get_contents($url);
    }
    if(isset($_POST['signup'])) {
        $fullName = $_POST['fullname'];
        $email = $_POST['email'];
        $password = md5($_POST['password']);
        $telegramChatId = $_POST['telegram_chat_id'];

        $count_my_page = "studentid.txt";
        $hits = file($count_my_page);
        $hits[0]++;
        $fp = fopen($count_my_page , "w");
        fputs($fp , "$hits[0]");
        fclose($fp);
        $studentId =  $hits[0];

        $verificationCode = generateVerificationCode();

        $botToken = '7304654930:AAF2Q_is81qMPx210n-hz1DkEYVuILPQfKA';
        $message = "Your verification code is: " . $verificationCode;
        sendTelegramMessage($telegramChatId, $message, $botToken);

        $sql = "INSERT INTO auth_codes (code, student_id) VALUES (:code, :student_id)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':code', $verificationCode, PDO::PARAM_STR);
        $query->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        $query->execute();

        $_SESSION['signup_details'] = [
            'studentId' => $studentId,
            'fullName' => $fullName,
            'email' => $email,
            'password' => $password,
            'telegramChatId' => $telegramChatId
        ];

        $_SESSION['signup_verification_required'] = true;
        $_SESSION['signup_msg']="Verification code has been sent, please check telegram and verify";
        header('location:signup-verification.php');
    }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Smart Library Management System | Student Signup</title>
    <link href="assets/css/style.css" rel="stylesheet" />
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript">
        function valid() {
            let password = document.signup.password.value;
            let confirmPassword = document.signup.confirmpassword.value;

            if (password !== confirmPassword) {
                alert("Password and Confirm Password fields do not match!");
                document.signup.confirmpassword.focus();
                return false;
            }
            return true;
        }

        function checkPasswordStrength() {
            let password = document.signup.password.value;
            let strength = 0;
            let strengthText = "";

            if (password.length > 8) {
                strength++;
            }
            if (password.match(/[a-z]+/)) {
                strength++;
            }
            if (password.match(/[A-Z]+/)) {
                strength++;
            }
            if (password.match(/[0-9]+/)) {
                strength++;
            }
            if (password.match(/[^a-zA-Z0-9]+/)) {
                strength++;
            }

            switch (strength) {
                case 0:
                    strengthText = "Very Weak";
                    break;
                case 1:
                case 2:
                    strengthText = "Weak";
                    break;
                case 3:
                    strengthText = "Medium";
                    break;
                case 4:
                case 5:
                    strengthText = "Strong";
                    break;
            }

            document.getElementById("password-strength").textContent = "Password Strength: " + strengthText;
        }

        function checkEmptyEmail(){
            let email = document.signup.email.value;
            if(email == ""){
                alert("Email field must not be empty");
                return false;
            }
            return true;
        }

        function togglePasswordVisibility(id) {
            let passwordField = document.signup[id];
            let toggleIcon = document.getElementById(id+'-toggle-icon');
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = "password";
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function updateProgressBar() {
            let totalFields = 5; 
            let filledFields = 0;

            if (document.signup.fullname.value) filledFields++;
            if (document.signup.email.value) filledFields++;
            if (document.signup.password.value) filledFields++;
            if (document.signup.confirmpassword.value) filledFields++;
            if (document.signup.telegram_chat_id.value) filledFields++;

            let progressPercentage = (filledFields / totalFields) * 100;
            let progressBar = document.getElementById('signup-progress');
            progressBar.style.width = progressPercentage + '%';
            progressBar.setAttribute('aria-valuenow', progressPercentage);
        }

        document.addEventListener('DOMContentLoaded', function() {
            let formElements = document.querySelectorAll('input, select, textarea');
            formElements.forEach(function(element) {
                element.addEventListener('input', updateProgressBar);
            });

            updateProgressBar();
        });
    </script>
    <script>
        function checkAvailability() {
            $("#loaderIcon").show();
            jQuery.ajax({
                url: "check_availability.php",
                data:'emailid='+$("#emailid").val(),
                type: "POST",
                success:function(data){
                    $("#user-availability-status").html(data);
                    $("#loaderIcon").hide();
                },
                error:function (){}
            });
        }
    </script>
       <style>
        .info-tooltip {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .info-tooltip .tooltip-text {
            visibility: hidden;
            width: 250px;
            background-color: #f9f9f9;
            color: #333;
            text-align: left;
            border-radius: 4px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%; /* Position above the info icon */
            left: 50%;
            margin-left: -125px;
            opacity: 0;
            transition: opacity 0.3s;
            border: 1px solid #ddd;
             box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .info-tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #ddd transparent transparent transparent;
        }
        .info-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        .info-icon {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 20px;
          height: 20px;
          background-color: #e0e0e0;
          color: #666;
          border-radius: 50%;
          font-size: 12px;
          margin-left: 5px;
          cursor: pointer;
        }

    </style>
</head>
    <body class="font-primary">
        <?php include('includes/header.php');?>
            <div class="min-h-screen bg-neutral flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
                    <h4 class="text-2xl font-bold text-neutral-dark text-center">Student Signup</h4>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700" style="margin-bottom:10px;">
                        <div class="bg-primary h-1.5 rounded-full" id="signup-progress" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        </div>
                    </div>
                    <form name="signup" method="post" class="space-y-6" onSubmit="return valid();">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-dark">Enter Full Name :</label>
                            <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="text" name="fullname" autocomplete="off" required  />
                        </div>
                         <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-dark">Enter Email :</label>
                            <div class="relative">
                                <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="email" name="email" id="emailid" onkeyup="checkAvailability()"  autocomplete="off" required />
                                <img src="assets/img/loader.gif" id="loaderIcon" class="absolute right-3 top-1/2 transform -translate-y-1/2" style="display:none" />
                            </div>
                            <span id="user-availability-status" class="text-sm text-gray-500" style="font-size:12px;"></span>
                        </div>
                         <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-dark">Enter Password :</label>
                            <div class="relative">
                                <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary" type="password" name="password" autocomplete="off" required  onkeyup="checkPasswordStrength()"/>
                                <button class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none" type="button" onclick="togglePasswordVisibility('password')">
                                    <i class="fa fa-eye" id="password-toggle-icon"></i>
                                </button>
                            </div>
                            <span id="password-strength" class="text-sm text-gray-500" style="font-size:12px;"></span>
                        </div>
                         <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-dark">Confirm Password :</label>
                            <div class="relative">
                                <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary"  type="password" name="confirmpassword" autocomplete="off" required  />
                                 <button class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none" type="button" onclick="togglePasswordVisibility('confirmpassword')">
                                    <i class="fa fa-eye" id="confirmpassword-toggle-icon"></i>
                                </button>
                            </div>
                        </div>
                         <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-dark">
                              Enter Telegram Chat ID :
                               <span class="info-tooltip">
                                <span class="info-icon"><i>i</i></span>
                                <span class="tooltip-text">
                                    To get your Telegram Chat ID, follow these steps:
                                    <ul class="list-disc pl-5 mb-3">
                                        <li>Open Telegram and search for <a href="https://telegram.me/userinfobot" target="_blank" class="text-primary-400 hover:underline">@userinfobot</a>.</li>
                                        <li>Start a chat with the bot.</li>
                                        <li>The bot will send you a message containing your chat ID.</li>
                                        <li>Copy and paste the chat ID into the field below.</li>
                                    </ul>
                                </span>
                            </span>
                            </label>
                            <input class="w-full border-gray-300 border rounded-md py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary"  type="text" name="telegram_chat_id" autocomplete="off" required  />
                        </div>
                        <button type="submit" name="signup" class="w-full py-2 px-4 bg-primary text-white font-medium rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="submit" onclick="return checkEmptyEmail()">Signup </button>
                    </form>
                </div>
            </div>
          <?php include('includes/footer.php');?>
        </body>
    </html>