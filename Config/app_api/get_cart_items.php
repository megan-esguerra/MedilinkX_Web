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

// CRITICAL: Remove any connection success message here!
// Do not echo anything before the main JSON response

// Check for user_id
if (!isset($_GET['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request. User ID is required."
    ]);
    exit;
}

// Get the user_id
$userId = $_GET['user_id'];

// Fetch the pending order for this user
$stmt = $conn->prepare("
    SELECT o.order_id
    FROM orders o
    WHERE o.user_id = ? AND o.order_status = 'pending'
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows > 0) {
    $orderRow = $orderResult->fetch_assoc();
    $orderId = $orderRow['order_id'];

    // Fetch cart items for this order
    $cartStmt = $conn->prepare("
        SELECT oi.order_item_id, oi.product_id, p.name AS product_name,
               oi.quantity, oi.price_per_unit, oi.subtotal
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");

    $cartStmt->bind_param("i", $orderId);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();

    $cartItems = [];
    $totalAmount = 0;

    while ($row = $cartResult->fetch_assoc()) {
        $cartItems[] = $row;
        $totalAmount += $row['subtotal'];
    }

    $tax = $totalAmount * 0.12; // 12% tax
    $grandTotal = $totalAmount + $tax;

    echo json_encode([
        "status" => "success",
        "order_id" => $orderId,
        "cart_items" => $cartItems,
        "subtotal" => $totalAmount,
        "tax" => $tax,
        "total" => $grandTotal
    ]);
} else {
    echo json_encode([
        "status" => "empty",
        "message" => "No items in cart"
    ]);
}

$conn->close();
?>