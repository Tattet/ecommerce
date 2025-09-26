<?php
// Include necessary files
require_once '../include/config.php';
// Check if the user is logged in as an admin
// For this example, we'll assume a user with id=1 is an admin.
// In a real-world app, you would check a session variable like $_SESSION['is_admin']
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$stmt_user_count = $pdo->query("SELECT COUNT(*) FROM users");
$user_count = $stmt_user_count->fetchColumn();

$stmt_product_count = $pdo->query("SELECT COUNT(*) FROM products");
$product_count = $stmt_product_count->fetchColumn();

$stmt_order_count = $pdo->query("SELECT COUNT(*) FROM orders");
$order_count = $stmt_order_count->fetchColumn();

// Fetch 5 most recent users for a quick glance
$stmt_recent_users = $pdo->query("SELECT full_name, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt_recent_users->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
    <style>
        .card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #4b5563;
        }
        .card-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #007bff;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <nav class="bg-white bg-opacity-90 backdrop-blur-sm shadow-lg p-4 sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="text-2xl font-bold text-gray-800">Admin Panel</div>
            <div class="space-x-4">
                <a href="dashboard.php" class="nav-link active-nav-link">Dashboard</a>
                <a href="category.php" class="nav-link">Category</a>
                <a href="product.php" class="nav-link">Product</a>
                <a href="order_management.php" class="nav-link">Order</a>
                <a href="../auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-600 transition-colors">Logout</a>
            </div>
        </div>
    </nav>
    <main class="flex-grow container mx-auto p-6 md:p-8">
        <h1 class="text-4xl font-bold mb-8 text-gray-800">Welcome, Admin!</h1>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card">
                <div class="card-title">Total Users</div>
                <div class="card-value"><?php echo $user_count; ?></div>
            </div>
            <div class="card">
                <div class="card-title">Total Products</div>
                <div class="card-value"><?php echo $product_count; ?></div>
            </div>
            <div class="card">
                <div class="card-title">Total Orders</div>
                <div class="card-value"><?php echo $order_count; ?></div>
            </div>
        </div>
        <div class="content-section">
            <h2 class="text-2xl font-bold mb-4 text-gray-800">Recent Users</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
