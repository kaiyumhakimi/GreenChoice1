<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['userID'])) {
    $uid     = (int)$_SESSION['userID'];
    $pid     = (int)$_POST['product_id'];
    $rating  = (int)$_POST['rating'];
    $comment = $conn->real_escape_string($_POST['comment']);
    $date    = date('Y-m-d H:i:s');

    $sql = "INSERT INTO feedback (userID, productID, rating, comment, feedbackDate) 
            VALUES ($uid, $pid, $rating, '$comment', '$date')";

    if ($conn->query($sql)) {
        header("Location: details.php?id=$pid&msg=Review+submitted!");
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    header("Location: index.php");
}
?>