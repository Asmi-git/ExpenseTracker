<?php
session_start();

include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION['user_id'])) {
    header("Location:../auth/login.php");
    exit();
}
$user_id=$_SESSION['user_id'];

$id=$_GET['id'];
$type=$_GET['type'];

if($type=='income'){
    $query="DELETE from income where id='$id' and user_id='$user_id'";
}
else{
    $query="DELETE from expense where id='$id' and user_id='$user_id'";
}

$result=mysqli_query($conn,$query);
header("Location: transaction.php");
exit();
?>