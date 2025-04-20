<?php
// Database connection parameters
$servername = "127.0.0.1";
$username = "root"; // Replace with your actual database username
$password = ""; // Replace with your actual database password
$dbname = "shoe_store";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Process form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate form data
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject = isset($_POST['subject']) ? filter_var($_POST['subject'], FILTER_SANITIZE_STRING) : '';
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid form data']);
        exit();
    }
    
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Success
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        // Error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to send message: ' . $stmt->error]);
    }
    
    // Close statement
    $stmt->close();
} else {
    // Not a POST request
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// Close connection
$conn->close();
?>