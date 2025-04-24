<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Connect to the database
require_once 'Config/db.php';

// Get current user ID and role
$user_id = $_SESSION['user_id'];

// Process user actions
$success_message = '';
$error_message = '';

// Handle user deletion
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    try {
        $delete_id = $_POST['user_id'];
        // Prevent self-deletion
        if ($delete_id == $user_id) {
            $error_message = "You cannot delete your own account!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$delete_id]);
            $success_message = "User deleted successfully";
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Handle role update
if (isset($_POST['update_role']) && isset($_POST['user_id']) && isset($_POST['role'])) {
    try {
        $update_id = $_POST['user_id'];
        $new_role = $_POST['role'];
        
        // Prevent changing own role
        if ($update_id == $user_id) {
            $error_message = "You cannot change your own role!";
        } else {
            // Validate role input
            $allowed_roles = ['admin', 'staff', 'user'];
            if (!in_array($new_role, $allowed_roles)) {
                $error_message = "Invalid role specified";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
                $stmt->execute([$new_role, $update_id]);
                $success_message = "User role updated successfully";
            }
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Get search query if any
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get users with search filter
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ? ORDER BY created_at DESC");
        $searchTerm = "%$search%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
        $stmt->execute();
    }
    $users = $stmt->fetchAll();
    
    // Count users by role
    $admin_count = 0;
    $staff_count = 0;
    $user_count = 0;
    
    foreach ($users as $u) {
        if ($u['role'] === 'admin') {
            $admin_count++;
        } elseif ($u['role'] === 'staff') {
            $staff_count++;
        } else {
            $user_count++;
        }
    }
    
    $total_users = count($users);
    $active_users = $total_users; // In a real app, you might track active vs inactive users
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $users = [];
    $total_users = 0;
    $admin_count = 0;
    $staff_count = 0;
    $user_count = 0;
    $active_users = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - MediLinkX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2979ff;
            --primary-light: #e3f2fd;
            --primary-dark: #1c54b2;
            --secondary-color: #f5f7fb;
            --text-color: #333;
            --text-light: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --border-color: #e9ecef;
            --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
        }
        
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 220px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            padding-top: 1rem;
        }
        
        .logo {
            display: flex;
            align-items: center;
            padding: 1rem;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }
        
        .logo i {
            margin-right: 0.5rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
        }
        
        .nav-item i {
            width: 1.5rem;
            margin-right: 0.5rem;
            text-align: center;
        }
        
        .section-title {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            color: var(--text-light);
            margin-top: 1rem;
        }
        
        /* Main content area */
        .main-content {
            margin-left: 220px;
            padding: 1.5rem;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
            color: var(--primary-color);
        }
        
        .search-bar {
            display: flex;
            align-items: center;
        }
        
        .search-input {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            width: 300px;
            font-size: 0.875rem;
        }
        
        .search-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            margin-left: 0.5rem;
            cursor: pointer;
        }
        
        /* Stats cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stat-info h3 {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }
        
        .stat-info .number {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.25rem;
        }
        
        .user-icon {
            background-color: #e3f2fd;
            color: #2979ff;
        }
        
        .admin-icon {
            background-color: #fff3cd;
            color: #ffc107;
        }
        
        .staff-icon {
            background-color: #d4edda;
            color: #28a745;
        }
        
        .active-icon {
            background-color: #f8d7da;
            color: #dc3545;
        }
        
        /* User management card */
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .add-user-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 0.875rem;
        }
        
        .add-user-btn i {
            margin-right: 0.5rem;
        }
        
        /* Users table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background-color: var(--secondary-color);
            text-align: left;
            padding: 0.75rem;
            font-size: 0.875rem;
            color: var(--text-light);
            border-bottom: 2px solid var(--border-color);
        }
        
        td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
            vertical-align: middle;
        }
        
        tr:hover {
            background-color: var(--primary-light);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.75rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .username {
            font-weight: 500;
        }
        
        .email {
            color: var(--text-light);
            font-size: 0.825rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-admin {
            background-color: var(--warning-color);
            color: #856404;
        }
        
        .badge-staff {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-user {
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }
        
        .date-joined {
            color: var(--text-light);
            font-size: 0.825rem;
        }
        
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            border: none;
            padding: 0.4rem 0.75rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 0.25rem;
        }
        
        .btn-edit {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .btn-delete {
            background-color: #f8d7da;
            color: var(--danger-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
            gap: 0.25rem;
        }
        
        .page-link {
            border: 1px solid var(--border-color);
            padding: 0.4rem 0.75rem;
            border-radius: 4px;
            color: var(--text-color);
            text-decoration: none;
        }
        
        .page-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* Alert messages */
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Role dropdown */
        .role-select {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            font-size: 0.75rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar .logo span,
            .sidebar .nav-item span,
            .section-title {
                display: none;
            }
            
            .sidebar .nav-item {
                padding: 0.75rem;
                justify-content: center;
            }
            
            .sidebar .nav-item i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .search-input {
                width: 200px;
            }
            
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
            
            .actions-col {
                min-width: 120px;
            }
        }
    </style>
