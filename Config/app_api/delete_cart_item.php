<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

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

// Check for order_item_id
if (!isset($_GET['order_item_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request. Order item ID is required."
    ]);
    exit;
}

// Get the order_item_id
$orderItemId = intval($_GET['order_item_id']);

// Delete the cart item
$stmt = $conn->prepare("DELETE FROM order_items WHERE order_item_id = ?");
$stmt->bind_param("i", $orderItemId);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Item deleted successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete item"
    ]);
}

$stmt->close();
$conn->close();
?>