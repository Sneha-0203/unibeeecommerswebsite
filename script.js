document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const loginModal = document.getElementById('login-modal');
    const signupModal = document.getElementById('signup-modal');
    const adminLoginModal = document.getElementById('admin-login-modal');
    const loginBtn = document.getElementById('login-dropdown-btn');
    const signupBtn = document.getElementById('signup-dropdown-btn');
    const adminLoginLink = document.getElementById('admin-login-link');
    const loginLink = document.getElementById('login-link');
    const signupLink = document.getElementById('signup-link');
    const closeBtns = document.querySelectorAll('.close');

    // Open modals
    loginBtn.addEventListener('click', function(e) {
        e.preventDefault();
        loginModal.style.display = 'block';
    });

    signupBtn.addEventListener('click', function(e) {
        e.preventDefault();
        signupModal.style.display = 'block';
    });

    adminLoginLink.addEventListener('click', function(e) {
        e.preventDefault();
        loginModal.style.display = 'none';
        adminLoginModal.style.display = 'block';
    });

    loginLink.addEventListener('click', function(e) {
        e.preventDefault();
        signupModal.style.display = 'none';
        loginModal.style.display = 'block';
    });

    signupLink.addEventListener('click', function(e) {
        e.preventDefault();
        loginModal.style.display = 'none';
        signupModal.style.display = 'block';
    });

    // Close modals
    closeBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            loginModal.style.display = 'none';
            signupModal.style.display = 'none';
            adminLoginModal.style.display = 'none';
        });
    });

    window.addEventListener('click', function(e) {
        if (e.target == loginModal || e.target == signupModal || e.target == adminLoginModal) {
            loginModal.style.display = 'none';
            signupModal.style.display = 'none';
            adminLoginModal.style.display = 'none';
        }
    });

    // Featured Products AJAX
    loadFeaturedProducts();

    // Form validation
    const signupForm = document.getElementById('signup-form');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            const password = document.getElementById('signup-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    }

    // Add to cart functionality
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-cart')) {
            e.preventDefault();
            const productId = e.target.getAttribute('data-id');
            addToCart(productId, 1);
        }
    });
});

// Load featured products via AJAX
function loadFeaturedProducts() {
    const featuredProducts = document.getElementById('featured-products');
    if (!featuredProducts) return;

    // Normally this would be an AJAX call to your backend
    // For demo purposes, we'll use hardcoded data
    const products = [
        {
            id: 1,
            name: 'Running Shoes',
            price: 79.99,
            image: 'images/products/running-shoes.jpg'
        },
        {
            id: 2,
            name: 'Casual Sneakers',
            price: 59.99,
            image: 'images/products/casual-sneakers.jpg'
        },
        {
            id: 3,
            name: 'Formal Oxfords',
            price: 99.99,
            image: 'images/products/formal-oxfords.jpg'
        },
        {
            id: 4,
            name: 'Sports Trainers',
            price: 69.99,
            image: 'images/products/sports-trainers.jpg'
        }
    ];

    let html = '';
    products.forEach(product => {
        html += `
            <div class="product-card">
                <div class="product-img">
                    <img src="${product.image}" alt="${product.name}">
                </div>
                <div class="product-info">
                    <h3 class="product-title">${product.name}</h3>
                    <div class="product-price">$${product.price.toFixed(2)}</div>
                    <div class="product-actions">
                        <a href="product-details.php?id=${product.id}" class="btn">View Details</a>
                        <button class="btn add-to-cart" data-id="${product.id}">Add to Cart</button>
                    </div>
                </div>
            </div>
        `;
    });

    featuredProducts.innerHTML = html;
}

// Add to cart function
function addToCart(productId, quantity) {
    // In a real app, this would send an AJAX request to your backend
    // For demo purposes, we'll just show an alert
    
    // You could also update a session variable or localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Check if product already exists in cart
    const existingProductIndex = cart.findIndex(item => item.id === productId);
    
    if (existingProductIndex >= 0) {
        // Update quantity if product exists
        cart[existingProductIndex].quantity += quantity;
    } else {
        // Add new product to cart
        cart.push({
            id: productId,
            quantity: quantity
        });
    }
    
    // Save updated cart
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Update cart count in the header
    updateCartCount();
    
    alert('Product added to cart!');
}

// Update cart count
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
    
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = cartCount;
    }
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
});