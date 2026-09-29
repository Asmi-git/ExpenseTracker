<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
include '../config/db.php';
/** @var mysqli $conn */

$user_id = $_SESSION['user_id'];
$errors = [];

if (isset($_POST['update_profile'])) {

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ---- Basic field validation ----
    if (empty($fullname) || strlen($fullname) < 3) {
        $errors[] = "Full name must be at least 3 characters long.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // ---- Email must not belong to a different user ----
    if (!empty($email)) {
        $email_check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($email_check_stmt, "si", $email, $user_id);
        mysqli_stmt_execute($email_check_stmt);
        $email_check_result = mysqli_stmt_get_result($email_check_stmt);
        if (mysqli_num_rows($email_check_result) > 0) {
            $errors[] = "That email address is already in use by another account.";
        }
        mysqli_stmt_close($email_check_stmt);
    }

    // ---- Find out which optional columns actually exist ----
    $available_columns = [];
    $columns_result = mysqli_query($conn, "SHOW COLUMNS FROM users");
    if ($columns_result) {
        while ($col = mysqli_fetch_assoc($columns_result)) {
            $available_columns[] = $col['Field'];
        }
    }
    $has_username = in_array('username', $available_columns);
    $has_phone = in_array('phone', $available_columns);
    $has_password_updated_at = in_array('password_updated_at', $available_columns);

    // ---- Password change validation (only if the user is trying to change it) ----
    $changing_password = ($new_password !== '' || $confirm_password !== '');
    $new_hashed_password = null;

    if ($changing_password) {
        if (empty($current_password)) {
            $errors[] = "Please enter your current password to change it.";
        } else {
            $pass_check_stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
            mysqli_stmt_bind_param($pass_check_stmt, "i", $user_id);
            mysqli_stmt_execute($pass_check_stmt);
            $pass_check_result = mysqli_stmt_get_result($pass_check_stmt);
            $pass_row = mysqli_fetch_assoc($pass_check_result);
            mysqli_stmt_close($pass_check_stmt);

            if (!$pass_row || !password_verify($current_password, $pass_row['password'])) {
                $errors[] = "Current password is incorrect.";
            }
        }

        if (
            empty($new_password) || strlen($new_password) < 8 ||
            !preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).+$/', $new_password)
        ) {
            $errors[] = "New password must be at least 8 characters and include a number and a special character.";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "New password and confirm password do not match.";
        }

        if (empty($errors)) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }

    if (!empty($errors)) {
        $_SESSION['profileError'] = implode(" ", $errors);
        header("Location: profile.php");
        exit();
    }

    // ---- Build the update dynamically depending on which columns exist ----
    $set_parts = ["fullname = ?", "email = ?"];
    $types = "ss";
    $params = [$fullname, $email];

    if ($has_username) {
        $set_parts[] = "username = ?";
        $types .= "s";
        $params[] = $username;
    }
    if ($has_phone) {
        $set_parts[] = "phone = ?";
        $types .= "s";
        $params[] = $phone;
    }
    if ($new_hashed_password !== null) {
        $set_parts[] = "password = ?";
        $types .= "s";
        $params[] = $new_hashed_password;

        if ($has_password_updated_at) {
            $set_parts[] = "password_updated_at = NOW()";
        }
    }

    $types .= "i";
    $params[] = $user_id;

    $update_query = "UPDATE users SET " . implode(", ", $set_parts) . " WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, $types, ...$params);

    if (mysqli_stmt_execute($update_stmt)) {
        $_SESSION['user_name'] = $fullname;
        $_SESSION['profileSuccess'] = "Your profile was updated successfully.";
    } else {
        $_SESSION['profileError'] = "Something went wrong while updating your profile. Please try again.";
    }
    mysqli_stmt_close($update_stmt);

    header("Location: profile.php");
    exit();
}

header("Location: profile.php");
exit();
