<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
include '../config/db.php';
/** @var mysqli $conn */

if (isset($_POST['confirm_delete'])) {
    $user_id = $_SESSION['user_id'];

    // Remove the user's financial records first, then the account itself.
    $delete_expense_stmt = mysqli_prepare($conn, "DELETE FROM expense WHERE user_id = ?");
    mysqli_stmt_bind_param($delete_expense_stmt, "i", $user_id);
    mysqli_stmt_execute($delete_expense_stmt);
    mysqli_stmt_close($delete_expense_stmt);

    $delete_income_stmt = mysqli_prepare($conn, "DELETE FROM income WHERE user_id = ?");
    mysqli_stmt_bind_param($delete_income_stmt, "i", $user_id);
    mysqli_stmt_execute($delete_income_stmt);
    mysqli_stmt_close($delete_income_stmt);

    $delete_user_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($delete_user_stmt, "i", $user_id);
    mysqli_stmt_execute($delete_user_stmt);
    mysqli_stmt_close($delete_user_stmt);

    session_destroy();
    header("Location: ../auth/login.php?deleted=1");
    exit();
}

header("Location: profile.php");
exit();
