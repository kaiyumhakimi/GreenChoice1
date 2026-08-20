<?php
session_start();
include('db.php');

if(isset($_POST['favorite_id']) && isset($_SESSION['userID'])) {
    $favId = mysqli_real_escape_string($conn, $_POST['favorite_id']);
    $uid = $_SESSION['userID'];

    // Verify this favorite actually belongs to the logged in user before deleting
    $sql = "DELETE FROM favorite WHERE favoriteID = '$favId' AND userID = '$uid'";
    
    if(mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "unauthorized";
}
?>