<?php
require_once __DIR__ . '/components/db_connect.php';
require_once __DIR__ . '/components/functions.php';

// Get all products with category and supplier info
function getAllProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, c.category_name, s.supplier_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.category_id 
                         LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
                         ORDER BY p.product_id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add product
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $supplier_id = $_POST['supplier_id'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $reorder_level = $_POST['reorder_level'];
    $expiration_date = $_POST['expiration_date'];
    $unit = $_POST['unit'];

    $stmt = $pdo->prepare("INSERT INTO products (name, description, category_id, supplier_id, 
                          price, stock, reorder_level, expiration_date, unit) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $category_id, $supplier_id, 
                   $price, $stock, $reorder_level, $expiration_date, $unit]);
    header("Location: products.php");
    exit();
}

// Delete product
if (isset($_GET['delete'])) {
    try {
        $pdo->beginTransaction();
        
        $id = $_GET['delete'];
        
        // First, delete any related records from archive
        $stmt = $pdo->prepare("DELETE FROM archive WHERE product_id = ?");
        $stmt->execute([$id]);
        
        // Then delete any related records from order_items
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
        $stmt->execute([$id]);
        
        // Delete any related records from prescriptions
        $stmt = $pdo->prepare("DELETE FROM prescriptions WHERE product_id = ?");
        $stmt->execute([$id]);
        
        // Finally, delete the product
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        
        $_SESSION['success'] = "Product deleted successfully";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
    }
    
    header("Location: products.php");
    exit();
}

$products = getAllProducts();

// Get success or error messages
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';

// Clear messages after displaying
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - MediLinkX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php require_once __DIR__ . '/components/styles.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>

        <div class="container-fluid">
            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Add Product Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Product</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <?php
                                $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
                                foreach ($categories as $category) {
                                    echo "<option value='{$category['category_id']}'>{$category['category_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select" required>
                                <?php
                                $suppliers = $pdo->query("SELECT * FROM suppliers")->fetchAll();
                                foreach ($suppliers as $supplier) {
                                    echo "<option value='{$supplier['supplier_id']}'>{$supplier['supplier_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" step="0.01" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" name="expiration_date" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="add_product" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Add Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Products List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Supplier</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Expiration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= $product['product_id'] ?></td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                                    <td><?= htmlspecialchars($product['supplier_name']) ?></td>
                                    <td>$<?= number_format($product['price'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $product['stock'] <= $product['reorder_level'] ? 'danger' : 'success' ?>">
                                            <?= $product['stock'] ?>
                                        </span>
                                    </td>
                                    <td><?= $product['expiration_date'] ?></td>
                                    <td>
                                        <a href="edit_product.php?id=<?= $product['product_id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?= $product['product_id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this product? This will also delete all related records.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>