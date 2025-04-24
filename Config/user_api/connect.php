<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medilink";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if($conn){
    echo json_encode(["status"=>"success", "message"=>"Database connected successfully"]);
}else {
    echo json_encode(["status"=>"error", "message"=>"Database connection failed"]);
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>