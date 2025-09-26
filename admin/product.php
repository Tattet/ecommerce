<?php
require_once '../include/config.php';

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';
$is_error = false;
$editing_product = null;

// Handle CRUD actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $product_name = trim($_POST['product_name']);
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $image_url = trim($_POST['image_url']);

        if (empty($product_name) || empty($price) || $category_id <= 0) {
            $message = "Please fill in all required fields.";
            $is_error = true;
        } else {
            $sql = "INSERT INTO products (product_name, category_id, price, image_url) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$product_name, $category_id, $price, $image_url])) {
                $message = "Product added successfully!";
            } else {
                $message = "Failed to add product.";
                $is_error = true;
            }
        }
    } elseif (isset($_POST['update_product'])) {
        $product_id = intval($_POST['product_id']);
        $product_name = trim($_POST['product_name']);
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $image_url = trim($_POST['image_url']);
        
        if (empty($product_name) || empty($price) || $category_id <= 0 || $product_id <= 0) {
            $message = "Please fill in all required fields.";
            $is_error = true;
        } else {
            $sql = "UPDATE products SET product_name = ?, category_id = ?, price = ?, image_url = ? WHERE product_id = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$product_name, $category_id, $price, $image_url, $product_id])) {
                $message = "Product updated successfully!";
            } else {
                $message = "Failed to update product.";
                $is_error = true;
            }
        }
    } elseif (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);

        if ($product_id <= 0) {
            $message = "Invalid product ID.";
            $is_error = true;
        } else {
            $sql = "DELETE FROM products WHERE product_id = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$product_id])) {
                $message = "Product deleted successfully!";
            } else {
                $message = "Failed to delete product.";
                $is_error = true;
            }
        }
    }
}

// Check for edit request
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt_edit = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt_edit->execute([$edit_id]);
    $editing_product = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    if (!$editing_product) {
        $message = "Product not found.";
        $is_error = true;
    }
}

// Fetch all categories
$stmt_categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Fetch all products
$stmt_products = $pdo->query("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id ORDER BY p.created_at DESC");
$products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../asset/admin.css">
    <style>
        /* ----------------------------------------------------------------
    1. BASE STYLES & TYPOGRAPHY
    ---------------------------------------------------------------- */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family: 'Inter', sans-serif;
    color: #333;
    background: linear-gradient(to bottom right, #e0f7fa, #c5f4f8);
    min-height: 100vh;
}
h1, h2 {
    color: #007bff;
    text-align: center;
    margin-bottom: 20px;
}
a {
    color: #007bff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}

/* ----------------------------------------------------------------
    2. NAVBAR & MAIN LAYOUT
    ---------------------------------------------------------------- */
.nav-link {
    transition: all 0.3s ease;
    position: relative;
    padding: 1rem;
}
.nav-link::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    background-color: #007bff;
    transition: width 0.3s ease;
}
.nav-link:hover::after {
    width: 100%;
}
.active-nav-link {
    font-weight: 700;
    color: #0056b3;
}
.active-nav-link::after {
    width: 100%;
}
.content-section {
    background-color: #ffffff;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}
.table-container {
    overflow-x: auto;
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table th, .table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}
.table th {
    background-color: #f3f4f6;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    color: #555;
}
.table tr:hover {
    background-color: #f9fafb;
}

/* ----------------------------------------------------------------
    3. FORM ELEMENTS
    ---------------------------------------------------------------- */
.form-group {
    margin-bottom: 1.5rem;
}
.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
}
.form-input, .form-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ccc;
    border-radius: 8px;
    transition: all 0.2s ease;
}
.form-input:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    outline: none;
}
.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease;
}
.btn-primary {
    background-color: #007bff;
    color: white;
}
.btn-primary:hover {
    background-color: #0056b3;
}
.btn-danger {
    background-color: #dc3545;
    color: white;
}
.btn-danger:hover {
    background-color: #c82333;
}
.message {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 8px;
    font-weight: 600;
    text-align: center;
}
.message-success {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}
.message-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c2c7;
}

    </style>
</head>
<body class="flex flex-col min-h-screen">
    <nav class="bg-white bg-opacity-90 backdrop-blur-sm shadow-lg p-4 sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="text-2xl font-bold text-gray-800">Admin Panel</div>
            <div class="space-x-4">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="category.php" class="nav-link">Category</a>
                <a href="product.php" class="nav-link active-nav-link">Product</a>
                <a href="order_management.php" class="nav-link">Order</a>
                <a href="../auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-600 transition-colors">Logout</a>
            </div>
        </div>
    </nav>
    <main class="flex-grow container mx-auto p-6 md:p-8">
        <div class="content-section">
            <h1 class="text-3xl font-bold mb-6 text-gray-800 border-b-2 pb-2">Product Management</h1>
            <?php if ($message): ?>
                <div class="message <?php echo $is_error ? 'message-error' : 'message-success'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">
                    <?php echo $editing_product ? 'Edit Product' : 'Add New Product'; ?>
                </h3>
                <form action="product.php" method="post" class="space-y-4">
                    <?php if ($editing_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($editing_product['product_id']); ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="product-name" class="form-label">Product Name</label>
                        <input type="text" id="product-name" name="product_name" class="form-input" required value="<?php echo htmlspecialchars($editing_product['product_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="category-id" class="form-label">Category</label>
                        <select id="category-id" name="category_id" class="form-select" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category_id']); ?>"
                                    <?php echo ($editing_product && $editing_product['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" id="price" name="price" class="form-input" required value="<?php echo htmlspecialchars($editing_product['price'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="image-url" class="form-label">Image URL</label>
                        <input type="url" id="image-url" name="image_url" class="form-input" value="<?php echo htmlspecialchars($editing_product['image_url'] ?? ''); ?>">
                    </div>
                    <div>
                        <button type="submit" name="<?php echo $editing_product ? 'update_product' : 'add_product'; ?>" class="w-full btn btn-primary">
                            <?php echo $editing_product ? 'Update Product' : 'Add Product'; ?>
                        </button>
                    </div>
                    <?php if ($editing_product): ?>
                        <div class="text-center mt-4">
                            <a href="product.php" class="text-gray-500 hover:text-gray-800">Cancel Edit</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div>
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Existing Products</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td>₱<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                                    <td class="text-right flex space-x-2 justify-end">
                                        <a href="product.php?edit=<?php echo htmlspecialchars($product['product_id']); ?>" class="btn px-3 py-1 text-sm bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">Edit</a>
                                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                            <button type="submit" name="delete_product" class="btn px-3 py-1 text-sm bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
