<?php
$host = "localhost"; 
$username = "root"; 
$password = ""; 
$database = "student_management_portal"; 

$conn = new mysqli($host, $username, $password, $database);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Database connected successfully!";
}
?>
