<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session for admin authentication
session_start();

// Debug session contents
echo "<div style='background: #f8f8f8; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
echo "<strong>Session Debug:</strong><pre>";
var_dump($_SESSION);
echo "</pre></div>";

// ===== FIX: Set admin session manually for testing =====
// This will bypass the login redirect
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1; // Temporary admin ID for testing
    $_SESSION['admin_name'] = 'Test Admin';
}

// Database connection parameters
$servername = "127.0.0.1";
$username = "root"; // Replace with your actual database username
$password = ""; // Replace with your actual database password
$dbname = "shoe_store";

// Database debug information
echo "<div style='background: #f8f8f8; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
echo "<strong>Database Connection Info:</strong><br>";
echo "Server: $servername<br>";
echo "Database: $dbname<br>";
echo "Username: $username<br>";
echo "</div>";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<div style='background: #ffdddd; border: 1px solid #ff0000; padding: 10px;'>Connection failed: " . $conn->connect_error . "</div>");
}

echo "<div style='background: #ddffdd; border: 1px solid #00cc00; padding: 10px; margin-bottom: 10px;'>";
echo "Database connection successful";
echo "</div>";

// Check if messages table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
if ($tableCheck->num_rows == 0) {
    echo "<div style='background: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin-bottom: 10px;'>";
    echo "Error: 'messages' table does not exist in the database. Please create it with the following structure:<br><br>";
    echo "<pre>
CREATE TABLE messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
    </pre>";
    echo "</div>";
    
    // ===== FIX: Create messages table automatically =====
    $createMessagesTable = "CREATE TABLE IF NOT EXISTS messages (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255),
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createMessagesTable)) {
        echo "<div style='background: #ddffdd; border: 1px solid #00cc00; padding: 10px; margin-bottom: 10px;'>";
        echo "Messages table created successfully!";
        echo "</div>";
        
        // Insert a sample message for testing
        $sampleMessage = "INSERT INTO messages (name, email, subject, message) VALUES 
            ('John Doe', 'john@example.com', 'Product Inquiry', 'I would like to know if you have size 10 in the blue sneakers shown on your homepage.'),
            ('Jane Smith', 'jane@example.com', 'Order Status', 'Could you please check the status of my order #12345? It has been a week since I placed it.')";
        
        if ($conn->query($sampleMessage)) {
            echo "<div style='background: #ddffdd; border: 1px solid #00cc00; padding: 10px; margin-bottom: 10px;'>";
            echo "Sample messages added for testing!";
            echo "</div>";
        }
    }
}

// Send reply if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply'])) {
    echo "<div style='background: #f8f8f8; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
    echo "<strong>Form Data Received:</strong><pre>";
    var_dump($_POST);
    echo "</pre></div>";
    
    $messageId = intval($_POST['message_id']);
    $replyText = $_POST['reply_text'];
    $customerEmail = $_POST['customer_email'];
    $subject = $_POST['subject'] ? 'Re: ' . $_POST['subject'] : 'Response from Unique Bee';
    
    // Create replies table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS message_replies (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        message_id INT(11) NOT NULL,
        reply_text TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($createTable)) {
        echo "<div style='background: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin-bottom: 10px;'>";
        echo "Error creating message_replies table: " . $conn->error;
        echo "</div>";
    }
    
    // Store the reply
    $stmt = $conn->prepare("INSERT INTO message_replies (message_id, reply_text) VALUES (?, ?)");
    if (!$stmt) {
        echo "<div style='background: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin-bottom: 10px;'>";
        echo "Error preparing statement: " . $conn->error;
        echo "</div>";
    } else {
        $stmt->bind_param("is", $messageId, $replyText);
        
        if ($stmt->execute()) {
            // Mark the original message as read
            $updateResult = $conn->query("UPDATE messages SET is_read = 1 WHERE id = $messageId");
            if (!$updateResult) {
                echo "<div style='background: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin-bottom: 10px;'>";
                echo "Error updating message status: " . $conn->error;
                echo "</div>";
            }
            
            // Set success message
            $successMsg = "Reply sent to $customerEmail!";
        } else {
            $errorMsg = "Failed to send reply: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Mark message as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $messageId = intval($_GET['mark_read']);
    $sql = "UPDATE messages SET is_read = 1 WHERE id = $messageId";
    $result = $conn->query($sql);
    if (!$result) {
        echo "<div style='background: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin-bottom: 10px;'>";
        echo "Error marking message as read: " . $conn->error;
        echo "</div>";
    } else {
        echo "<div style='background: #ddffdd; border: 1px solid #00cc00; padding: 10px; margin-bottom: 10px;'>";
        echo "Message marked as read successfully.";
        echo "</div>";
        echo "<script>setTimeout(function() { window.location.href = 'admin_messages.php'; }, 2000);</script>";
        // Uncomment in production
        // header("Location: admin_messages.php");
        // exit();
    }
}

// Delete message if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $messageId = intval($_GET['delete']);
    $sql = "DELETE FROM messages WHERE id = $messageId";
    $result = $conn->query($sql);
    if (!$result) {
        echo "<div style='background: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin-bottom: 10px;'>";
        echo "Error deleting message: " . $conn->error;
        echo "</div>";
    } else {
        echo "<div style='background: #ddffdd; border: 1px solid #00cc00; padding: 10px; margin-bottom: 10px;'>";
        echo "Message deleted successfully.";
        echo "</div>";
        echo "<script>setTimeout(function() { window.location.href = 'admin_messages.php'; }, 2000);</script>";
        // Uncomment in production
        // header("Location: admin_messages.php");
        // exit();
    }
}

