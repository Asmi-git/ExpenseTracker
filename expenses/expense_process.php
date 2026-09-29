<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}
include '../config/db.php';
/** @var mysqli $conn */

$has_error=false;
if(isset($_POST['add_expense'])){
    $category=trim($_POST['category']);
    $amount=trim($_POST['amount']);
    $date=$_POST['date'];
    $description=trim($_POST['description']);
    
    if(empty($category) || empty($amount) || empty($date)){
        $has_error=true;
    }

    if(!is_numeric($amount) || $amount<=0){
        $has_error=true;
    }

    if($has_error){
        header("Location: expense.php");
        exit();
    }

    $user_id=$_SESSION['user_id'];
    $insert_query="INSERT into expense(user_id,category,amount,expense_date,description) values('$user_id','$category','$amount','$date','$description')";
    if(mysqli_query($conn,$insert_query)){
        header("Location: expense.php");
        exit();
    }
    else{
        echo "Insert failed: " .mysqli_error($conn);
    }
}
?>