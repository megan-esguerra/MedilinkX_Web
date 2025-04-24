<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'Config/db.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Simple validation
    if (empty($full_name) || empty($email)) {
        $error_message = "Full name and email are required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        // Check if email already exists (exclude current user)
        $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_stmt->execute([$email, $user_id]);
        if ($check_stmt->rowCount() > 0) {
            $error_message = "Email already in use by another account";
        } else {
            // Update profile
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
            $update_stmt->execute([$full_name, $email, $phone, $address, $user_id]);
            $success_message = "Profile updated successfully";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match";
    } elseif (!password_verify($current_password, $user['password'])) {
        $error_message = "Current password is incorrect";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update_stmt->execute([$hashed_password, $user_id]);
        $success_message = "Password changed successfully";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - MediLinkX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php require_once __DIR__ . '/components/styles.php'; ?>

    <style>
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
            font-weight: 600;
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
        }
        
        .form-control, .form-select {
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(62, 115, 218, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2c5bb9;
            border-color: #2c5bb9;
        }
        
        .btn-outline-secondary {
            color: var(--light-text);
            border-color: var(--border-color);
        }
        
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            color: var(--text-color);
        }
        
        .avatar-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .upload-btn {
            font-size: 13px;
        }
        
        .profile-details {
            display: flex;
            flex-wrap: wrap;
        }
        
        .profile-details > div {
            flex: 0 0 50%;
            margin-bottom: 15px;
        }
        
        .profile-details .label {
            font-weight: 600;
            font-size: 13px;
            color: var(--light-text);
        }
        
        .profile-details .value {
            font-size: 15px;
        }

        .alert {
            border-radius: 5px;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: rgba(0, 195, 138, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(0, 195, 138, 0.2);
        }
        
        .alert-danger {
            background-color: rgba(255, 87, 87, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(255, 87, 87, 0.2);
        }
        
        
    </style>
</head>
<body>
<?php require_once __DIR__ . '/components/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="main-content">
    <?php require_once __DIR__ . '/components/header.php'; ?>
        <!-- Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- User Info Card -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>User Information</div>
                    </div>
                    <div class="card-body">
                        <div class="avatar-container">
                            <div class="avatar">
                                <?php echo strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)); ?>
                            </div>
                            <h5><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h5>
                            <span class="badge bg-primary"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="profile-details">
                            <div>
                                <div class="label">Username</div>
                                <div class="value"><?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                            <div>
                                <div class="label">Email</div>
                                <div class="value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div>
                                <div class="label">Phone</div>
                                <div class="value"><?php echo htmlspecialchars($user['phone'] ?? 'Not specified'); ?></div>
                            </div>
                            <div>
                                <div class="label">Member Since</div>
                                <div class="value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Edit Card -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">Edit Profile</div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <div class="form-text">Username cannot be changed</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Password Change Card -->
                <div class="card">
                    <div class="card-header">Change Password</div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>