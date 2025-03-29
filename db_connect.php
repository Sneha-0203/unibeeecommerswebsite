<?php
// Database connection parameters
$host = "localhost";      // Your database host (usually localhost for XAMPP)
$username = "root";       // Default XAMPP username
$password = "";           // Default XAMPP password is empty
$database = "shoe_store"; // Your database name - fixed the typo here

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper character encoding
$conn->set_charset("utf8mb4");
?>