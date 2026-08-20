<?php
include('db.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Intercept non-authenticated backend attempts
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized entry']);
    exit();
}

$uid = (int)$_SESSION['userID'];
$action = $_POST['action'] ?? '';

header('Content-Type: application/json');

if ($action === 'update') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $new_pass = $_POST['new_password'];

    if (empty($username) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Username and email cannot be empty.']);
        exit();
    }

    // Process secure update directly targeted to table 'user'
    $stmt = $conn->prepare("UPDATE user SET username = ?, email = ? WHERE userID = ?");
    $stmt->bind_param("ssi", $username, $email, $uid);
    
    if ($stmt->execute()) {
        $_SESSION['username'] = $username; // Keep session storage synced
        
        // Handle password hashing workflow if provided
        if (!empty($new_pass)) {
            if ($new_pass !== $_POST['confirm_password']) {
                echo json_encode(['status' => 'error', 'message' => 'Profile details saved, but password validation confirmation failed.']);
                exit();
            }
            
            // Password verification hash update mapping to column 'password'
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
            $pStmt = $conn->prepare("UPDATE user SET password = ? WHERE userID = ?");
            $pStmt->bind_param("si", $hashed, $uid);
            $pStmt->execute();
            $pStmt->close();
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Profile changes securely saved!', 'new_username' => htmlspecialchars($username)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database operation error. Email or Username could already be registered.']);
    }
    $stmt->close();
    exit();
}

if ($action === 'delete') {
    // Drop user record safely using exact matching target primary key
    $stmt = $conn->prepare("DELETE FROM user WHERE userID = ?");
    $stmt->bind_param("i", $uid);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to drop database record safely.']);
    }
    $stmt->close();
    exit();
}