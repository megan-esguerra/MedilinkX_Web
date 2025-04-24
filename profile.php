<?php
session_start();
require_once 'Config/db.php';
require_once 'components/check_session.php';

// Check session
checkSession();

$success_message = '';
$error_message = '';

// Fetch current user data with error handling
try {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: login.php?error=invalid_user");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Error loading user data. Please try again.";
    $user = [
        'username' => $_SESSION['username'] ?? 'Unknown',
        'email' => '',
        'phone' => '',
        'address' => '',
        'full_name' => '',
        'role' => 'user',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        try {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            
            if (empty($full_name) || empty($email)) {
                throw new Exception("Full name and email are required fields");
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            // Check if email already exists (exclude current user)
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email already in use by another account");
            }
            
            // Update profile
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $email, $phone, $address, $user_id]);
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $success_message = "Profile updated successfully";
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        try {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (strlen($new_password) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }
            
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            $success_message = "Password changed successfully";
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - MediLinkX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php require_once __DIR__ . '/components/styles.php'; ?>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .avatar-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #6c97c5;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 10px;
        }
        .profile-details {
            display: flex;
            flex-direction: column;
        }
        .profile-details .label {
            font-weight: bold;
        }
        .profile-details .value {
            margin-bottom: 10px;
        }
        .account-section-title {
            font-size: 14px;
            color: #6c757d;
            margin: 20px 0 10px;
            text-transform: uppercase;
        }
        .card-header {
    background-color: #4a89dc;
    color: white;
}
        .card-header i {
            margin-right: 5px;
        }
        .btn-primary {
            background-color: #4a89dc;
            border-color: #4a89dc;
        }
        .btn-primary:hover {
            background-color: #4a89dc;
            border-color: #4a89dc;
        }
        .form-label {
            font-weight: bold;
        }
        .form-text {
            font-size: 12px;
            color: #6c757d;
        }
        .text-end {
            margin-top: 20px;
        }
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            width: 300px;
        }
        .alert i {
            margin-right: 5px;
        }
        .alert-dismissible .btn-close {
            padding: 0.5rem 1rem;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-dismissible .btn-close {
            position: absolute;
            top: 0.5rem;
            right: 1rem;
            z-index: 1051;
        }
        .alert-dismissible .btn-close:hover {
            color: #000;
        }
        .alert-dismissible .btn-close:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.5);
        }
        .alert-dismissible .btn-close:not(:disabled):not(.disabled) {
            cursor: pointer;
        }
        .alert-dismissible .btn-close:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.5);
        }
        .alert-dismissible .btn-close:focus:not(:disabled):not(.disabled) {
            cursor: pointer;
        }
        .alert-dismissible .btn-close:focus:not(:disabled):not(.disabled) {
            cursor: pointer;
        }

        
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>
        
        <!-- Alert Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- User Info Card -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header text-center">User Information</div>
                    <div class="card-body d-flex flex-column align-items-center">
                        <div class="avatar-container text-center">
                            <div class="avatar mx-auto mb-3">
                                <?= strtoupper(substr($user['full_name'] ?? $user['username'] ?? 'U', 0, 1)) ?>
                            </div>
                            <h5 class="mb-2"><?= htmlspecialchars($user['full_name'] ?? $user['username'] ?? 'User') ?></h5>
                            <span class="badge bg-primary mb-3">
                                <?= ucfirst(htmlspecialchars($user['role'] ?? 'user')) ?>
                            </span>
                        </div>
                        
                        <hr class="w-100 my-3">
                        
                        <div class="profile-details w-100">
                            <div class="row mb-3">
                                <div class="col-5 label">Username:</div>
                                <div class="col-7 text-end"><?= htmlspecialchars($user['username'] ?? 'Not set') ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 label">Email:</div>
                                <div class="col-7 text-end"><?= htmlspecialchars($user['email'] ?? 'Not set') ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 label">Phone:</div>
                                <div class="col-7 text-end"><?= htmlspecialchars($user['phone'] ?? 'Not specified') ?></div>
                            </div>
                            <div class="row">
                                <div class="col-5 label">Member Since:</div>
                                <div class="col-7 text-end">
                                    <?= $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : 'Unknown' ?>
                                </div>
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
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" 
                                           value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" 
                                           value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
                                    <div class="form-text">Username cannot be changed</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Password Change Card -->
                <div class="card">
                    <div class="card-header">Change Password</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>