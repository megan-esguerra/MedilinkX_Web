<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Include database configuration
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


// Check if it's a GET request with user_id parameter
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT full_name, role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode([
            "status" => "success",
            "user" => $user
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request. User ID is required."
    ]);
}

$conn->close();
?>