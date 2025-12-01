<?php
// db.php — MySQLi connection
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "zerohunger";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set UTF8
$conn->set_charset("utf8mb4");

// Use $conn throughout the app (mysqli)
