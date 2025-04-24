<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$servername = "localhost"; 
$username = "root";
$password = "";
$dbname = "medilinkx";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the input data
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Check if username/email and password are provided
    if (!empty($username) && !empty($password)) {
        // Query to check credentials (allowing login with either username or email)
        $sql = "SELECT user_id, username, password, role, full_name, email FROM users 
                WHERE username = ? OR email = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // For simplicity, checking plain text password
            // In production, use password_verify() with hashed passwords
            if ($password == $user['password']) {
                // Password is correct
                echo json_encode([
                    "status" => "success",
                    "message" => "Login successful",
                    "user" => [
                        "user_id" => $user['user_id'],
                        "username" => $user['username'],
                        "role" => $user['role'],
                        "full_name" => $user['full_name'],
                        "email" => $user['email']
                    ]
                ]);
            } else {
                // Password is incorrect
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid password"
                ]);
            }
        } else {
            // User not found
            echo json_encode([
                "status" => "error",
                "message" => "User not found"
            ]);
        }

        $stmt->close();
    } else {
        // Invalid request
        echo json_encode([
            "status" => "error",
            "message" => "Username and password are required"
        ]);
    }
} else {
    // Method not allowed
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
}

$conn->close();
?>