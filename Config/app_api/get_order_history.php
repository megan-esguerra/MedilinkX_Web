<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medilinkx";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

if (!isset($_GET['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User ID is required."]);
    exit;
}

$userId = intval($_GET['user_id']);

$query = $conn->prepare("
    SELECT order_id, order_date, total_amount
    FROM orders
    WHERE user_id = ? AND order_status = 'completed'
");
$query->bind_param("i", $userId);
$query->execute();
$result = $query->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode(["status" => "success", "orders" => $orders]);

$conn->close();
?>