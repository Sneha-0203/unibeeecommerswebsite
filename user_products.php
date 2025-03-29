<?php
// Start session
session_start();

// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "shoe_store";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Fetch categories for filter sidebar
$categories = [];
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Build the query for products
$query = "SELECT p.id, p.name, p.description, p.base_price as price, p.image, p.category_id, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";

$params = [];

// Add category filter if selected
if ($category_id > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}

// Add search term if provided
if (!empty($search_term)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
}

// Add sorting
switch ($sort_by) {
    case 'price_asc':
        $query .= " ORDER BY p.base_price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.base_price DESC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    case 'name_asc':
    default:
        $query .= " ORDER BY p.name ASC";
        break;
}

// Prepare and execute the query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - ShoeStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional Styles for Products Page */
        .products-container {
            display: flex;
            margin-top: 30px;
        }
        
        .filters-sidebar {
            width: 250px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-right: 30px;
            height: fit-content;
        }
        
        .filters-sidebar h3 {
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .filter-group {
            margin-bottom: 20px;
        }
        
        .filter-group h4 {
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .filter-options a {
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .filter-options a:hover {
            color: var(--primary-color);
        }
        
        .filter-options a.active {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .filter-options a i {
            margin-right: 8px;
        }
        
        .products-wrapper {
            flex: 1;
        }
        
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .products-count {
            font-size: 16px;
            font-weight: 500;
        }
        
        .sort-options {
            display: flex;
            align-items: center;
        }
        
        .sort-options label {
            margin-right: 10px;
        }
        
        .sort-options select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
        }
        
        .search-form {
            margin-bottom: 20px;
            position: relative;
        }
        
        .search-form input {
            width: 100%;
            padding: 12px 15px;
            padding-right: 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .search-form button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
            font-size: 18px;
        }
        
        .search-form button:hover {
            color: var(--primary-color);
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .no-products {
            text-align: center;
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .no-products i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-products h3 {
            margin-bottom: 10px;
            color: #333;
            font-size: 20px;
        }
        
        .no-products p {
            color: #777;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .products-container {
                flex-direction: column;
            }
            
            .filters-sidebar {
                width: 100%;
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .products-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container">
            <div class="brand">
                <a href="index.php">
                    <h1>ShoeStore</h1>
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="user_products.php" class="active">Products</a></li>
                    <li><a href="cart.php">Cart <span id="cart-count">0</span></a></li>
                    <li class="profile-dropdown">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="#" id="profile-btn" class="user-logged-in">
                                <i class="fas fa-user-circle"></i> 
                                <?php echo $_SESSION['user_name']; ?> 
                                <i class="fas fa-caret-down"></i>
                            </a>
                        <?php else: ?>
                            <a href="#" id="profile-btn">
                                <i class="fas fa-user"></i> Profile 
                                <i class="fas fa-caret-down"></i>
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-content">
                            <?php if(!isset($_SESSION['user_id'])): ?>
                                <a href="#" id="login-dropdown-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                                <a href="#" id="signup-dropdown-btn"><i class="fas fa-user-plus"></i> Sign Up</a>
                                <a href="admin_login.php"><i class="fas fa-lock"></i> Admin Login</a>
                            <?php else: ?>
                                <a href="profile.php"><i class="fas fa-id-card"></i> My Profile</a>
                                <a href="user-order_history.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            <?php endif; ?>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Products Section -->
    <section class="section-padding">
        <div class="container">
            <h2 class="section-title">Our Products</h2>
            
            <div class="products-container">
                <!-- Filters Sidebar -->
                <div class="filters-sidebar">
                    <h3>Filters</h3>
                    
                    <div class="search-form">
                        <form action="user_products.php" method="GET">
                            <?php if($category_id > 0): ?>
                                <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                            <?php endif; ?>
                            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                    
                    <div class="filter-group">
                        <h4>Categories</h4>
                        <div class="filter-options">
                            <a href="user_products.php" <?php echo $category_id == 0 ? 'class="active"' : ''; ?>>
                                <i class="fas fa-shoe-prints"></i> All Shoes
                            </a>
                            <?php foreach ($categories as $category): ?>
                            <a href="user_products.php?category=<?php echo $category['id']; ?>" 
                               <?php echo $category_id == $category['id'] ? 'class="active"' : ''; ?>>
                                <i class="fas fa-tag"></i> <?php echo $category['name']; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Products Wrapper -->
                <div class="products-wrapper">
                    <div class="products-header">
                        <div class="products-count">
                            <?php echo count($products); ?> Products Found
                        </div>
                        <div class="sort-options">
                            <label>Sort by:</label>
                            <select name="sort" id="sort-select" onchange="window.location.href=this.value">
                                <option value="user_products.php?sort=name_asc<?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" <?php echo $sort_by == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="user_products.php?sort=name_desc<?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" <?php echo $sort_by == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="user_products.php?sort=price_asc<?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                                <option value="user_products.php?sort=price_desc<?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (!empty($products)): ?>
                        <div class="product-grid">
                            <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                    <span class="product-tag"><?php echo $product['category_name']; ?></span>
                                </div>
                                <div class="product-info">
                                    <h3><?php echo $product['name']; ?></h3>
                                    <p class="product-description"><?php echo $product['description']; ?></p>
                                    <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                                    <div class="product-actions">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                                        <button class="add-to-cart" data-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-products">
                            <i class="fas fa-search"></i>
                            <h3>No Products Found</h3>
                            <p>We couldn't find any products matching your criteria.</p>
                            <a href="user_products.php" class="btn">View All Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>ShoeStore</h3>
                    <p>Your ultimate destination for stylish and comfortable footwear. We bring you the best brands and designs from around the world.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="user_products.php">Products</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="shipping.php">Shipping Policy</a></li>
                        <li><a href="returns.php">Returns & Exchanges</a></li>
                        <li><a href="terms.php">Terms & Conditions</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Info</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Shoe Street, Fashion City</li>
                        <li><i class="fas fa-phone"></i> +1 234 567 8900</li>
                        <li><i class="fas fa-envelope"></i> info@shoestore.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 ShoeStore. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        const cartCount = document.getElementById('cart-count');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                
                // AJAX request to add item to cart
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count
                        cartCount.textContent = data.cart_count;
                        
                        // Show notification
                        alert('Product added to cart!');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
        
        // Initialize cart count on page load
        fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                cartCount.textContent = data.count;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    </script>
</body>
</html>