<?php
session_start();
include('db.php');

if (!isset($_SESSION['userID'])) {
    die("Unauthorized access");
}

$user_id = $_SESSION['userID'];

// --- DELETE FUNCTION ---
if (isset($_GET['delete'])) {
    $fid = (int)$_GET['delete'];
    $pid = (int)$_GET['pid'];

    // Double check that the review belongs to the user
    $sql = "DELETE FROM feedback WHERE feedbackID = $fid AND userID = $user_id";
    
    if ($conn->query($sql)) {
        header("Location: details.php?id=$pid&msg=Deleted");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// --- UPDATE FUNCTION ---
if (isset($_POST['update_review'])) {
    $fid = (int)$_POST['feedbackID'];
    $pid = (int)$_POST['pid'];
    $rating = mysqli_real_escape_string($conn, $_POST['rating']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    // Double check that the review belongs to the user
    $sql = "UPDATE feedback SET rating = '$rating', comment = '$comment' 
            WHERE feedbackID = $fid AND userID = $user_id";

    if ($conn->query($sql)) {
        header("Location: details.php?id=$pid&msg=Updated");
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>