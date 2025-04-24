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

$query = "SELECT order_id, order_date, order_status, total_amount FROM orders";
$result = $conn->query($query);

$orders = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

echo json_encode($orders);

$conn->close();
?>