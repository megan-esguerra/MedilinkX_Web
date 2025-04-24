<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medilinkx";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$orderId = $data['order_id'] ?? null;

if (!$orderId) {
    echo json_encode(["status" => "error", "message" => "Order ID is required."]);
    exit;
}

$query = $conn->prepare("UPDATE orders SET order_status = 'archived' WHERE order_id = ?");
$query->bind_param("i", $orderId);

if ($query->execute()) {
    echo json_encode(["status" => "success", "message" => "Order archived successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to archive order."]);
}

$conn->close();
?>