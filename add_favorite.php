<?php
include('db.php');
session_start();

// Correct session key is 'userID' not 'user_id'
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    echo "unauthenticated";
    exit();
}

$userId = (int)$_SESSION['userID'];

if (isset($_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];

    // Check if already bookmarked — correct column: productID
    $check = $conn->prepare("SELECT favoriteID FROM favorite WHERE userID = ? AND productID = ?");
    $check->bind_param("ii", $userId, $productId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        // Not yet bookmarked — insert it
        $stmt = $conn->prepare("INSERT INTO favorite (userID, productID) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $productId);
        echo $stmt->execute() ? "success" : "error";
    } else {
        // Already bookmarked — toggle it off (remove)
        $stmt = $conn->prepare("DELETE FROM favorite WHERE userID = ? AND productID = ?");
        $stmt->bind_param("ii", $userId, $productId);
        echo $stmt->execute() ? "removed" : "error";
    }
}
?>