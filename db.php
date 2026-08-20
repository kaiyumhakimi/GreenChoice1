<?php
$host = "127.0.0.1";
$user = "root";
$pass = ""; 
$dbname = "greenchoice7";

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>