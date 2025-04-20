<?php
// reset_password.php - Direct password reset
session_start();
require_once 'config/database.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if email is submitted (first step)
    if (isset($_POST['email']) && !isset($_POST['new_password'])) {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error = "Please enter your email address";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } else {
            // Check if email exists in database
            $stmt = $conn->prepare("SELECT id, username FROM admins WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $user_id = $row['id'];
                $username = $row['username'];
                
                // Store user ID in session to use in reset form
                $_SESSION['reset_user_id'] = $user_id;
                $_SESSION['reset_username'] = $username;
                
                // Show password reset form
                $show_reset_form = true;
                
                // Log the reset request
                error_log("Password reset initiated for user ID: $user_id, Username: $username, Email: $email");
            } else {
                // Don't reveal if email exists or not for security
                $error = "No account found with that email address";
            }
            
            $stmt->close();
        }
    }
    // Check if new password is submitted (second step)
    elseif (isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify session contains reset user ID
        if (!isset($_SESSION['reset_user_id'])) {
            $error = "Password reset session expired. Please start again.";
        } 
        // Validate password
        elseif (empty($new_password)) {
            $error = "Please enter a new password";
        } 
        elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long";
        }
        elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match";
        } 
        else {
            $user_id = $_SESSION['reset_user_id'];
            $username = $_SESSION['reset_username'];
            
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in database
            $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success = "Password has been reset successfully!";
                
                // Clear the reset session
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_username']);
                
                // Log the successful reset
                error_log("Password reset completed for user ID: $user_id, Username: $username");
            } else {
                $error = "An error occurred. Please try again later.";
            }
            
            $stmt->close();
        }
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
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <p class="success"><?php echo $success; ?></p>
        <p><a href="admin_login.php">Back to Login</a></p>
    <?php elseif (isset($show_reset_form) && $show_reset_form): ?>
        <!-- Password Reset Form -->
        <p>Enter a new password for your account.</p>
        <form method="post">
            <input type="password" name="new_password" placeholder="New Password" required minlength="8"><br>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
            <button type="submit">Reset Password</button>
        </form>
    <?php else: ?>
        <!-- Email Form -->
        <p>Enter your email address to reset your password.</p>
        <form method="post">
            <input type="email" name="email" placeholder="Email Address" required><br>
            <button type="submit">Continue</button>
        </form>
    <?php endif; ?>
    
    <?php if (empty($success) && !isset($show_reset_form)): ?>
        <p><a href="admin_login.php">Back to Login</a></p>
    <?php endif; ?>
</div>

</body>
</html>