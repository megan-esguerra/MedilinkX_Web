<?php
// Initialize session
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Process login form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'Config/db.php';
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $error = '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Start session and store user info
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediLinkX - Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 400px;
            padding: 40px;
            text-align: center;
        }
        
        .logo {
            color: #2979ff;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 30px;
        }
        
        .login-form h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            color: #555;
            margin-bottom: 6px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #2979ff;
        }
        
        .submit-btn {
            background: linear-gradient(to right, #4e8cff, #2979ff);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background: linear-gradient(to right, #2979ff, #1c54b2);
        }
        
        .forgot-password {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .forgot-password a {
            color: #2979ff;
            text-decoration: none;
        }
        
        .error-message {
            color: #f44336;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-heartbeat"></i> MediLinkX
        </div>
        <div class="login-form">
            <h1>Admin Login</h1>
            <?php if(isset($error) && !empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="submit-btn">Sign In</button>
            </form>
            <div class="forgot-password">
                <a href="forgot-password.php">Forgot your password?</a>
            </div>
        </div>
    </div>
</body>
</html>