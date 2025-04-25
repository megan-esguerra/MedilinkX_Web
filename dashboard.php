<?php
session_start();
require_once 'Config/db.php';
require_once 'components/check_session.php';

// Check session
checkSession();

// Get user information with proper error handling
try {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // User not found in database
        session_destroy();
        header("Location: login.php?error=invalid_user");
        exit;
    }

    // Get dashboard statistics
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?? 0;
    // Updated query: count only users registered today
    $todayUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURRENT_DATE")->fetchColumn() ?? 0;
    $todayMoney = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(order_date) = CURRENT_DATE")->fetchColumn() ?? 0;
    $totalSales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders")->fetchColumn() ?? 0;

    // Get recent orders
    $recentOrders = $pdo->query("
        SELECT o.*, u.username, u.full_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        ORDER BY o.order_date DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC) ?? [];
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $user = [
        'username' => 'Guest',
        'full_name' => 'Guest User'
    ];
    $totalProducts = 0;
    $todayUsers = 0;
    $todayMoney = 0;
    $totalSales = 0;
    $recentOrders = [];
}

// Set username with validation
$userName = !empty($user['full_name']) ? htmlspecialchars($user['full_name']) :
           (!empty($user['username']) ? htmlspecialchars($user['username']) : 'Guest');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediLinkX</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #4a89dc;
      --secondary-color: #e1f0ff;
      --light-color: #ffffff;
      --text-color: #2c3e50;
      --light-text: #7b8a9d;
      --success-color: #37ca82;
      --warning-color: #ffb93c;
      --danger-color: #f25656;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f9ff;
      color: var(--text-color);
    }
    .sidebar {
      background-color: var(--light-color);
      height: 100vh;
      position: fixed;
      box-shadow: 0 0 15px rgba(122, 153, 198, 0.1);
      z-index: 1000;
      width: 250px;
      padding-top: 20px;
    }
    .logo {
      padding: 15px 20px;
      margin-bottom: 20px;
      font-weight: bold;
      font-size: 18px;
      border-bottom: 1px solid rgba(122, 153, 198, 0.1);
    }
    .main-content {
      margin-left: 250px;
      padding: 20px;
    }
    .nav-item {
      margin-bottom: 5px;
    }
    .nav-link {
      color: var(--light-text);
      padding: 10px 20px;
      border-radius: 8px;
      margin: 0 10px;
      transition: all 0.3s;
    }
    .nav-link.active, .nav-link:hover {
      background-color: var(--secondary-color);
      color: var(--primary-color);
    }
    .nav-link i {
      margin-right: 10px;
    }
    .header {
      background-color: var(--light-color);
      padding: 15px 20px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(122, 153, 198, 0.1);
      margin-bottom: 20px;
    }
    .search-bar {
      background-color: var(--secondary-color);
      border: none;
      padding: 10px 15px;
      border-radius: 8px;
      width: 300px;
    }
    .stat-card {
      background-color: var(--light-color);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 10px rgba(122, 153, 198, 0.1);
    }
    .stat-card h5 {
      color: var(--light-text);
      font-size: 14px;
      margin-bottom: 10px;
    }
    .stat-card h2 {
      font-weight: bold;
      margin-bottom: 5px;
    }
    .stat-card .icon {
      background-color: var(--secondary-color);
      width: 48px;
      height: 48px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-color);
      font-size: 20px;
    }
    .welcome-card {
      background: linear-gradient(135deg, #53a0fd, #4a89dc);
      color: white;
      padding: 30px;
      border-radius: 12px;
      margin-bottom: 20px;
      position: relative;
      overflow: hidden;
      width: 100%;
    }
    .welcome-card::after {
      content: '';
      position: absolute;
      width: 300px;
      height: 300px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      right: -100px;
      bottom: -100px;
    }
    .welcome-card h1 {
      font-weight: bold;
      margin-bottom: 5px;
    }
    /* Custom style for the profile button */
    #recordButton {
      background-color: #ffffff;
      color: #2c5893;
      border: none;
      padding: 10px 20px;
      border-radius: 10px;
      font-size: 14px;
      transition: background-color 0.3s ease;
    }
    #recordButton:hover {
      background-color: #3a73b7;
      color: white;
    }
    .account-section-title {
      font-size: 14px;
      color: var(--light-text);
      padding: 10px 20px;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-top: 20px;
    }
    .me-2 {
      margin-right: .5rem !important;
      background-color: #e1f0ff;
      color: #4a89dc;
    }
    span.positive-change {
      color: #10b53c;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
      <div class="logo">
          <i class="fas fa-chart-pie text-primary me-2"></i> MediLinkX
      </div>
      <ul class="nav flex-column">
          <li class="nav-item">
              <a href="#" class="nav-link active">
                  <i class="fas fa-home"></i> Dashboard
              </a>
          </li>
          <li class="nav-item">
              <a href="products.php" class="nav-link">
                  <i class="fas fa-table"></i> Products
              </a>
          </li>
          <li class="nav-item">
              <a href="categories.php" class="nav-link">
                  <i class="fas fa-list"></i> Categories
              </a>
          </li>
          <li class="nav-item">
              <a href="inventory_management.php" class="nav-link">
                  <i class="fas fa-boxes"></i> Inventory
              </a>
          </li>
          <li class="nav-item">
              <a href="sales_report.php" class="nav-link">
                  <i class="fas fa-chart-bar"></i> Sales Report
              </a>
          </li>
          <li class="nav-item">
              <a href="order.php" class="nav-link">
                  <i class="fas fa-align-right"></i> Orders
              </a>
          </li>
          <div class="account-section-title">ACCOUNT PAGES</div>
          <li class="nav-item">
              <a href="./profile.php" class="nav-link">
                  <i class="fas fa-user"></i> Profile
              </a>
          </li>
          <li class="nav-item">
              <a href="./Config/logout.php" class="nav-link">
                  <i class="fas fa-sign-in-alt"></i> Sign Out
              </a>
          </li>
      </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center header">
          <div>
              <nav aria-label="breadcrumb">
                  <ol class="breadcrumb mb-0">
                      <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i></a></li>
                      <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                  </ol>
              </nav>
              <h5 class="mb-0 mt-1">Dashboard</h5>
          </div>
          <div class="d-flex">
              <input type="text" class="form-control search-bar me-3" placeholder="Type here...">
              <div class="d-flex align-items-center">
                  <button class="btn btn-light me-2"><i class="fas fa-cog"></i></button>
                  <button class="btn btn-light me-2"><i class="fas fa-bell"></i></button>
              </div>
          </div>
      </div>

      <!-- Stats Row with static values -->
      <div class="row">
          <div class="col-md-3">
              <div class="stat-card d-flex justify-content-between">
                  <div>
                      <h5>Today's Money</h5>
                      <h2>₱89.91</h2>
                      <span class="positive-change">+0.0%</span>
                  </div>
                  <div class="icon">
                      <i class="fas fa-wallet"></i>
                  </div>
              </div>
          </div>
          <div class="col-md-3">
              <div class="stat-card d-flex justify-content-between">
                  <div>
                      <h5>Today's Users</h5>
                      <h2><?= $todayUsers ?></h2>
                      <span class="positive-change">+0.0%</span>
                  </div>
                  <div class="icon">
                      <i class="fas fa-users"></i>
                  </div>
              </div>
          </div>
          <div class="col-md-3">
              <div class="stat-card d-flex justify-content-between">
                  <div>
                      <h5>New Clients</h5>
                      <h2>+5</h2>
                      <span class="positive-change">+0.0%</span>
                  </div>
                  <div class="icon">
                      <i class="fas fa-user-plus"></i>
                  </div>
              </div>
          </div>
          <div class="col-md-3">
              <div class="stat-card d-flex justify-content-between">
                  <div>
                      <h5>Monthly Sales</h5>
                      <h2>₱89.91</h2>
                      <span class="positive-change">+0.0%</span>
                  </div>
                  <div class="icon">
                      <i class="fas fa-shopping-cart"></i>
                  </div>
              </div>
          </div>
      </div>

      <!-- Full Width Welcome Card with redirection to Profile -->
      <div class="row">
          <div class="col-12">
              <div class="welcome-card">
                  <p class="mb-1">Welcome back,</p>
                  <h1><?= $userName ?></h1>
                  <p>Glad to see you again!<br>Ask me anything.</p>
                  <button id="recordButton" class="btn btn-light btn-sm mt-3 profile-btn">
                      <i class="fas fa-user-circle me-1"></i>Go to profile
                  </button>
              </div>
          </div>
      </div>
  </div>

  <!-- Bootstrap & jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Redirect Go to profile button -->
  <script>
      const recordButton = document.getElementById('recordButton');
      recordButton.addEventListener('click', () => {
          window.location.href = "./profile.php";
      });
  </script>
</body>
</html>