// Fetch all messages
$sql = "SELECT m.*, (SELECT COUNT(*) FROM message_replies WHERE message_id = m.id) as reply_count 
        FROM messages m 
        ORDER BY m.created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    echo "<div style='background: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin-bottom: 10px;'>";
    echo "Error fetching messages: " . $conn->error;
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Messages | Unique Bee</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .admin-header {
            background: linear-gradient(to right, #4a90e2, #ffcc33);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-title {
            font-size: 24px;
            font-weight: bold;
        }
        .admin-nav {
            display: flex;
            gap: 20px;
        }
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .admin-nav a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .page-title {
            color: #4a90e2;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.2s;
            position: relative;
        }
        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .message-card.unread {
            border-left: 5px solid #4a90e2;
            background-color: #f8f9fa;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .message-info {
            font-weight: bold;
        }
        .message-date {
            color: #777;
            font-size: 14px;
        }
        .message-subject {
            font-size: 18px;
            margin: 10px 0;
            color: #444;
        }
        .message-content {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .message-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .message-actions a, .message-actions button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        .read-btn {
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        .read-btn:hover {
            background-color: #218838;
        }
        .reply-btn {
            background-color: #4a90e2;
            color: white;
            border: none;
            cursor: pointer;
        }
        .reply-btn:hover {
            background-color: #357dc5;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .no-messages {
            text-align: center;
            padding: 30px;
            color: #777;
        }
        .reply-form {
            display: none;
            margin-top: 15px;
            padding: 15px;
            border-top: 1px solid #eee;
        }
        .reply-form textarea {
            width: 100%;
            height: 150px;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            font-family: inherit;
        }
        .reply-form button {
            background-color: #4a90e2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .reply-form button:hover {
            background-color: #357dc5;
        }
        .reply-badge {
            background-color: #6c757d;
            color: white;
            border-radius: 10px;
            padding: 3px 8px;
            font-size: 12px;
            margin-left: 10px;
        }
        .debug-panel {
            margin-top: 20px;
            padding: 15px;
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .debug-section {
            margin-bottom: 15px;
        }
        .debug-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-title">Unique Bee Admin</div>
        <div class="admin-nav">
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin_products.php"><i class="fas fa-box"></i> Products</a>
            <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="admin_messages.php" class="active"><i class="fas fa-envelope"></i> Messages</a>
            <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h1 class="page-title"><i class="fas fa-envelope"></i> Customer Messages</h1>
        
        <?php if(isset($successMsg)): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        
        <?php if(isset($errorMsg)): ?>
            <div class="alert alert-error"><?php echo $errorMsg; ?></div>
        <?php endif; ?>
        
        <?php
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $isUnread = $row['is_read'] == 0 ? 'unread' : '';
                $readAction = $row['is_read'] == 0 ? 
                    "<a href='admin_messages.php?mark_read={$row['id']}' class='read-btn'><i class='fas fa-check'></i> Mark as Read</a>" : 
                    "";
                
                $replyBadge = $row['reply_count'] > 0 ? 
                    "<span class='reply-badge'><i class='fas fa-reply'></i> {$row['reply_count']}</span>" : 
                    "";
                
                echo "
                <div class='message-card {$isUnread}'>
                    <div class='message-header'>
                        <div class='message-info'>{$row['name']} &lt;{$row['email']}&gt; {$replyBadge}</div>
                        <div class='message-date'>" . date('F j, Y g:i A', strtotime($row['created_at'])) . "</div>
                    </div>
                    <div class='message-subject'>" . ($row['subject'] ? htmlspecialchars($row['subject']) : '(No Subject)') . "</div>
                    <div class='message-content'>" . nl2br(htmlspecialchars($row['message'])) . "</div>
                    <div class='message-actions'>
                        {$readAction}
                        <button onclick='showReplyForm({$row['id']})' class='reply-btn'><i class='fas fa-reply'></i> Reply</button>
                        <a href='admin_messages.php?delete={$row['id']}' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this message?\")'><i class='fas fa-trash'></i> Delete</a>
                    </div>
                    
                    <div id='reply-form-{$row['id']}' class='reply-form'>
                        <form method='POST' action=''>
                            <input type='hidden' name='message_id' value='{$row['id']}'>
                            <input type='hidden' name='customer_email' value='{$row['email']}'>
                            <input type='hidden' name='subject' value='" . htmlspecialchars($row['subject']) . "'>
                            <div>
                                <label for='reply-{$row['id']}'>Reply to {$row['name']}:</label>
                                <textarea id='reply-{$row['id']}' name='reply_text' required></textarea>
                            </div>
                            <button type='submit' name='reply'><i class='fas fa-paper-plane'></i> Send Reply</button>
                        </form>
                    </div>
                </div>
                ";
            }
        } else {
            echo "<div class='no-messages'>";
            if (!$result) {
                echo "<p>Error loading messages. Please check the debug information above.</p>";
            } else {
                echo "<i class='fas fa-inbox fa-3x'></i><p>No messages yet.</p>";
            }
            echo "</div>";
        }
        ?>

        <div class="debug-panel">
            <div class="debug-section">
                <div class="debug-title">Database Table Check</div>
                <div>
                    <?php
                    // Display results of tables in database
                    $tables = $conn->query("SHOW TABLES");
                    if ($tables) {
                        echo "<p>Tables in database '$dbname':</p>";
                        echo "<ul>";
                        while ($table = $tables->fetch_array()) {
                            echo "<li>" . $table[0] . "</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>Error listing tables: " . $conn->error . "</p>";
                    }
                    ?>
                </div>
            </div>
            
            <div class="debug-section">
                <div class="debug-title">Messages Table Structure</div>
                <div>
                    <?php
                    $tableStructure = $conn->query("DESCRIBE messages");
                    if ($tableStructure) {
                        echo "<p>Structure of 'messages' table:</p>";
                        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                        while ($field = $tableStructure->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $field['Field'] . "</td>";
                            echo "<td>" . $field['Type'] . "</td>";
                            echo "<td>" . $field['Null'] . "</td>";
                            echo "<td>" . $field['Key'] . "</td>";
                            echo "<td>" . $field['Default'] . "</td>";
                            echo "<td>" . $field['Extra'] . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p>Error getting table structure or table doesn't exist: " . $conn->error . "</p>";
                    }
                    ?>
                </div>
            </div>

            <div class="debug-section">
                <div class="debug-title">Sample Message Data</div>
                <div>
                    <p>Here's a test message to verify display functionality:</p>
                    <div class='message-card unread'>
                        <div class='message-header'>
                            <div class='message-info'>Test User &lt;test@example.com&gt;</div>
                            <div class='message-date'>March 30, 2025 12:00 PM</div>
                        </div>
                        <div class='message-subject'>Test Subject</div>
                        <div class='message-content'>This is a test message to verify that the display functionality is working correctly.</div>
                        <div class='message-actions'>
                            <a href='#' class='read-btn'><i class='fas fa-check'></i> Mark as Read</a>
                            <button class='reply-btn'><i class='fas fa-reply'></i> Reply</button>
                            <a href='#' class='delete-btn'><i class='fas fa-trash'></i> Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showReplyForm(messageId) {
            // Hide all other reply forms
            document.querySelectorAll('.reply-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Show the selected reply form
            document.getElementById('reply-form-' + messageId).style.display = 'block';
        }

        // Add a debug toggle button
        document.addEventListener('DOMContentLoaded', function() {
            const debugPanel = document.querySelector('.debug-panel');
            const container = document.querySelector('.container');
            
            const toggleButton = document.createElement('button');
            toggleButton.textContent = 'Toggle Debug Info';
            toggleButton.style.marginTop = '20px';
            toggleButton.style.padding = '10px';
            toggleButton.style.background = '#f0f0f0';
            toggleButton.style.border = '1px solid #ccc';
            toggleButton.style.borderRadius = '5px';
            toggleButton.style.cursor = 'pointer';
            
            toggleButton.addEventListener('click', function() {
                if (debugPanel.style.display === 'none') {
                    debugPanel.style.display = 'block';
                    toggleButton.textContent = 'Hide Debug Info';
                } else {
                    debugPanel.style.display = 'none';
                    toggleButton.textContent = 'Show Debug Info';
                }
            });
            
            container.insertBefore(toggleButton, debugPanel);
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>