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
        /* Your existing styles */
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
                <div class="card">
                    <div class="card-header">User Information</div>
                    <div class="card-body">
                        <div class="avatar-container">
                            <div class="avatar">
                                <?= strtoupper(substr($user['full_name'] ?? $user['username'] ?? 'U', 0, 1)) ?>
                            </div>
                            <h5><?= htmlspecialchars($user['full_name'] ?? $user['username'] ?? 'User') ?></h5>
                            <span class="badge bg-primary">
                                <?= ucfirst(htmlspecialchars($user['role'] ?? 'user')) ?>
                            </span>
                        </div>
                        
                        <hr>
                        
                        <div class="profile-details">
                            <div>
                                <div class="label">Username</div>
                                <div class="value"><?= htmlspecialchars($user['username'] ?? 'Not set') ?></div>
                            </div>
                            <div>
                                <div class="label">Email</div>
                                <div class="value"><?= htmlspecialchars($user['email'] ?? 'Not set') ?></div>
                            </div>
                            <div>
                                <div class="label">Phone</div>
                                <div class="value"><?= htmlspecialchars($user['phone'] ?? 'Not specified') ?></div>
                            </div>
                            <div>
                                <div class="label">Member Since</div>
                                <div class="value">
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