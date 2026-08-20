<?php
require_once dirname(__FILE__) . '/db.php';

if (!isset($conn)) {
    die("Error: The database connection variable was not found.");
}

if (isset($_POST['register_btn'])) {
    // Collect inputs (No need for mysqli_real_escape_string if using prepared statements)
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // 2. Check if email already exists using a Prepared Statement
    $check_email = "SELECT email FROM user WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_email);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "<script>alert('This email is already registered!'); window.history.back();</script>";
        mysqli_stmt_close($stmt);
        exit();
    }
    mysqli_stmt_close($stmt);

    // 3. Hash password and insert user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // NOTE: If you fixed Step 1 (Auto Increment), remove 'userID' and the first '?' from below.
    // If you haven't fixed Step 1, you'll have to manually supply an ID here, which is risky.
    $query = "INSERT INTO user (username, email, password) VALUES (?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Registration Successful!'); window.location='login.php';</script>";
    } else {
        echo "Error: " . mysqli_stmt_error($stmt);
    }
    
    mysqli_stmt_close($conn);
}
?>