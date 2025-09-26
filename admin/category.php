<?php
require_once '../include/config.php';

// Check if the user is logged in as an admin (assuming all logged-in users are admins in this context)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';
$is_error = false;

// --- CREATE (Add Category) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    if (!empty($category_name)) {
        try {
            $sql = "INSERT INTO categories (category_name) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$category_name])) {
                $message = "Category added successfully!";
            } else {
                $message = "Failed to add category.";
                $is_error = true;
            }
        } catch (PDOException $e) {
            // Assuming a UNIQUE constraint on category_name causes this error
            $message = "Error: Category name already exists.";
            $is_error = true;
        }
    } else {
        $message = "Category name cannot be empty.";
        $is_error = true;
    }
}

// --- DELETE (Delete Category) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_category'])) {
    $category_id = intval($_POST['category_id']);

    if ($category_id > 0) {
        try {
            // Check for product association (good practice to prevent orphaned records)
            $check_sql = "SELECT COUNT(*) FROM products WHERE category_id = ?";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$category_id]);
            $product_count = $check_stmt->fetchColumn();

            if ($product_count > 0) {
                $message = "Cannot delete category: $product_count product(s) are using it. Please reassign or delete the associated products first.";
                $is_error = true;
            } else {
                $sql = "DELETE FROM categories WHERE category_id = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$category_id])) {
                    $message = "Category deleted successfully!";
                } else {
                    $message = "Failed to delete category.";
                    $is_error = true;
                }
            }
        } catch (PDOException $e) {
            $message = "Database error during deletion.";
            $is_error = true;
        }
    } else {
        $message = "Invalid category ID for deletion.";
        $is_error = true;
    }
}

// --- UPDATE (Edit Category) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $category_id = intval($_POST['category_id']);
    $category_name = trim($_POST['category_name']);

    if ($category_id > 0 && !empty($category_name)) {
        try {
            $sql = "UPDATE categories SET category_name = ? WHERE category_id = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$category_name, $category_id])) {
                $message = "Category updated successfully!";
            } else {
                $message = "Failed to update category.";
                $is_error = true;
            }
        } catch (PDOException $e) {
            // Check for unique constraint violation
            if ($e->getCode() == '23000' || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                 $message = "Error: Category name already exists.";
            } else {
                 $message = "Database error during update.";
            }
            $is_error = true;
        }
    } else {
        $message = "Category ID or name is invalid for update.";
        $is_error = true;
    }
}

// --- READ (Fetch Categories) ---
// Re-fetch categories after any operation to display the latest list
$stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add lucide icons for action buttons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
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
.btn-edit {
    background-color: #ffc107;
    color: #333;
    padding: 0.5rem 0.75rem;
}
.btn-edit:hover {
    background-color: #e0a800;
}
.btn-action-delete {
    background-color: #dc3545;
    color: white;
    padding: 0.5rem 0.75rem;
}
.btn-action-delete:hover {
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

/* ----------------------------------------------------------------
   4. MODAL STYLES
   ---------------------------------------------------------------- */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: none; /* Hidden by default */
    justify-content: center;
    align-items: center;
    z-index: 1000;
}
.modal-content {
    background-color: white;
    padding: 2rem;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <nav class="bg-white bg-opacity-90 backdrop-blur-sm shadow-lg p-4 sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="text-2xl font-bold text-gray-800">Admin Panel</div>
            <div class="space-x-4">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="category.php" class="nav-link active-nav-link">Category</a>
                <a href="product.php" class="nav-link">Product</a>
                <a href="order_management.php" class="nav-link">Order</a>
                <a href="../auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-600 transition-colors">Logout</a>
            </div>
        </div>
    </nav>
    <main class="flex-grow container mx-auto p-6 md:p-8">
        <div class="content-section">
            <h1 class="text-3xl font-bold mb-6 text-gray-800 border-b-2 pb-2">Category Management</h1>
            <?php if ($message): ?>
                <div class="message <?php echo $is_error ? 'message-error' : 'message-success'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Add New Category</h3>
                <form action="" method="post" class="space-y-4">
                    <div class="form-group">
                        <label for="category-name" class="form-label">Category Name</label>
                        <input type="text" id="category-name" name="category_name" class="form-input" required>
                    </div>
                    <div>
                        <button type="submit" name="add_category" class="w-full btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
            <div>
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Existing Categories</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['category_id']); ?></td>
                                    <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['created_at']); ?></td>
                                    <td class="flex space-x-2">
                                        <!-- Edit Button -->
                                        <button 
                                            type="button" 
                                            class="btn btn-edit text-sm inline-flex items-center space-x-1" 
                                            onclick="openEditModal(<?php echo htmlspecialchars($category['category_id']); ?>, '<?php echo htmlspecialchars($category['category_name'], ENT_QUOTES); ?>')"
                                        >
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                            <span class="hidden sm:inline">Edit</span>
                                        </button>

                                        <!-- Delete Form -->
                                        <form action="" method="post" class="inline" onsubmit="return confirmDelete(<?php echo htmlspecialchars($category['category_id']); ?>)">
                                            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category['category_id']); ?>">
                                            <button type="submit" name="delete_category" class="btn btn-action-delete text-sm inline-flex items-center space-x-1">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                <span class="hidden sm:inline">Delete</span>
                                            </button>
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

    <!-- Edit Category Modal -->
    <div id="edit-modal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="text-2xl font-bold mb-4 text-gray-800">Edit Category</h3>
            <form action="" method="post" class="space-y-4">
                <input type="hidden" name="category_id" id="edit-category-id">
                <div class="form-group">
                    <label for="edit-category-name" class="form-label">Category Name</label>
                    <input type="text" id="edit-category-name" name="category_name" class="form-input" required>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" class="btn bg-gray-300 text-gray-800 hover:bg-gray-400" onclick="closeEditModal()">
                        Cancel
                    </button>
                    <button type="submit" name="update_category" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize lucide icons
        lucide.createIcons();

        const editModal = document.getElementById('edit-modal');
        const editCategoryId = document.getElementById('edit-category-id');
        const editCategoryName = document.getElementById('edit-category-name');

        /**
         * Opens the modal and pre-fills the form fields with category data.
         * @param {number} id - The ID of the category.
         * @param {string} name - The current name of the category.
         */
        function openEditModal(id, name) {
            editCategoryId.value = id;
            editCategoryName.value = name;
            editModal.style.display = 'flex';
            editCategoryName.focus();
        }

        /**
         * Closes the modal.
         */
        function closeEditModal() {
            editModal.style.display = 'none';
        }

        // Close modal when clicking outside the content area
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) {
                closeEditModal();
            }
        });

        /**
         * Prompts the user for confirmation before deleting a category.
         * @param {number} id - The ID of the category to be deleted.
         * @returns {boolean} - True if the user confirms, false otherwise.
         */
        function confirmDelete(id) {
            // Note: Since 'alert' is blocked, we rely on the browser's default 'confirm' dialog
            // for simple form-based actions. If this were a complex app, a custom modal would be used.
            const message = `Are you sure you want to delete Category ID ${id}? This action cannot be undone.`;
            return window.confirm(message);
        }
    </script>
</body>
</html>
