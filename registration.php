<?php
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
    
    // Enhanced validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters and can only contain letters, numbers, and underscores";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif (!preg_match('/[A-Z]/', $password) || 
              !preg_match('/[a-z]/', $password) || 
              !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must include at least one uppercase letter, one lowercase letter, and one number";
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
    
    if (!empty($phone) && !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
        $errors[] = "Invalid phone number format";
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
        :root {
            --primary: #2979ff;
            --primary-hover: #1c54b2;
            --primary-light: #e9f0ff;
            --success: #4CAF50;
            --success-light: #e8f5e9;
            --danger: #f44336;
            --danger-light: #ffebee;
            --gray: #757575;
            --light-gray: #f5f7fb;
            --border: #ddd;
            --box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px 10px;
            color: #333;
        }
        
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 520px;
            padding: 30px 20px;
            text-align: center;
            margin: 10px auto;
        }
        
        .logo {
            color: var(--primary);
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .register-form h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 18px;
            text-align: left;
            position: relative;
        }
        
        .form-group label {
            display: block;
            color: var(--gray);
            margin-bottom: 6px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(41, 121, 255, 0.1);
        }
        
        .form-group .input-with-icon {
            position: relative;
        }
        
        .form-group .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray);
        }
        
        .form-group .toggle-password:focus {
            outline: none;
            color: var(--primary);
        }
        
        .submit-btn {
            background: linear-gradient(to right, #4e8cff, #2979ff);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 14px;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(41, 121, 255, 0.2);
        }
        
        .submit-btn:hover {
            background: linear-gradient(to right, #2979ff, #1c54b2);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(41, 121, 255, 0.25);
        }
        
        .submit-btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 8px rgba(41, 121, 255, 0.25);
        }
        
        .login-link {
            margin-top: 20px;
            font-size: 15px;
            color: var(--gray);
        }
        
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: var(--danger-light);
            color: var(--danger);
            font-size: 14px;
            margin: 15px 0;
            list-style-type: none;
            text-align: left;
            padding: 12px 15px;
            border-radius: 6px;
            border-left: 4px solid var(--danger);
        }
        
        .error-message li {
            margin-bottom: 5px;
            display: flex;
            align-items: flex-start;
        }
        
        .error-message li:before {
            content: "\f071";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
        }
        
        .error-message li:last-child {
            margin-bottom: 0;
        }
        
        .success-message {
            color: var(--success);
            font-size: 16px;
            margin: 20px 0;
            padding: 15px;
            background-color: var(--success-light);
            border-radius: 6px;
            border-left: 4px solid var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .success-message:before {
            content: "\f058";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
            font-size: 18px;
        }
        
        .form-row {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        /* Password strength indicator */
        .password-strength-meter {
            height: 4px;
            width: 100%;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 6px;
            position: relative;
            overflow: hidden;
        }
        
        .password-strength-meter span {
            display: block;
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
        
        .strength-weak {
            width: 25% !important;
            background-color: #ff5252;
        }
        
        .strength-medium {
            width: 50% !important;
            background-color: #ffab40;
        }
        
        .strength-good {
            width: 75% !important;
            background-color: #9ccc65;
        }
        
        .strength-strong {
            width: 100% !important;
            background-color: #66bb6a;
        }
        
        .password-feedback {
            font-size: 12px;
            margin-top: 6px;
            color: var(--gray);
            display: flex;
            align-items: center;
        }
        
        .validation-icon {
            margin-right: 4px;
            font-size: 12px;
        }
        
        @media screen and (min-width: 576px) {
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
                font-size: 24px;
            }
            
            .register-form h1 {
                font-size: 20px;
            }
            
            .form-group input {
                padding: 10px;
            }
            
            .submit-btn {
                padding: 12px;
                font-size: 15px;
            }
        }
        
        /* Add touch-friendly sizing for mobile */
        @media (hover: none) and (pointer: coarse) {
            .form-group input, 
            .submit-btn {
                min-height: 48px;
            }
        }
        
        /* Form field transition animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        form .form-group {
            animation: fadeIn 0.4s ease-out;
        }
        
        form .form-group:nth-child(2) { animation-delay: 0.05s; }
        form .form-group:nth-child(3) { animation-delay: 0.1s; }
        form .form-group:nth-child(4) { animation-delay: 0.15s; }
        form .form-group:nth-child(5) { animation-delay: 0.2s; }
        form .form-group:nth-child(6) { animation-delay: 0.25s; }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <i class="fas fa-heartbeat"></i> MediLinkX
        </div>
        <div class="register-form">
            <h1>Create Your Account</h1>
            
            <?php if($success): ?>
                <div class="success-message">
                    Registration successful! You can now login with your credentials.
                </div>
            <?php else: ?>
            
                <?php if(!empty($errors)): ?>
                    <ul class="error-message">
                        <?php foreach($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               placeholder="Enter a unique username" required autocomplete="username">
                        <div class="password-feedback username-feedback"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-with-icon">
                                <input type="password" id="password" name="password" placeholder="At least 8 characters" 
                                       required autocomplete="new-password" onkeyup="checkPasswordStrength()">
                                <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength-meter">
                                <span id="strength-meter"></span>
                            </div>
                            <div class="password-feedback" id="password-feedback">Enter at least 8 characters</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">Confirm Password</label>
                            <div class="input-with-icon">
                                <input type="password" id="password_confirm" name="password_confirm" placeholder="Re-enter password" 
                                       required autocomplete="new-password" onkeyup="checkPasswordMatch()">
                                <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-feedback" id="match-feedback"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                               placeholder="Enter your full name" required autocomplete="name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               placeholder="your@email.com" required autocomplete="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                               placeholder="e.g., +1 (555) 123-4567" autocomplete="tel">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address (Optional)</label>
                        <input type="text" id="address" name="address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" 
                               placeholder="Your address" autocomplete="street-address">
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Log in</a>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
        });
        
        // Check password strength
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const meter = document.getElementById('strength-meter');
            const feedback = document.getElementById('password-feedback');
            
            if (password.length === 0) {
                meter.className = '';
                meter.style.width = '0';
                feedback.innerHTML = '<i class="fas fa-info-circle validation-icon"></i> Enter at least 8 characters';
                return;
            }
            
            // Calculate strength
            let strength = 0;
            
            // Length check
            if (password.length > 7) strength += 1;
            if (password.length > 10) strength += 1;
            
            // Complexity checks
            if (password.match(/[a-z]+/)) strength += 1;
            if (password.match(/[A-Z]+/)) strength += 1;
            if (password.match(/[0-9]+/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]+/)) strength += 1;
            
            // Update the meter
            switch(true) {
                case (strength <= 2):
                    meter.className = 'strength-weak';
                    feedback.innerHTML = '<i class="fas fa-exclamation-circle validation-icon" style="color: #ff5252;"></i> Weak password';
                    break;
                case (strength <= 4):
                    meter.className = 'strength-medium';
                    feedback.innerHTML = '<i class="fas fa-exclamation-triangle validation-icon" style="color: #ffab40;"></i> Medium password';
                    break;
                case (strength <= 5):
                    meter.className = 'strength-good';
                    feedback.innerHTML = '<i class="fas fa-check-circle validation-icon" style="color: #9ccc65;"></i> Good password';
                    break;
                case (strength > 5):
                    meter.className = 'strength-strong';
                    feedback.innerHTML = '<i class="fas fa-shield-alt validation-icon" style="color: #66bb6a;"></i> Strong password';
                    break;
            }
            
            // Check passwords match
            checkPasswordMatch();
        }
        
        // Check if passwords match
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;
            const feedback = document.getElementById('match-feedback');
            
            if (confirm.length === 0) {
                feedback.innerHTML = '';
                return;
            }
            
            if (password === confirm) {
                feedback.innerHTML = '<i class="fas fa-check-circle validation-icon" style="color: #66bb6a;"></i> Passwords match';
            } else {
                feedback.innerHTML = '<i class="fas fa-times-circle validation-icon" style="color: #ff5252;"></i> Passwords do not match';
            }
        }
    </script>
</body>
</html>