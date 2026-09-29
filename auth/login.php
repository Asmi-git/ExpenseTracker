<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Expense Tracker</title>
    <link rel="stylesheet" href="../assets/css/register_login.css">
    <link rel="icon" type="image/png" href="../assets/images/android-chrome-192x192.png">
</head>

<body>
    <div class="left">
        <img src="../assets/images/login_img.avif" alt="Logo">
    </div>
    <div class="right">
        <div class="logo-container">
            <img src="../assets/images/mainlogo.jpeg" alt="Logo">
            <h1>Expense Tracker</h1>
        </div>
        <div class="box">
            <h2>Welcome Back!</h2>
            <?php if (isset($_GET['deleted'])) { ?>
                <small id="accountDeletedMsg" style="color:green;display:block;">Your account was deleted successfully.</small>
            <?php } ?>
            <form action="login_process.php" method="POST">
                <label for="email">Email</label><br>
                <input type="email" id="email" placeholder="Enter email..." name="email" required><br>
                <?php
                if (isset($_SESSION['emailError'])) {
                    echo '<small id="emailError">' . $_SESSION['emailError'] . '</small>';
                    unset($_SESSION['emailError']);
                }
                ?>
                <label for="pswd">Password</label><br>
                <input type="password" id="pswd" placeholder="Enter your password..." name="password" required><br>
                <?php
                if (isset($_SESSION['passwordError'])) {
                    echo '<small id="passwordError">' . $_SESSION['passwordError'] . '</small>';
                    unset($_SESSION['passwordError']);
                }
                ?>
                <input type="submit" value="Log In" name="login">
            </form>
            <p>Don't have an account?<a href="register.php">Sign Up</a></p>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const inputs = document.querySelectorAll("input");
            inputs.forEach(function(input) {
                input.addEventListener("input", function() {
                    let emailMsg = document.getElementById("emailError");
                    let passwordMsg=document.getElementById("passwordError");
                    if (emailMsg) {
                        emailMsg.style.display = "none";
                    }
                    if(passwordMsg){
                        passwordMsg.style.display="none";
                    }
                });
            });
        });
    </script>
</body>

</html>