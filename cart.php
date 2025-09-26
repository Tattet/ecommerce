<?php
require_once 'include/config.php';

// Protection check: If the user is NOT logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$total_price = 0;

// Handle "Update Quantity" or "Remove Item"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $cart_item_id = intval($_POST['cart_item_id']);
    
    // Check if the cart item belongs to the current user
    $stmt_check = $pdo->prepare("
        SELECT ci.cart_id FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.cart_id
        WHERE ci.cart_item_id = ? AND c.user_id = ?
    ");
    $stmt_check->execute([$cart_item_id, $user_id]);
    if ($stmt_check->rowCount() > 0) {
        if ($_POST['action'] == 'update') {
            $quantity = intval($_POST['quantity']);
            if ($quantity > 0) {
                $stmt_update = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
                $stmt_update->execute([$quantity, $cart_item_id]);
                $message = "Cart updated successfully!";
            } else {
                $message = "Quantity must be a positive number.";
            }
        } elseif ($_POST['action'] == 'remove') {
            $stmt_remove = $pdo->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
            $stmt_remove->execute([$cart_item_id]);
            $message = "Item removed from cart.";
        }
    } else {
        $message = "Error: Invalid request.";
    }
}

// Fetch the user's cart items
$stmt_cart = $pdo->prepare("
    SELECT ci.cart_item_id, ci.quantity, p.product_name, p.price, p.image_url
    FROM cart_items ci
    JOIN carts c ON ci.cart_id = c.cart_id
    JOIN products p ON ci.product_id = p.product_id
    WHERE c.user_id = ?
");
$stmt_cart->execute([$user_id]);
$cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

// Calculate total price
foreach ($cart_items as $item) {
    $total_price += $item['quantity'] * $item['price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* E-commerce Store Styles */
/* Theme Colors:
    Primary: #FAF9EE (Light cream)
    Secondary: #A2AF9B (Sage green)
    Accent: #DCCFC0 (Light tan)
    Neutral: #EEEEEE (Light gray)
*/

:root {
    --primary-color: #FAF9EE;
    --secondary-color: #A2AF9B;
    --accent-color: #DCCFC0;
    --neutral-color: #EEEEEE;
    --text-dark: #2c3e50;
    --text-medium: #5a6c7d;
    --text-light: #7f8c8d;
    --success-color: #27ae60;
    --error-color: #e74c3c;
    --warning-color: #f39c12;
    --white: #ffffff;
    --shadow-light: rgba(0, 0, 0, 0.05);
    --shadow-medium: rgba(0, 0, 0, 0.1);
    --shadow-heavy: rgba(0, 0, 0, 0.15);
}

/* Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--neutral-color) 100%);
    color: var(--text-dark);
    line-height: 1.6;
    min-height: 100vh;
}

/* Navigation Styles */
nav {
    background: rgba(250, 249, 238, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--accent-color);
    box-shadow: 0 2px 10px var(--shadow-light);
}

nav .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

nav a {
    text-decoration: none;
    color: var(--text-dark);
    font-weight: 500;
    transition: all 0.3s ease;
}

nav a:hover {
    color: var(--secondary-color);
}

/* Navigation Links */
.nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    background: transparent;
    color: var(--text-medium);
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.nav-link:hover {
    background: var(--accent-color);
    color: var(--text-dark);
    transform: translateY(-1px);
}

.nav-link.active-nav-link,
.active-nav-link {
    background: var(--secondary-color);
    color: var(--white);
    border-color: var(--secondary-color);
}

.nav-link svg {
    width: 1.25rem;
    height: 1.25rem;
}

/* Content Sections */
.content-section {
    background: var(--white);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px var(--shadow-light);
    border: 1px solid var(--accent-color);
    margin-bottom: 2rem;
}

.content-section h1,
.content-section h2 {
    color: var(--text-dark);
    border-bottom: 3px solid var(--secondary-color);
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

.content-section h1 {
    font-size: 2rem;
    font-weight: 700;
}

.content-section h2 {
    font-size: 1.5rem;
    font-weight: 600;
}

/* Button Styles */
.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
    line-height: 1.5;
    box-shadow: 0 2px 8px var(--shadow-light);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px var(--shadow-medium);
}

.btn-primary {
    background: var(--secondary-color);
    color: var(--white);
    border: 2px solid var(--secondary-color);
}

.btn-primary:hover {
    background: #8a9688;
    border-color: #8a9688;
}

.btn-danger {
    background: var(--error-color);
    color: var(--white);
    border: 2px solid var(--error-color);
}

.btn-danger:hover {
    background: #c0392b;
    border-color: #c0392b;
}

.btn-secondary {
    background: var(--accent-color);
    color: var(--text-dark);
    border: 2px solid var(--accent-color);
}

.btn-secondary:hover {
    background: #d4c4b0;
    border-color: #d4c4b0;
}

/* Form Styles */
input[type="text"],
input[type="number"],
input[type="email"],
input[type="password"],
textarea,
select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--accent-color);
    border-radius: 8px;
    font-size: 1rem;
    background: var(--white);
    color: var(--text-dark);
    transition: all 0.3s ease;
}

input:focus,
textarea:focus,
select:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(162, 175, 155, 0.1);
}

