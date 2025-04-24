<?php
session_start();
require_once __DIR__ . '/components/db_connect.php';
require_once __DIR__ . '/components/functions.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = $_GET['id'];

// Get product details
function getProduct($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, c.category_id, s.supplier_id 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.category_id 
                          LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
                          WHERE p.product_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update product
if (isset($_POST['update_product'])) {
    try {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $supplier_id = $_POST['supplier_id'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $reorder_level = $_POST['reorder_level'];
        $expiration_date = $_POST['expiration_date'];
        $unit = $_POST['unit'];

        $stmt = $pdo->prepare("UPDATE products SET 
                              name = ?, 
                              description = ?, 
                              category_id = ?, 
                              supplier_id = ?, 
                              price = ?, 
                              stock = ?, 
                              reorder_level = ?, 
                              expiration_date = ?, 
                              unit = ? 
                              WHERE product_id = ?");
                              
        $stmt->execute([
            $name, 
            $description, 
            $category_id, 
            $supplier_id, 
            $price, 
            $stock, 
            $reorder_level, 
            $expiration_date, 
            $unit, 
            $product_id
        ]);

        $_SESSION['success'] = "Product updated successfully";
        header("Location: products.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating product: " . $e->getMessage();
    }
}

$product = getProduct($product_id);

// If product not found
if (!$product) {
    $_SESSION['error'] = "Product not found";
    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - MediLinkX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php require_once __DIR__ . '/components/styles.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>

        <div class="container-fluid">
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); endif; ?>

            <!-- Edit Product Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Edit Product
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <?php
                                $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
                                foreach ($categories as $category) {
                                    $selected = ($category['category_id'] == $product['category_id']) ? 'selected' : '';
                                    echo "<option value='{$category['category_id']}' {$selected}>{$category['category_name']}</option>";
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
                                    $selected = ($supplier['supplier_id'] == $product['supplier_id']) ? 'selected' : '';
                                    echo "<option value='{$supplier['supplier_id']}' {$selected}>{$supplier['supplier_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" step="0.01" class="form-control" 
                                   value="<?= $product['price'] ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" class="form-control" 
                                   value="<?= $product['stock'] ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" 
                                   value="<?= $product['reorder_level'] ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" 
                                   value="<?= htmlspecialchars($product['unit']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" name="expiration_date" class="form-control" 
                                   value="<?= $product['expiration_date'] ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" 
                                      rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="update_product" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Product
                            </button>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>