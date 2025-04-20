<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Unique Bee</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .navbar {
            background: linear-gradient(to right, #f12711, #f5af19);
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            width: 100%;
            box-sizing: border-box;
            z-index: 1000;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 5px;
            transition: color 0.3s ease-in-out;
        }
        .navbar a:hover {
            color: white;
        }
        .navbar .brand {
            font-size: 22px;
            font-weight: bold;
            color: white;
            margin-right: auto;
        }
        .container {
            width: 80%;
            max-width: 1200px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .section {
            margin-bottom: 40px;
            border-bottom: 1px solid #eee;
            padding-bottom: 30px;
        }
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        h1, h2 {
            text-align: left;
            color: #ff5722;
            font-family: 'Montserrat', sans-serif;
            margin-top: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        h1 i, h2 i {
            margin-right: 15px;
            font-size: 32px;
            color: #ff5722;
        }
        p {
            font-size: 18px;
            line-height: 1.8;
            color: #444;
            margin-bottom: 20px;
        }
        .faq-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .faq-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .faq-item i {
            margin-right: 15px;
            font-size: 24px;
            min-width: 24px;
            text-align: center;
        }
        .faq-question {
            font-weight: bold;
            font-size: 18px;
            margin: 0;
        }
        .footer {
            background: linear-gradient(to right, #4a90e2, #ffcc33);
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 16px;
            font-weight: bold;
            position: relative;
            margin-top: 50px;
        }
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin: 30px 0;
        }
        .social-icons a {
            color: #4a90e2;
            font-size: 28px;
            transition: transform 0.3s ease, color 0.3s ease;
            background-color: #f4f4f4;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .social-icons a:hover {
            transform: scale(1.1);
            color: #ff5722;
        }
        .contact-info {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 10px;
        }
        .contact-info span {
            display: inline-flex;
            align-items: center;
        }
        .contact-info i {
            margin-right: 8px;
        }
        @media (max-width: 768px) {
            .navbar {
                flex-wrap: wrap;
            }
            .container {
                width: 90%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <span class="brand"><i class="fas fa-shoe-prints"></i> UNIBEE</span>
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a>
        <a href="shipping-policy.php"><i class="fas fa-truck"></i> Shipping Policy</a>
        <a href="return-policy.php"><i class="fas fa-undo"></i> Returns & Exchanges</a>
        <a href="terms.php"><i class="fas fa-file-alt"></i> Terms & Conditions</a>
    </div>
    
    <div class="container">
        <div class="section">
            <h1><i class="fas fa-question-circle"></i> Frequently Asked Questions (FAQ)</h1>
            <p>Find answers to the most common questions about Unique Bee and our customizable footwear platform.</p>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-info-circle"></i> General Questions</h2>
            
            <div class="faq-item">
                <i class="fas fa-shoe-prints" style="color:#ff6f61;"></i>
                <p class="faq-question">What is Unique Bee?</p>
            </div>
            <p>Unique Bee is an online platform that allows users to design, buy, and sell customizable shoes.</p>
            
            <div class="faq-item">
                <i class="fas fa-cogs" style="color:#4a90e2;"></i>
                <p class="faq-question">How does Unique Bee work?</p>
            </div>
            <p><b>Customers:</b> Browse or create custom footwear using our tool.</p>
            <p><b>Sellers:</b> Register to list their customizable shoes.</p>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-shopping-cart"></i> Customization & Ordering</h2>
            
            <div class="faq-item">
                <i class="fas fa-edit" style="color:#6f42c1;"></i>
                <p class="faq-question">Can I customize every part of the shoe?</p>
            </div>
            <p>Yes! Modify colors, materials, and add text or images.</p>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-money-bill-wave"></i> Payments & Pricing</h2>
            
            <div class="faq-item">
                <i class="fas fa-credit-card" style="color:#28a745;"></i>
                <p class="faq-question">What payment methods do you accept?</p>
            </div>
            <p>We accept credit/debit cards, UPI, PayPal, and digital wallets.</p>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-truck"></i> Shipping & Returns</h2>
            
            <div class="faq-item">
                <i class="fas fa-globe" style="color:#20c997;"></i>
                <p class="faq-question">Do you offer international shipping?</p>
            </div>
            <p>Yes! We ship worldwide. Fees vary by location.</p>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-headset"></i> Support & Contact</h2>
            
            <div class="faq-item">
                <i class="fas fa-envelope" style="color:#fd7e14;"></i>
                <p class="faq-question">How do I contact Unique Bee for support?</p>
            </div>
            <p>Email: <a href="mailto:support@uniquebee.com">support@uniquebee.com</a></p>
            
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-pinterest"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
    </div>
    
    <div class="footer">
        &copy; 2025 Unique Bee. All Rights Reserved.
        <div class="contact-info">
            <span><i class="fas fa-phone-alt"></i> (555) 123-4567</span>
            <span><i class="fas fa-envelope"></i> info@uniquebee.com</span>
            <span><i class="fas fa-map-marker-alt"></i> 123 Creative Way, Design City</span>
        </div>
    </div>
</body>
</html>