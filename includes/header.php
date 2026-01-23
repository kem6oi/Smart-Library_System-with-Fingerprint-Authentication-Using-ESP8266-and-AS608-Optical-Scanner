<div class="bg-white py-4 shadow-md">
    <div class="container mx-auto px-4 flex items-center justify-between">
        <div class="text-neutral-dark font-bold text-xl">
             <a href="index.php" class="flex items-center">
                <img src="assets/img/logo5.png" class="mr-2 h-8" alt="Logo" />
                <span>Smart Library Management System</span>
            </a>
        </div>
         <div class="flex items-center space-x-4">
            <?php if(isset($_SESSION['login']) && $_SESSION['login']) { ?>
                <a href="logout.php" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Log out</a>
            <?php } else { ?>
                <a href="index.php" class="bg-primary text-white py-2 px-4 rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">User Login</a>
                <a href="adminlogin.php" class="bg-primary text-white py-2 px-4 rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">Librarian Login</a>

           <?php } ?>
        </div>
    </div>
</div>
<!-- LOGO HEADER END-->
<?php if(isset($_SESSION['login']) && $_SESSION['login']) { ?>
    <section class="menu-section">
        <div class="container mx-auto px-4">
            <div class="flex">
                <div class="w-full">
                    <div class="flex justify-end">
                        <ul id="menu-top" class="flex space-x-6">
                            <li><a href="dashboard.php" class="text-neutral-dark hover:text-primary">DASHBOARD</a></li>
                            <li class="relative group">
                                <a href="#" class="text-neutral-dark hover:text-primary flex items-center group-hover:text-primary" id="ddlmenuItem"> Account <i class="fa fa-angle-down ml-1"></i></a>
                                <ul class="absolute hidden group-hover:block bg-white border border-gray-200 rounded-md shadow-md mt-2 py-1 w-48">
                                    <li class="px-4 py-2 hover:bg-gray-100"><a href="my-profile.php" class="block text-neutral-dark">My Profile</a></li>
                                    <li class="px-4 py-2 hover:bg-gray-100"><a href="change-password.php" class="block text-neutral-dark">Change Password</a></li>
                                </ul>
                            </li>
                            <li><a href="issued-books.php" class="text-neutral-dark hover:text-primary">Issued Books</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php } else { ?>
    <section class="menu-section">
        <div class="container mx-auto px-4">
            <div class="flex">
                <div class="w-full">
                    <div class="flex justify-end">
                        <ul id="menu-top" class="flex space-x-6">
                            <?php if (basename($_SERVER['PHP_SELF']) !== 'signup.php') { ?>
                                <li><a href="signup.php"  class="bg-primary text-white py-2 px-4 rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">User Signup</a></li>
                             <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php } ?>