]<?php
// Initialize session
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$success = false;

// Process registration form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'Config/db.php';
    
    // Get form data in the same order as DB columns
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $role = 'user'; // Default role
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    // created_at handled by MySQL
    
    // Validation in order matching DB fields
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $password_confirm) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if username or email already exists
    if (count($errors) === 0) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                if ($existingUser['username'] === $username) {
                    $errors[] = "Username already taken";
                }
                if ($existingUser['email'] === $email) {
                    $errors[] = "Email already registered";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    // Register user if no errors - align with DB columns order
    if (count($errors) === 0) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, phone, address, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$username, $hashedPassword, $role, $full_name, $email, $phone, $address]);
            
            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediLinkX - Registration</title>
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
            min-height: 100vh;
            padding: 20px 10px;
        }
        
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 30px 20px;
            text-align: center;
            margin: 10px auto;
        }
        
        .logo {
            color: #2979ff;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .register-form h1 {
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 16px;
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
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            background: linear-gradient(to right, #2979ff, #1c54b2);
        }
        
        .login-link {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .login-link a {
            color: #2979ff;
            text-decoration: none;
        }
        
        .error-message {
            color: #f44336;
            font-size: 14px;
            margin-top: 10px;
            list-style-type: none;
            text-align: left;
            padding: 0;
        }
        
        .error-message li {
            margin-bottom: 5px;
        }
        
        .success-message {
            color: #4CAF50;
            font-size: 16px;
            margin: 20px 0;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 5px;
        }
        
        .form-row {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        @media screen and (min-width: 480px) {
            .register-container {
                padding: 40px 30px;
            }
            
            .form-row {
                flex-direction: row;
            }
            
            .form-row .form-group {
                flex: 1;
            }
        }
        
        @media screen and (max-width: 350px) {
            .logo {
                font-size: 22px;
            }
            
            .register-form h1 {
                font-size: 20px;
            }
            
            .form-group input {
                padding: 10px;
            }
            
            .submit-btn {
                padding: 10px;
                font-size: 14px;
            }
        }
        
        /* Add touch-friendly sizing for mobile */
        @media (hover: none) and (pointer: coarse) {
            .form-group input, 
            .submit-btn {
                min-height: 44px; /* Apple's recommended minimum touch target size */
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <i class="fas fa-heartbeat"></i> MediLinkX
        </div>
        <div class="register-form">
            <h1>Create Account</h1>
            
            <?php if($success): ?>
                <div class="success-message">
                    Registration successful! You can now <a href="login.php">login</a> with your credentials.
                </div>
            <?php else: ?>
            
                <?php if(!empty($errors)): ?>
                    <ul class="error-message">
                        <?php foreach($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <!-- Fields rearranged to match database column order -->
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">Confirm Password</label>
                            <input type="password" id="password_confirm" name="password_confirm" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="submit-btn">Register</button>
                </form>
            <?php endif; ?>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Log in</a>
            </div>
        </div>
    </div>
</body>
</html>