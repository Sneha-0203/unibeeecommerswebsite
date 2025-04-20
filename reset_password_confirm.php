<?php
// reset_password_confirm.php - Reset confirmation page
session_start();
require_once 'config/database.php';

$error = "";
$success = "";
$token_valid = false;
$token = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token
    $stmt = $conn->prepare("SELECT pr.user_id, pr.expires_at, a.username 
                           FROM password_resets pr 
                           JOIN admins a ON pr.user_id = a.id 
                           WHERE pr.token = ? AND pr.expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $username = $row['username'];
        $token_valid = true;
    } else {
        $error = "Invalid or expired reset link. Please request a new one.";
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            // Delete used token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $success = "Password has been reset successfully. You can now login with your new password.";
            
            // Log the successful reset
            error_log("Password reset completed for user ID: $user_id, Username: $username");
        } else {
            $error = "An error occurred. Please try again later.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .reset-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
        }
        input {
            width: 90%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px;
            width: 90%;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0069d9;
        }
        .error {
            color: red;
            font-size: 14px;
        }
        .success {
            color: green;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="reset-container">
    <h2>Reset Password</h2>
    
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo $error; ?></p>
        <p><a href="reset_password.php">Request a new reset link</a></p>
    <?php elseif (!empty($success)): ?>
        <p class="success"><?php echo $success; ?></p>
        <p><a href="admin_login.php">Go to Login</a></p>
    <?php elseif ($token_valid): ?>
        <p>Enter your new password below:</p>
        <form method="post">
            <input type="password" name="password" placeholder="New Password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required><br>
            <button type="submit">Reset Password</button>
        </form>
    <?php else: ?>
        <p class="error">Invalid request. Please use the reset link sent to your email.</p>
        <p><a href="reset_password.php">Request a password reset</a></p>
    <?php endif; ?>
</div>

</body>
</html>