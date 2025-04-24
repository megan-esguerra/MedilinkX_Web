<?php
session_start();
require_once __DIR__ . '/components/db_connect.php';
require_once __DIR__ . '/components/functions.php';

// Get all products with category and supplier info
function getAllProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, c.category_name, s.supplier_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.category_id 
                         LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
                         ORDER BY p.stock ASC, p.product_id DESC"); // Order by stock first
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get low stock and out of stock products
function getLowStockProducts() {
    global $pdo;
    return $pdo->query("SELECT p.*, c.category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        WHERE p.stock <= p.reorder_level 
                        ORDER BY p.stock ASC")->fetchAll();
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

    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, category_id, supplier_id, 
                              price, stock, reorder_level, expiration_date, unit) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $category_id, $supplier_id, 
                       $price, $stock, $reorder_level, $expiration_date, $unit]);
        $_SESSION['success'] = "Product added successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding product: " . $e->getMessage();
    }
    header("Location: products.php");
    exit();
}

// Delete product
if (isset($_GET['delete'])) {
    try {
        $pdo->beginTransaction();
        
        $id = $_GET['delete'];
        
        // Archive the product first
        $stmt = $pdo->prepare("INSERT INTO archive (product_id, name, reason) 
                              SELECT product_id, name, 'Product deleted' 
                              FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        
        // Delete related records
        $pdo->prepare("DELETE FROM order_items WHERE product_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM prescriptions WHERE product_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$id]);
        
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
$low_stock_products = getLowStockProducts();

// Count out of stock and low stock products
$out_of_stock_count = 0;
$low_stock_count = 0;
foreach ($products as $product) {
    if ($product['stock'] == 0) {
        $out_of_stock_count++;
    } elseif ($product['stock'] <= $product['reorder_level']) {
        $low_stock_count++;
    }
}
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
    <style>
        .stock-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
        }
        
        .stock-badge {
            font-size: 0.9rem;
            padding: 0.5em 0.8em;
        }
        
        .table tr.out-of-stock {
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .table tr.low-stock {
            background-color: rgba(255, 193, 7, 0.1);
        }
        
        .product-stats {
            background: var(--light-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .blink {
            animation: blink 1s linear infinite;
        }
        
        @keyframes blink {
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>

        <div class="container-fluid">
            <!-- Stock Alerts -->
            <?php if ($out_of_stock_count > 0 || $low_stock_count > 0): ?>
                <?php if ($out_of_stock_count > 0): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Warning!</strong> <?= $out_of_stock_count ?> products are out of stock!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($low_stock_count > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Notice:</strong> <?= $low_stock_count ?> products are running low on stock.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Stock Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="product-stats">
                        <h6 class="text-muted">Total Products</h6>
                        <div class="stat-number"><?= count($products) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="product-stats">
                        <h6 class="text-muted">Out of Stock</h6>
                        <div class="stat-number text-danger <?= $out_of_stock_count > 0 ? 'blink' : '' ?>">
                            <?= $out_of_stock_count ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="product-stats">
                        <h6 class="text-muted">Low Stock</h6>
                        <div class="stat-number text-warning">
                            <?= $low_stock_count ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="product-stats">
                        <h6 class="text-muted">In Stock</h6>
                        <div class="stat-number text-success">
                            <?= count($products) - ($out_of_stock_count + $low_stock_count) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rest of your existing code... -->
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Products List</h5>
                
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
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
                                <?php foreach ($products as $product): 
                                    $row_class = $product['stock'] == 0 ? 'out-of-stock' : 
                                              ($product['stock'] <= $product['reorder_level'] ? 'low-stock' : '');
                                ?>
                                <tr class="<?= $row_class ?>">
                                    <td><?= $product['product_id'] ?></td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                                    <td><?= htmlspecialchars($product['supplier_name']) ?></td>
                                    <td>$<?= number_format($product['price'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $product['stock'] == 0 ? 'danger' : 
                                                              ($product['stock'] <= $product['reorder_level'] ? 'warning' : 'success') ?>">
                                            <?= $product['stock'] == 0 ? 'Out of Stock' : $product['stock'] ?>
                                        </span>
                                    </td>
                                    <td><?= $product['expiration_date'] ?></td>
                                    <td>
                                        <a href="edit_product.php?id=<?= $product['product_id'] ?>" 
                                           class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?= $product['product_id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this product?')">
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
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);

        // Play alert sound for out of stock items
        <?php if ($out_of_stock_count > 0): ?>
        new Audio('assets/sounds/alert.mp3').play().catch(e => console.log('Audio play failed:', e));
        <?php endif; ?>
    </script>
</body>
</html>