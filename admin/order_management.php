<?php
require_once '../include/config.php';

// Protection check: If the user is NOT logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';
$message_type = '';
$statuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];

// Handle Status Update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if ($order_id && in_array($new_status, $statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->execute([$new_status, $order_id]);
            $message = "Order #$order_id status updated to " . ucfirst($new_status) . " successfully!";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Error updating order status: " . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = "Invalid input for status update.";
        $message_type = 'error';
    }
}

// Handle Delete Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    
    if ($order_id) {
        try {
            $pdo->beginTransaction();
            
            // Delete order details first (due to foreign key constraint)
            $stmt = $pdo->prepare("DELETE FROM order_details WHERE order_id = ?");
            $stmt->execute([$order_id]);
            
            // Delete the order
            $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
            $stmt->execute([$order_id]);
            
            $pdo->commit();
            $message = "Order #$order_id deleted successfully!";
            $message_type = 'success';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Error deleting order: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Fetch all orders from the database with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$count_stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

// Fetch orders with pagination
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name AS customer_name, u.email AS customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    ORDER BY o.created_at DESC 
    LIMIT ? OFFSET ?
");

// --- FIX APPLIED HERE ---
// Explicitly bind LIMIT and OFFSET parameters as integers to prevent PDO from quoting them as strings,
// which causes a MySQL syntax error (line 80 in original file context).
$stmt->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
// --- END FIX ---

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
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
         3. FORM ELEMENTS & BUTTONS
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
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .btn:active {
            transform: scale(0.98);
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

        /* Status Update Form Styling for smaller screens */
        @media (max-width: 768px) {
            .status-form {
                flex-direction: column;
                align-items: flex-start;
            }
            .status-form select {
                margin-bottom: 8px;
            }
        }
        /* Additional styles for order management */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-paid { background-color: #dbeafe; color: #1e40af; }
        .status-shipped { background-color: #e0e7ff; color: #3730a3; }
        .status-delivered { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        
        .modal-overlay {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }
        
        .pagination-btn {
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border: 1px solid var(--accent-color);
            border-radius: 0.5rem;
            background: var(--white);
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .pagination-btn:hover {
            background: var(--secondary-color);
            color: var(--white);
            border-color: var(--secondary-color);
        }
        
        .pagination-btn.active {
            background: var(--secondary-color);
            color: var(--white);
            border-color: var(--secondary-color);
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen bg-gray-100 font-sans">
    <nav class="bg-white bg-opacity-90 backdrop-blur-sm shadow-lg p-4 sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="text-2xl font-bold text-gray-800">Admin Panel</div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="category.php" class="nav-link">Category</a>
                <a href="product.php" class="nav-link">Product</a>
                <a href="order_management.php" class="nav-link active-nav-link">Order</a>
                <a href="../auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-600 transition-colors">Logout</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto p-6 md:p-8">
        <div class="content-section">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Order Management</h1>
                <div class="text-sm text-gray-600">
                    Total Orders: <?php echo $total_orders; ?>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message message-<?php echo $message_type; ?> mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse bg-white rounded-lg shadow-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    No orders found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            #<?php echo htmlspecialchars($order['order_id']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">
                                            ₱<?php echo number_format($order['total_amount'], 2); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo date('g:i A', strtotime($order['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="POST" class="flex items-center space-x-2">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <select name="status" class="text-sm px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?php echo $status; ?>" <?php echo ($order['status'] == $status) ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($status); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                        <div class="mt-1">
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)" 
                                                     class="btn btn-primary text-xs px-3 py-1">
                                                View
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $order['order_id']; ?>)" 
                                                     class="btn btn-danger text-xs px-3 py-1">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-6 flex justify-center">
                    <div class="flex items-center space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn">&larr; Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn">Next &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Order Details Modal -->
    <div id="orderModal" class="fixed inset-0 modal-overlay hidden items-center justify-center p-4 z-50">
        <div class="bg-white w-full max-w-4xl max-h-90vh rounded-xl shadow-2xl overflow-hidden">
            <div class="flex justify-between items-center px-6 py-4 bg-gray-50 border-b">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Order Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <div id="modalBody" class="p-6 overflow-y-auto max-h-96">
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-4 text-gray-600">Loading...</p>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t text-right">
                <button onclick="closeModal()" class="btn btn-secondary px-4 py-2">Close</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 modal-overlay hidden items-center justify-center p-4 z-50">
        <div class="bg-white w-full max-w-md rounded-xl shadow-2xl">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Confirm Delete</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to delete this order? This action cannot be undone.</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeleteModal()" class="btn btn-secondary px-4 py-2">Cancel</button>
                    <form id="deleteForm" method="POST" class="inline">
                        <input type="hidden" name="order_id" id="deleteOrderId">
                        <button type="submit" name="delete_order" class="btn btn-danger px-4 py-2">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentOrderId = null;

        async function viewOrderDetails(orderId) {
            currentOrderId = orderId;
            const modal = document.getElementById('orderModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modalTitle.textContent = `Order #${orderId} Details`;
            
            // Show loading
            modalBody.innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-4 text-gray-600">Loading order details...</p>
                </div>
            `;

            try {
                // NOTE: 'get_order_details.php' is assumed to exist in the same directory or accessible path
                const response = await fetch(`get_order_details.php?order_id=${orderId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    modalBody.innerHTML = `<div class="text-center py-8 text-red-500">${data.error}</div>`;
                    return;
                }

                const order = data.order;
                const items = data.items;
                
                let itemsHtml = '';
                if (items && items.length > 0) {
                    itemsHtml = items.map(item => `
                        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                            <img src="${item.image_url || 'https://via.placeholder.com/60x60?text=No+Image'}" 
                                 alt="${item.product_name}" 
                                 class="w-15 h-15 object-cover rounded-lg"
                                 onerror="this.src='https://via.placeholder.com/60x60?text=No+Image'">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900">${item.product_name}</h4>
                                <p class="text-sm text-gray-600">
                                    $${parseFloat(item.price_each).toFixed(2)} × ${item.quantity} = 
                                    <span class="font-semibold">$${(parseFloat(item.price_each) * parseInt(item.quantity)).toFixed(2)}</span>
                                </p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    itemsHtml = '<p class="text-center text-gray-500 py-4">No items found.</p>';
                }
                
                modalBody.innerHTML = `
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-semibold text-gray-700 mb-2">Customer Information</h4>
                                <p><span class="font-medium">Name:</span> ${order.customer_name}</p>
                                <p><span class="font-medium">Email:</span> ${order.customer_email}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-700 mb-2">Order Information</h4>
                                <p><span class="font-medium">Date:</span> ${new Date(order.created_at).toLocaleDateString()}</p>
                                <p><span class="font-medium">Status:</span> 
                                    <span class="status-badge status-${order.status}">${order.status}</span>
                                </p>
                                <p><span class="font-medium">Total:</span> 
                                    <span class="text-lg font-bold text-green-600">$${parseFloat(order.total_amount).toFixed(2)}</span>
                                </p>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-4">Order Items</h4>
                            <div class="space-y-3">
                                ${itemsHtml}
                            </div>
                        </div>
                    </div>
                `;
                
            } catch (error) {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-red-500 font-semibold mb-2">Error loading order details</p>
                        <p class="text-gray-600 text-sm">${error.message}</p>
                    </div>
                `;
            }
        }

        function closeModal() {
            document.getElementById('orderModal').classList.add('hidden');
            document.getElementById('orderModal').classList.remove('flex');
        }

        function confirmDelete(orderId) {
            document.getElementById('deleteOrderId').value = orderId;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }

        // Close modals when clicking outside
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>
