<?php
session_start();
require_once __DIR__ . '/components/db_connect.php';
require_once __DIR__ . '/components/functions.php';

// Get all categories with product count and stock info
function getAllCategories() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT c.*, 
                         COUNT(p.product_id) as product_count,
                         COALESCE(SUM(p.stock), 0) as total_stock
                         FROM categories c
                         LEFT JOIN products p ON c.category_id = p.category_id
                         GROUP BY c.category_id, c.category_name, c.description, c.created_at
                         ORDER BY c.category_id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching categories: " . $e->getMessage();
        return [];
    }
}

// Add category
if (isset($_POST['add_category'])) {
    try {
        $name = trim($_POST['category_name']);
        $description = trim($_POST['description']);

        // Validate input
        if (empty($name)) {
            throw new Exception("Category name is required");
        }

        // Check if category already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("A category with this name already exists");
        }

        $stmt = $pdo->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        
        $_SESSION['success'] = "Category added successfully!";
        header("Location: categories.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Delete category
if (isset($_GET['delete'])) {
    try {
        $id = $_GET['delete'];
        
        // Check if category has products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete category that contains products");
        }
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Category deleted successfully!";
        header("Location: categories.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
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
    <style>
        .category-card {
            transition: transform 0.2s;
        }
        .category-card:hover {
            transform: translateY(-5px);
        }
        .category-stats {
            background: var(--secondary-color);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>

        <div class="container-fluid">
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Category Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card category-stats">
                        <div class="card-body">
                            <h5 class="card-title">Total Categories</h5>
                            <h2><?= count($categories) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card category-stats">
                        <div class="card-body">
                            <h5 class="card-title">Total Products</h5>
                            <h2><?= array_sum(array_column($categories, 'product_count')) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card category-stats">
                        <div class="card-body">
                            <h5 class="card-title">Total Stock</h5>
                            <h2><?= array_sum(array_column($categories, 'total_stock')) ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Category Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-folder-plus me-2"></i>Add New Category
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
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
                    </form>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-folder-open me-2"></i>Categories List
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>No categories found. Add your first category above.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                        <td><?= htmlspecialchars($category['description'] ?: 'No description') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $category['product_count'] > 0 ? 'primary' : 'secondary' ?>">
                                                <?= $category['product_count'] ?>
                                            </span>
                                        </td>
                                        <td><?= $category['total_stock'] ?></td>
                                        <td><?= date('Y-m-d', strtotime($category['created_at'])) ?></td>
                                        <td>
                                            <a href="edit_category.php?id=<?= $category['category_id'] ?>" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?= $category['category_id'] ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this category? This cannot be undone.')"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
    </script>
</body>
</html>