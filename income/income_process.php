<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}
include '../config/db.php';
/** @var mysqli $conn */

$has_error=false;
if(isset($_POST['add_income'])){
    $source=trim($_POST['source']);
    $amount=trim($_POST['amount']);
    $date=$_POST['date'];
    $description=trim($_POST['description']);
    
    if(empty($source) || empty($amount) || empty($date)){
        $has_error=true;
    }

    if(!is_numeric($amount) || $amount<=0){
        $has_error=true;
    }

    if($has_error){
        header("Location: income.php");
        exit();
    }

    $user_id=$_SESSION['user_id'];
    $insert_query="INSERT into income(user_id,source,amount,income_date,description) values('$user_id','$source','$amount','$date','$description')";
    if(mysqli_query($conn,$insert_query)){
        header("Location: income.php");
        exit();
    }
    else{
        echo "Insert failed: " .mysqli_error($conn);
    }
}
?>