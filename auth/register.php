<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up | Expense Tracker</title>
    <link rel="stylesheet" href="../assets/css/register_login.css">
    <link rel="icon" type="image/png" href="../assets/images/android-chrome-192x192.png">
</head>

<body>
    <div class="left">
        <img src="../assets/images/login_img.avif" alt="Logo">
    </div>
    <div class="right" style="margin-top:15px;">
        <div class="logo-container">
            <img src="../assets/images/mainlogo.jpeg" alt="Logo">
            <h1>Expense Tracker</h1>
        </div>
        <div class="box" style="height: auto;">
            <h2>Create Your Account</h2>
            <form action="register_process.php" method="post" id="register-form">
                <label for="name">Full name</label><br>
                <input type="text" id="name" placeholder="Enter your name here..." name="fullname" required minlength="3"><br>
                <label for="email">Email</label><br>
                <input type="email" id="email" placeholder="Enter email..." name="email" required><br>
                <label for="pswd">Password</label><br>
                <input type="password" id="pswd" placeholder="Create a strong password..." name="password"><br>
                <small id="passwordError"></small>
                <label for="confirmpswd">Confirm Password</label><br>
                <input type="password" id="confirmpswd" placeholder="Retype your password..." name="confirm_password"><br>
                <small id="confirmPasswordError"></small>
                <input type="submit" value="Create Account" name="create_account">
            </form>
            <p>Already have an account?<a href="login.php">Sign In</a></p>
            <?php
            if (isset($_SESSION['error'])) {
                echo '<small id="msg" style="font-size: 15px; text-align: center;">' . $_SESSION['error'] . '</small>';
                unset($_SESSION['error']);
            }
            ?>
        </div>
    </div>
    <script src="../assets/js/register.js"></script>
</body>

</html>