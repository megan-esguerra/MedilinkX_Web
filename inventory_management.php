<?php
session_start();
require_once __DIR__ . '/components/db_connect.php';
require_once __DIR__ . '/components/functions.php';

// Function to get low stock products
function getLowStockProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, c.category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.category_id 
                         WHERE p.stock <= p.reorder_level
                         ORDER BY p.stock ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get expiring products
function getExpiringProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, c.category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.category_id 
                         WHERE p.expiration_date <= DATE_ADD(CURRENT_DATE, INTERVAL 3 MONTH) 
                         AND p.expiration_date > CURRENT_DATE
                         ORDER BY p.expiration_date ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get stock movements (updated to include product_id)
function getStockMovements() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT p.product_id,
                                   p.name as product_name, 
                                   p.stock as current_stock,
                                   p.reorder_level,
                                   CASE 
                                       WHEN p.stock <= p.reorder_level THEN 'Low Stock'
                                       ELSE 'In Stock'
                                   END as stock_status
                            FROM products p
                            ORDER BY p.stock ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Handle stock update
if (isset($_POST['update_stock'])) {
    try {
        $pdo->beginTransaction();

        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        $notes = $_POST['notes'];
        $transaction_type = $_POST['transaction_type']; // 'in' or 'out'
        $reference = 'REF-' . date('YmdHis');

        // Verify product exists
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $current_stock = $stmt->fetchColumn();

        if ($transaction_type == 'out' && $quantity > $current_stock) {
            throw new Exception("Cannot remove more items than current stock");
        }

        // Update product stock
        $new_stock = $transaction_type == 'in' ? 
                    $current_stock + $quantity : 
                    $current_stock - $quantity;

        $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE product_id = ?");
        $stmt->execute([$new_stock, $product_id]);

        // Record stock movement
        $stmt = $pdo->prepare("INSERT INTO stock_movements 
                              (product_id, quantity, movement_type, reference, notes, created_at) 
                              VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$product_id, $quantity, $transaction_type, $reference, $notes]);

        $pdo->commit();
        $_SESSION['success'] = "Stock updated successfully";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: inventory_management.php");
    exit();
}

$lowStockProducts = getLowStockProducts();
$expiringProducts = getExpiringProducts();
$stockMovements = getStockMovements();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - MediLinkX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php require_once __DIR__ . '/components/styles.php'; ?>
    <style>
        .stock-card {
            transition: transform 0.2s;
        }
        .stock-card:hover {
            transform: translateY(-5px);
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-low {
            background-color: #dc3545;
        }
        .status-ok {
            background-color: #198754;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>

        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stock-card">
                        <div class="card-body">
                            <h6 class="text-muted">Total Products</h6>
                            <h3><?= count($stockMovements) ?></h3>
                            <small class="text-muted">In inventory</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stock-card">
                        <div class="card-body">
                            <h6 class="text-muted">Low Stock Items</h6>
                            <h3><?= count($lowStockProducts) ?></h3>
                            <small class="text-danger">Need attention</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stock-card">
                        <div class="card-body">
                            <h6 class="text-muted">Expiring Soon</h6>
                            <h3><?= count($expiringProducts) ?></h3>
                            <small class="text-warning">Within 3 months</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stock-card">
                        <div class="card-body">
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#stockModal">
                                <i class="fas fa-plus-circle me-2"></i>Manage Stock
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Management Section -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Low Stock Alerts
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Stock</th>
                                            <th>Reorder Level</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lowStockProducts as $product): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                                            <td>
                                                <span class="badge bg-danger"><?= $product['stock'] ?></span>
                                            </td>
                                            <td><?= $product['reorder_level'] ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="prepareRestock(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['name']) ?>')">
                                                    <i class="fas fa-plus-circle"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock text-danger me-2"></i>
                                Expiring Products
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Expiry Date</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expiringProducts as $product): ?>
                                        <?php 
                                            $days_until_expiry = (strtotime($product['expiration_date']) - time()) / (60 * 60 * 24);
                                            $status_class = $days_until_expiry <= 30 ? 'bg-danger' : 
                                                          ($days_until_expiry <= 60 ? 'bg-warning' : 'bg-info');
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                                            <td><?= $product['expiration_date'] ?></td>
                                            <td><?= $product['stock'] ?></td>
                                            <td>
                                                <span class="badge <?= $status_class ?>">
                                                    <?= round($days_until_expiry) ?> days
                                                </span>
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

            <!-- Current Stock Overview -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-boxes me-2"></i>
                        Stock Overview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="stockTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stockMovements as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= $item['current_stock'] ?></td>
                                    <td><?= $item['reorder_level'] ?></td>
                                    <td>
                                        <span class="status-indicator <?= $item['stock_status'] == 'Low Stock' ? 'status-low' : 'status-ok' ?>"></span>
                                        <?= $item['stock_status'] ?>
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

    <!-- Stock Management Modal -->
    <div class="modal fade" id="stockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <!-- Using modal to select product by product_id -->
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <select name="product_id" class="form-select" required>
                                <?php foreach ($stockMovements as $item): ?>
                                <option value="<?= $item['product_id'] ?>">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Type</label>
                            <select name="transaction_type" class="form-select" required>
                                <option value="in">Stock In</option>
                                <option value="out">Stock Out</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_stock" class="btn btn-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap Bundle -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function prepareRestock(productId, productName) {
            const modal = new bootstrap.Modal(document.getElementById('stockModal'));
            document.querySelector('#stockModal select[name="product_id"]').value = productId;
            document.querySelector('#stockModal select[name="transaction_type"]').value = 'in';
            modal.show();
        }
    </script>
</body>
</html>