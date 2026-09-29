<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */
$has_error = false;
if (isset($_POST['create_account'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($fullname) || strlen($fullname) < 3) {
        $has_error = true;
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $has_error = true;
    }

    if (empty($password) || strlen($password) < 8 || !preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).+$/', $password)) {
        $has_error = true;
    }

    if (empty($confirm_password) || $password !== $confirm_password) {
        $has_error = true;
    }

    if ($has_error) {
        header("Location: register.php");
        exit();
    }

    $email_query = "SELECT * from users where email='$email'";
    $result = mysqli_query($conn, $email_query);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['error'] = "Email already registered";
        header("Location: register.php");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $insert_query = "INSERT into users(fullname,email,password) values('$fullname','$email','$hashed_password')";
    if (mysqli_query($conn, $insert_query)) {
        header("Location: login.php");
        exit();
    }
    else{
        echo "Insert failed: " .mysqli_error($conn);
    }
}
