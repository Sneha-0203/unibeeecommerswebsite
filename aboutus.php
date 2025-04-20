<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Unique Bee</title>
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
            color: #4a90e2;
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
            text-align: justify;
            margin-bottom: 20px;
        }
        ul {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        ul li {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        ul li:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        ul li i {
            margin-right: 15px;
            font-size: 24px;
            min-width: 24px;
            text-align: center;
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
            ul {
                grid-template-columns: 1fr;
            }
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
    <div class="navbar"> <span class="brand"><i class="fas fa-shoe-prints"></i> UNIBEE</span>
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="product.php"><i class="fas fa-shopping-bag"></i> Products</a>
        <a href="aboutus.php"><i class="fas fa-info-circle"></i> About Us</a>
        <a href="contact.html"><i class="fas fa-envelope"></i> Contact</a>
    </div>
    <div class="container">
        <div class="section">
            <h1><i class="fas fa-shoe-prints"></i> About Unique Bee</h1>
            <p>At <strong>Unique Bee</strong>, we believe that footwear is more than just a necessity—it's an extension of personality, creativity, and self-expression...</p>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-bullseye"></i> Our Mission</h2>
            <ul>
                <li><i class="fas fa-paint-brush" style="color:#ff6f61;"></i> <strong>Empower Creativity:</strong> Customize every detail of your shoes.</li>
                <li><i class="fas fa-tshirt" style="color:#4a90e2;"></i> <strong>Enhance Comfort & Style:</strong> Use top-quality materials.</li>
                <li><i class="fas fa-leaf" style="color:#28a745;"></i> <strong>Promote Sustainability:</strong> Reduce waste with made-to-order production.</li>
                <li><i class="fas fa-users" style="color:#ffcc33;"></i> <strong>Foster a Community:</strong> Connect with like-minded individuals.</li>
                <li><i class="fas fa-heart" style="color:#e83e8c;"></i> <strong>Spread Joy:</strong> Help people express their unique personality.</li>
            </ul>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-lightbulb"></i> What Makes Us Different?</h2>
            <ul>
                <li><i class="fas fa-shoe-prints" style="color:#ff6f61;"></i> Select from various base models like sneakers, sports shoes, and casual wear.</li>
                <li><i class="fas fa-palette" style="color:#4a90e2;"></i> Customize colors, patterns, laces, and even the sole design.</li>
                <li><i class="fas fa-user-edit" style="color:#28a745;"></i> Add personal touches like text, artwork, or images.</li>
                <li><i class="fas fa-eye" style="color:#ffcc33;"></i> Preview your design before ordering.</li>
                <li><i class="fas fa-medal" style="color:#6f42c1;"></i> Receive a premium quality product that stands out.</li>
                <li><i class="fas fa-truck" style="color:#fd7e14;"></i> Enjoy fast and secure worldwide shipping.</li>
            </ul>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-hands-helping"></i> Join the Movement</h2>
            <p>We are not just selling shoes—we are creating a movement where everyone can wear their creativity with pride...</p>
            <p>Join us today and make every step truly yours! <i class="fas fa-rocket"></i></p>
            
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