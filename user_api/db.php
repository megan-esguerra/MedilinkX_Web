<?php
$host = "localhost";
$dbname = "u591433413_medilink"; // Fixed variable name
$username = "u591433413_medilink";
$password = "gAlrSyYN7i";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>