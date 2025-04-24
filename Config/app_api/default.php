<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once './db.php';

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different routes
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$table = $request[0] ?? '';
$id = $request[1] ?? null; // Extract the ID from the URL path

if ($table === 'users') {
    switch ($method) {
        case 'GET':
            if ($id) {
                getUserById($pdo, $id); // Fetch a single user by ID
            } else {
                getUsers($pdo); // Fetch all users
            }
            break;
        case 'POST':
            createUser($pdo);
            break;
        case 'PUT':
            updateUser($pdo);
            break;
        case 'DELETE':
            deleteUser($pdo);
            break;
        default:
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Method not allowed']);
    }
} else {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Endpoint not found']);
}

// Function to get all users
function getUsers($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM users');
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $users]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to get a single user by ID
function getUserById($pdo, $id) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode(['status' => 'success', 'data' => $user]);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to create a new user
function createUser($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        return;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$data['name'], $data['email'], password_hash($data['password'], PASSWORD_BCRYPT)]);
        $userId = $pdo->lastInsertId();
        echo json_encode(['status' => 'success', 'message' => 'User created', 'id' => $userId]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to update a user
function updateUser($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? null;

    if (!$id || !isset($data['name']) || !isset($data['email'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Missing ID or required fields']);
        return;
    }

    try {
        $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        $stmt->execute([$data['name'], $data['email'], $id]);
        echo json_encode(['status' => 'success', 'message' => 'User updated']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to delete a user
function deleteUser($pdo) {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
        return;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'User deleted']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>ssss