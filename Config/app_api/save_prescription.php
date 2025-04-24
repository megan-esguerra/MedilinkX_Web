<?php
// database connection details
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

// Get data from POST request
$doctor_name = $_POST["doctor_name"];
$patient_name = $_POST["patient_name"];
$medicines = $_POST["medicines"];

// SQL query to insert data
$sql = "INSERT INTO prescriptions (doctor_name, patient_name, product_id, dosage, instructions, issued_date) VALUES ('$doctor_name', '$patient_name', NULL, NULL, '$medicines', CURDATE())";

if ($conn->query($sql) === TRUE) {
  echo "Prescription saved successfully";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>