input[type="number"] {
    width: auto;
    min-width: 4rem;
}

/* Message Styles */
.message {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 500;
    border-left: 4px solid;
    background: var(--white);
    box-shadow: 0 2px 8px var(--shadow-light);
}

.message-success {
    border-left-color: var(--success-color);
    background: #d5f4e6;
    color: #155724;
}

.message-error {
    border-left-color: var(--error-color);
    background: #f8d7da;
    color: #721c24;
}

.message-warning {
    border-left-color: var(--warning-color);
    background: #fff3cd;
    color: #856404;
}

/* Product Card Styles */
.product-card {
    background: var(--white);
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 4px 15px var(--shadow-light);
    transition: all 0.3s ease;
    border: 1px solid var(--accent-color);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px var(--shadow-medium);
    border-color: var(--secondary-color);
}

.product-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 1rem;
    border: 2px solid var(--accent-color);
    transition: border-color 0.3s ease;
}

.product-card:hover .product-image {
    border-color: var(--secondary-color);
}

.product-card h3 {
    color: var(--text-dark);
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    flex-grow: 1;
}

.product-card p {
    color: var(--text-medium);
    margin-bottom: 1rem;
}

.add-to-cart-btn {
    background: var(--secondary-color);
    color: var(--white);
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    margin-top: auto;
}

.add-to-cart-btn:hover {
    background: #8a9688;
    transform: translateY(-1px);
}

/* Cart Item Styles */
.cart-item {
    background: var(--white);
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 15px var(--shadow-light);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    border: 1px solid var(--accent-color);
    transition: all 0.3s ease;
}

.cart-item:hover {
    box-shadow: 0 6px 25px var(--shadow-medium);
    border-color: var(--secondary-color);
}

/* --- Optimized Image Class: object-fit: cover ensures the image fills the container --- */
.cart-item-image {
    width: 100px;
    height: 100px;
    object-fit: cover; 
    border-radius: 12px;
    border: 2px solid var(--accent-color);
    flex-shrink: 0;
}

.cart-item h3 {
    color: var(--text-dark);
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.cart-item p {
    color: var(--text-medium);
    margin: 0;
}

.cart-item form {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.cart-item input[type="number"] {
    width: 4rem;
    text-align: center;
    padding: 0.5rem;
}

/* Order Summary Styles */
.order-summary {
    background: linear-gradient(135deg, var(--accent-color) 0%, var(--neutral-color) 100%);
    padding: 2rem;
    border-radius: 16px;
    border: 2px solid var(--secondary-color);
    box-shadow: 0 6px 25px var(--shadow-medium);
}

.order-total {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #8a9688 100%);
    color: var(--white);
    padding: 1.5rem;
    border-radius: 12px;
    font-size: 1.5rem;
    font-weight: 700;
    text-align: center;
    box-shadow: 0 4px 15px var(--shadow-medium);
}

/* Order Detail Styles */
.order-status {
    background: linear-gradient(135deg, var(--success-color) 0%, #27ae60 100%);
    color: var(--white);
    padding: 1rem 1.5rem;
    border-radius: 12px;
    text-align: center;
    font-weight: 600;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px var(--shadow-medium);
}

.order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: var(--white);
    border-radius: 12px;
    border: 1px solid var(--accent-color);
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px var(--shadow-light);
    transition: all 0.3s ease;
}

.order-item:hover {
    box-shadow: 0 4px 15px var(--shadow-medium);
    border-color: var(--secondary-color);
}

.order-item img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid var(--accent-color);
    flex-shrink: 0;
}

.order-item-details {
    flex-grow: 1;
}

.order-item-details h4 {
    color: var(--text-dark);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.order-item-details p {
    color: var(--text-medium);
    margin: 0.125rem 0;
}

.order-item-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-dark);
}

