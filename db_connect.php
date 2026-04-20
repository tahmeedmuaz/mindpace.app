<?php
// db_connect.php
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "mindpace_db"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>