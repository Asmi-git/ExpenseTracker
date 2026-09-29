<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */
if(isset($_POST['login'])){
    $email=trim($_POST['email']);
    $password=$_POST['password'];

    if(empty($email)){
        $_SESSION['emailError']="Email is required";
        header("Location: login.php");
        exit();
    }

    if(empty($password)){
        $_SESSION['passwordError']="Password is required";
        header("Location: login.php");
        exit();
    }

    $email_query="SELECT * from users where email='$email'";
    $email_result=mysqli_query($conn,$email_query);

    if(mysqli_num_rows($email_result)== 0){
        $_SESSION['emailError']="Email not registered";
        header("Location: login.php");
        exit();
    }

    $user=mysqli_fetch_assoc($email_result);

    if(!password_verify($password,$user['password'])){
        $_SESSION['passwordError']="Invalid password";
        header("Location: login.php");
        exit();
    }

    $_SESSION['user_id']=$user['id'];
    $_SESSION['user_name']=$user['fullname'];
    header("Location: ../dashboard/dashboard.php");
    exit();

}
?>