/* Grid Layouts */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-section {
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .cart-item {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
        padding: 1.5rem;
    }
    
    .cart-item-image {
        width: 80px;
        height: 80px;
    }
    
    .cart-item form {
        justify-content: center;
    }
    
    .order-item {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .product-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    nav .container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .nav-link {
        padding: 0.5rem 1rem;
    }
}

@media (max-width: 480px) {
    .content-section {
        padding: 1rem;
    }
    
    .btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
    }
    
    .cart-item {
        padding: 1rem;
    }
    
    .order-item {
        padding: 1rem;
    }
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.slide-in {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from { transform: translateX(-20px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Utility Classes */
.text-center { text-align: center; }
.text-right { text-align: right; }
.font-bold { font-weight: 700; }
.font-semibold { font-weight: 600; }
.mb-4 { margin-bottom: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }
.mt-4 { margin-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }
.p-4 { padding: 1rem; }
.rounded { border-radius: 8px; }

/* Special Effects */
.glass-effect {
    background: rgba(250, 249, 238, 0.8);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.gradient-bg {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 50%, var(--neutral-color) 100%);
}

/* Loading States */
.loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid var(--secondary-color);
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <nav class="bg-white bg-opacity-90 backdrop-blur-sm shadow-lg p-4 sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="text-2xl font-bold text-gray-800">
                <a href="index.php">My Store</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="cart.php" class="nav-link active-nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.182 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Cart
                </a>
                <a href="auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-600 transition-colors">Logout</a>
            </div>
        </div>
    </nav>
    <main class="flex-grow container mx-auto p-6 md:p-8">
        <?php if ($message): ?>
            <div class="message message-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="content-section">
            <h1 class="text-3xl font-bold mb-6 text-gray-800 border-b-2 pb-2">Your Shopping Cart</h1>

            <?php if (count($cart_items) > 0): ?>
                <div class="space-y-6 mb-8">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://via.placeholder.com/100.png?text=Product'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                 class="cart-item-image">
                            <div class="flex-grow">
                                <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <!-- Changed currency to Peso (₱) -->
                                <p class="text-gray-600">Price: ₱<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></p>
                            </div>
                            <form method="post" class="flex items-center space-x-4">
                                <input type="hidden" name="cart_item_id" value="<?php echo htmlspecialchars($item['cart_item_id']); ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" class="w-16 text-center border rounded-md">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <button type="submit" name="action" value="remove" class="btn btn-danger">Remove</button>
                            </form>
                            <!-- Changed currency to Peso (₱) -->
                            <span class="text-xl font-bold">₱<?php echo htmlspecialchars(number_format($item['price'] * $item['quantity'], 2)); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-between items-center bg-gray-100 p-6 rounded-lg font-bold">
                    <span class="text-2xl text-gray-700">Total:</span>
                    <!-- Changed currency to Peso (₱) -->
                    <span class="text-3xl text-gray-900">₱<?php echo htmlspecialchars(number_format($total_price, 2)); ?></span>
                </div>
                
                <div class="mt-8 text-center">
                    <a href="order.php" class="btn btn-primary text-2xl px-8 py-4">Proceed to Checkout</a>
                </div>

            <?php else: ?>
                <p class="text-center text-gray-500 text-lg">Your cart is empty.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
