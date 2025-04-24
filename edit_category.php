<?php
session_start();
require_once __DIR__ . '/components/db_connect.php';
require_once __DIR__ . '/components/functions.php';

// Get category details
function getCategory($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

// Update category
if (isset($_POST['update_category'])) {
    try {
        $id = $_POST['category_id'];
        $name = $_POST['category_name'];
        $description = $_POST['description'];

        $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
        $stmt->execute([$name, $description, $id]);

        $_SESSION['success'] = "Category updated successfully";
        header("Location: categories.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating category: " . $e->getMessage();
    }
}

// Get category if ID provided
$category = null;
if (isset($_GET['id'])) {
    $category = getCategory($_GET['id']);
    if (!$category) {
        $_SESSION['error'] = "Category not found";
        header("Location: categories.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - MediLinkX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php require_once __DIR__ . '/components/styles.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-edit me-2"></i>Edit Category
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($category): ?>
                                <form method="POST">
                                    <input type="hidden" name="category_id" value="<?= $category['category_id'] ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Category Name</label>
                                        <input type="text" name="category_name" class="form-control" 
                                               value="<?= htmlspecialchars($category['category_name']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" 
                                                  rows="3"><?= htmlspecialchars($category['description']) ?></textarea>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" name="update_category" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Category
                                        </button>
                                        <a href="categories.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="text-center">
                                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                                    <h4>Category Not Found</h4>
                                    <p class="text-muted">The category you're trying to edit doesn't exist.</p>
                                    <a href="categories.php" class="btn btn-primary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Categories
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>