<?php
require_once __DIR__ . '/components/db_connect.php';
require_once __DIR__ . '/components/functions.php';

// Get all categories
function getAllCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT c.*, 
                         COUNT(p.product_id) as product_count,
                         SUM(p.stock) as total_stock
                         FROM categories c
                         LEFT JOIN products p ON c.category_id = p.category_id
                         GROUP BY c.category_id
                         ORDER BY c.category_id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add category
if (isset($_POST['add_category'])) {
    $name = $_POST['category_name'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    header("Location: categories.php");
    exit();
}

// Delete category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if category has products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$id]);
        header("Location: categories.php");
    } else {
        $error = "Cannot delete category with existing products";
    }
    exit();
}

$categories = getAllCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - MediLinkX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php require_once __DIR__ . '/components/styles.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>

        <div class="container-fluid">
            <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
            <?php endif; ?>

            <!-- Add Category Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-folder-plus me-2"></i>Add New Category</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="category_name" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_category" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Add Category
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i>Categories List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Total Stock</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?= $category['category_id'] ?></td>
                                    <td><?= htmlspecialchars($category['category_name']) ?></td>
                                    <td><?= htmlspecialchars($category['description']) ?></td>
                                    <td><?= $category['product_count'] ?></td>
                                    <td><?= $category['total_stock'] ?? 0 ?></td>
                                    <td><?= date('Y-m-d', strtotime($category['created_at'])) ?></td>
                                    <td>
                                        <a href="edit_category.php?id=<?= $category['category_id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?= $category['category_id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this category?')">
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