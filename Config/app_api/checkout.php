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

$conn->begin_transaction();

try {
    $orderItemsQuery = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $orderItemsQuery->bind_param("i", $orderId);
    $orderItemsQuery->execute();
    $orderItemsResult = $orderItemsQuery->get_result();

    while ($item = $orderItemsResult->fetch_assoc()) {
        $productId = $item['product_id'];
        $quantity = $item['quantity'];

        $updateStockQuery = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?");
        $updateStockQuery->bind_param("iii", $quantity, $productId, $quantity);
        $updateStockQuery->execute();

        if ($updateStockQuery->affected_rows === 0) {
            throw new Exception("Insufficient stock for product ID: $productId");
        }
    }

    $updateOrderQuery = $conn->prepare("UPDATE orders SET order_status = 'completed' WHERE order_id = ?");
    $updateOrderQuery->bind_param("i", $orderId);
    $updateOrderQuery->execute();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Checkout completed successfully."]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>