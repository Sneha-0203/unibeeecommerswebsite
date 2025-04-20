<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['newsletter_error'] = "Invalid email format";
        header("Location: index.php#newsletter");
        exit();
    }
    
    // Database connection
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "shoe_store";
    
    try {
        // Create database connection
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Check if email already exists in subscribers
        $check_sql = "SELECT id FROM newsletter_subscribers WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if (!$check_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $check_stmt->close();
            $conn->close();
            $_SESSION['newsletter_error'] = "This email is already subscribed";
            header("Location: index.php#newsletter");
            exit();
        }
        $check_stmt->close();
        
        // Insert new subscriber
        $insert_sql = "INSERT INTO newsletter_subscribers (email, subscribed_at) VALUES (?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        
        if (!$insert_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $insert_stmt->bind_param("s", $email);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Insert failed: " . $insert_stmt->error);
        }
        
        $insert_stmt->close();
        $conn->close();
        
        // Send confirmation email using PHPMailer
        // Try to load Composer autoloader first
        $autoloaderPath = 'vendor/autoload.php';
        if (file_exists($autoloaderPath)) {
            require $autoloaderPath;
        } else {
            // Fall back to direct includes if autoloader isn't available
            require 'PHPMailer/PHPMailer.php';
            require 'PHPMailer/SMTP.php';
            require 'PHPMailer/Exception.php';
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'snehas032004@gmail.com';
        $mail->Password = 'eefi eyzz xznl lszw'; // Consider using environment variables for this
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('snehas032004@gmail.com', 'UNIBEE Shoes');
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to UNIBEE Newsletter';
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h1 style="color: #3a86ff;">UNIBEE</h1>
                    <p style="color: #666;">Your Ultimate Shoe Destination</p>
                </div>
                
                <div style="padding: 20px; background-color: #f9f9f9; border-radius: 10px;">
                    <h2 style="color: #333;">Thank You for Subscribing!</h2>
                    <p style="margin-bottom: 15px;">Dear Subscriber,</p>
                    <p style="margin-bottom: 15px;">Welcome to the UNIBEE family! Thank you for subscribing to our newsletter. You\'ll now receive updates on:</p>
                    <ul style="margin-bottom: 15px;">
                        <li>New arrivals and exclusive collections</li>
                        <li>Special offers and discounts</li>
                        <li>Shoe care tips and style guides</li>
                        <li>Upcoming events and promotions</li>
                    </ul>
                    <p style="margin-bottom: 20px;">Stay tuned for our upcoming collection launch!</p>
                    <div style="text-align: center;">
                        <a href="https://www.unibee.com" style="display: inline-block; background-color: #3a86ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Visit Our Website</a>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 14px;">
                    <p>If you didn\'t subscribe to our newsletter, please ignore this email.</p>
                    <p>&copy; 2025 UNIBEE. All Rights Reserved.</p>
                </div>
            </div>
        ';
        
        $mail->send();
        $_SESSION['newsletter_success'] = "Thank you for subscribing to our newsletter!";
        
    } catch (Exception $e) {
        $_SESSION['newsletter_error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: index.php#newsletter");
    exit();
}

// If not POST request or email not set
header("Location: index.php");
exit();
?>