</head>
<body>
       <!-- Sidebar -->
       <div class="sidebar">
        <div class="logo">
            <i class="fas fa-heartbeat"></i>
            <span>MediLinkX</span>
        </div>
        
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="products.php" class="nav-item">
            <i class="fas fa-boxes"></i>
            <span>Products</span>
        </a>
        
        <a href="categories.php" class="nav-item">
            <i class="fas fa-tags"></i>
            <span>Categories</span>
        </a>
        
        <a href="inventory.php" class="nav-item">
            <i class="fas fa-warehouse"></i>
            <span>Inventory</span>
        </a>
        
        <a href="sales-report.php" class="nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Sales Report</span>
        </a>
        
        <div class="section-title">ADMINISTRATION</div>
        
        <a href="user-management.php" class="nav-item active">
            <i class="fas fa-users-cog"></i>
            <span>User Management</span>
        </a>
        
        <a href="settings.php" class="nav-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        
        <div class="section-title">ACCOUNT PAGES</div>
        
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user-circle"></i>
            <span>Profile</span>
        </a>
        
        <a href="logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sign Out</span>
        </a>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1 class="page-title">
                <i class="fas fa-users-cog"></i> User Management
            </h1>
            <form action="" method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search users..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>
        
        <!-- Alerts -->
        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Users</h3>
                    <div class="number"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-icon user-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Administrators</h3>
                    <div class="number"><?php echo $admin_count; ?></div>
                </div>
                <div class="stat-icon admin-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Staff Members</h3>
                    <div class="number"><?php echo $staff_count; ?></div>
                </div>
                <div class="stat-icon staff-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Regular Users</h3>
                    <div class="number"><?php echo $user_count; ?></div>
                </div>
                <div class="stat-icon active-icon">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
        
        <!-- Users Table Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">User List</div>
                <a href="add-user.php" class="add-user-btn">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Joined Date</th>
                                <th class="actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 20px;">No users found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="username"><?php echo htmlspecialchars($u['full_name'] ?? $u['username']); ?></div>
                                                    <div class="email"><?php echo htmlspecialchars($u['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($u['user_id'] == $user_id): ?>
                                                <span class="status-badge badge-<?php echo $u['role']; ?>">
                                                    <?php echo ucfirst($u['role']); ?>
                                                </span>
                                            <?php else: ?>
                                                <form action="" method="POST">
                                                    <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                    <select name="role" class="role-select" onchange="this.form.submit()">
                                                        <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                        <option value="staff" <?php echo $u['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                                        <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                    </select>
                                                    <input type="hidden" name="update_role" value="1">
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['phone'] ?? 'Not provided'); ?></td>
                                        <td>
                                            <div class="date-joined">
                                                <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="edit-user.php?id=<?php echo $u['user_id']; ?>" class="btn btn-edit">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                
                                                <?php if ($u['user_id'] != $user_id): ?>
                                                    <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                        <button type="submit" name="delete_user" class="btn btn-delete">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="pagination">
                    <a href="#" class="page-link">Previous</a>
                    <a href="#" class="page-link active">1</a>
                    <a href="#" class="page-link">2</a>
                    <a href="#" class="page-link">3</a>
                    <a href="#" class="page-link">Next</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 1s ease';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 1000);
                }, 5000);
            });
        });
    </script>
</body>
</html>