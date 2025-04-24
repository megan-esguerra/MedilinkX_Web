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


// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Query to fetch products
    $sql = "SELECT product_id, name, description, stock, price, unit FROM products ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    $products = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    echo json_encode([
        "status" => "success",
        "products" => $products
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
}

$conn->close();
?>