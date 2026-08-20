<?php
session_start();
require_once('db.php');

if (isset($_POST['login_btn'])) {
    // Sanitize input to prevent SQL Injection
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Query the 'user' table
    $query = "SELECT * FROM user WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        
        // Verify the hashed password against the plain text input
        if (password_verify($password, $user_data['password'])) {
            
            // --- SESSION STORAGE (This is how the site remembers the user) ---
            $_SESSION['auth'] = true;
            $_SESSION['userID'] = $user_data['userID'];
            $_SESSION['username'] = $user_data['username'];
            
            // Optional: Store email or role if needed
            $_SESSION['userEmail'] = $user_data['email'];

            // Success Message and Redirect
            echo "<script>
                alert('Login Successful! Welcome back, " . addslashes($user_data['username']) . "');
                window.location.href='index.php';
            </script>";
            exit();
            
        } else {
            // Password mismatch
            echo "<script>
                alert('Incorrect Password! Please try again.');
                window.history.back();
            </script>";
            exit();
        }
    } else {
        // Email not found
        echo "<script>
            alert('No account found with that email address.');
            window.history.back();
        </script>";
        exit();
    }
} else {
    // If someone tries to access this file directly without clicking login
    header("Location: login.php");
    exit();
}
?>