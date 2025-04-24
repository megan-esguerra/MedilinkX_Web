<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

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

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get parameters
    $userId = $_POST['user_id'] ?? 0;
    $productId = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    $pricePerUnit = $_POST['price'] ?? 0;
    $subtotal = $quantity * $pricePerUnit;

    // Validate data
    if (!$userId || !$productId || $pricePerUnit <= 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid parameters"
        ]);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if user has a pending order
        $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND order_status = 'pending'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $orderId = 0;

        // If no pending order exists, create one
        if ($result->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO orders (user_id, order_status) VALUES (?, 'pending')");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $orderId = $conn->insert_id;
        } else {
            $row = $result->fetch_assoc();
            $orderId = $row['order_id'];
        }

        // Check if item already exists in cart
        $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $orderId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing item quantity and subtotal
            $row = $result->fetch_assoc();
            $newQuantity = $row['quantity'] + $quantity;
            $newSubtotal = $newQuantity * $pricePerUnit;

            $stmt = $conn->prepare("UPDATE order_items SET quantity = ?, subtotal = ? WHERE order_id = ? AND product_id = ?");
            $stmt->bind_param("idii", $newQuantity, $newSubtotal, $orderId, $productId);
        } else {
            // Add new item to cart
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_per_unit, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidi", $orderId, $productId, $quantity, $pricePerUnit, $subtotal);
        }

        $stmt->execute();

        // Commit transaction
        $conn->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Item added to cart",
            "order_id" => $orderId
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();

        echo json_encode([
            "status" => "error",
            "message" => "Database error: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
}

$conn->close();
?>