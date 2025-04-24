<?php
// Start session at the beginning of the file
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Connect to database to fetch user details
require_once 'Config/db.php';

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Default name if user data is incomplete
$userName = isset($user['full_name']) && !empty($user['full_name']) ? 
    htmlspecialchars($user['full_name']) : 
    htmlspecialchars($user['username']);
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
        
        .satisfaction-card {
            position: relative;
            padding-top: 30px;
            text-align: center;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        
        .percentage {
            font-size: 36px;
            font-weight: bold;
            margin-top: 20px;
        }
        
        .percentage-label {
            font-size: 14px;
            color: var(--light-text);
        }
        
        .referral-container {
            margin-bottom: 10px;
        }
        
        .referral-label {
            font-size: 14px;
            color: var(--light-text);
        }
        
        .referral-value {
            font-size: 24px;
            font-weight: bold;
        }
        
        .safety-score {
            font-size: 36px;
            font-weight: bold;
        }
        
        .projects-table {
            width: 100%;
        }
        
        .projects-table th {
            color: var(--light-text);
            font-weight: normal;
            padding: 10px 15px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .projects-table td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .projects-table tr {
            border-bottom: 1px solid rgba(122, 153, 198, 0.1);
        }
        
        .completed-badge {
            background-color: rgba(55, 202, 130, 0.2);
            color: var(--success-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }
        
        .positive-change {
            color: var(--success-color);
        }
        
        .negative-change {
            color: var(--danger-color);
        }
        
        .section-title {
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .documentation-card {
            background: linear-gradient(135deg, #4a89dc, #5d9cec);
            color: white;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .documentation-card::before {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            left: -50px;
            bottom: -50px;
        }
        
        .documentation-card .btn {
            background-color: white;
            color: var(--primary-color);
            border: none;
            margin-top: 15px;
            font-weight: 600;
        }
        
        .metric-item {
            padding: 15px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .metric-item.active {
            background-color: var(--secondary-color);
        }
        
        .metric-item .icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .metric-item.active .icon {
            background-color: var(--primary-color);
            color: white;
        }
        
        .metric-item .value {
            font-weight: bold;
            font-size: 20px;
            margin-bottom: 0;
        }
        
        .metric-label {
            font-size: 12px;
            color: var(--light-text);
        }
        
        .blue-line {
            background-color: var(--primary-color);
            height: 3px;
            width: 50px;
            margin-top: 5px;
        }
        
        .account-section-title {
            font-size: 14px;
            color: var(--light-text);
            padding: 10px 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
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
<!-- In your dashboard.php sidebar -->
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
                <a href="#" class="nav-link">
                    <i class="fas fa-file-invoice-dollar"></i> Stocks
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-align-right"></i> Orders
                </a>
            </li>
            
            <div class="account-section-title">ACCOUNT PAGES</div>
            
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-user"></i> Profile
                </a>
            </li>
            <li class="nav-item">
                <a href="./Config/logout.php" class="nav-link">
                    <i class="fas fa-sign-in-alt"></i> Sign Out
                </a>
            </li>
        </ul>
        
        <!-- <div class="mt-auto p-3">
            <div class="documentation-card mt-5">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-star text-white p-2 bg-white bg-opacity-25 rounded me-2"></i>
                    <h5 class="mb-0">Need help?</h5>
                </div>
                <p class="small mb-0">Please check our docs</p>
                <button class="btn btn-sm">DOCUMENTATION</button>
            </div>
        </div> -->
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

        <!-- Stats Row -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between">
                    <div>
                        <h5>Today's Money</h5>
                        <h2>$53,000</h2>
                        <span class="positive-change">+55%</span>
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
                        <h2>2,300</h2>
                        <span class="positive-change">+3%</span>
                    </div>
                    <div class="icon">
                        <i class="fas fa-globe"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between">
                    <div>
                        <h5>New Clients</h5>
                        <h2>+3,462</h2>
                        <span class="positive-change">+2%</span>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between">
                    <div>
                        <h5>Total Sales</h5>
                        <h2>$103,430</h2>
                        <span class="positive-change">+5%</span>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome & Data Row -->
        <!-- Welcome & Data Row -->
        <div class="row">
            <div class="col-md-8">
                <div class="welcome-card">
                    <p class="mb-1">Welcome back,</p>
                    <h1><?php echo $userName; ?></h1>
                    <p>Glad to see you again!<br>Ask me anything.</p>
                    <button class="btn btn-light btn-sm mt-3">
                        <i class="fas fa-play me-1"></i> Tap to record
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card h-70">
                    <h5 class="mb-4">Satisfaction Rate</h5>
                    <div class="satisfaction-card">
                        <div id="satisfactionChart" style="width: 100%; height: 150px;"></div>
                        <div class="percentage">95%</div>
                        <div class="percentage-label">Based on likes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referral & Sales Overview -->
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <h5 class="mb-4">Referral Tracking</h5>
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="referral-container">
                                <div class="referral-label">Invited</div>
                                <div class="referral-value">145</div>
                                <div class="referral-label">people</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="referral-container">
                                <div class="referral-label">Bonus</div>
                                <div class="referral-value">1,465</div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-end">
                            <div class="referral-label">Safety</div>
                            <div class="safety-score">9.3</div>
                            <div class="referral-label">Total Score</div>
                        </div>
                        <div style="width: 100px; height: 100px;">
                            <canvas id="safetyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="stat-card">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="section-title mb-0">Sales Overview</h5>
                        <span class="positive-change">+5% more in 2021</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects & Active Users -->
        <div class="row">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="section-title mb-0">Projects</h5>
                        <span class="completed-badge">
                            <i class="fas fa-check-circle me-1"></i> 30 done this month
                        </span>
                    </div>
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>COMPANIES</th>
                                <th>MEMBERS</th>
                                <th>BUDGET</th>
                                <th>COMPLETION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Company A</td>
                                <td>4 members</td>
                                <td>$14,000</td>
                                <td>60%</td>
                            </tr>
                            <tr>
                                <td>Company B</td>
                                <td>6 members</td>
                                <td>$21,000</td>
                                <td>80%</td>
                            </tr>
                            <tr>
                                <td>Company C</td>
                                <td>3 members</td>
                                <td>$9,500</td>
                                <td>40%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="section-title mb-0">Active Users</h5>
                        <span class="positive-change">(+23) than last week</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="userActivityChart"></canvas>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-3">
                            <div class="metric-item active">
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="value">32,984</div>
                                <div class="metric-label">Users</div>
                                <div class="blue-line"></div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="metric-item">
                                <div class="icon">
                                    <i class="fas fa-mouse-pointer"></i>
                                </div>
                                <div class="value">2.42M</div>
                                <div class="metric-label">Clicks</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="metric-item">
                                <div class="icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div class="value">2,400$</div>
                                <div class="metric-label">Sales</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="metric-item">
                                <div class="icon">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="value">320</div>
                                <div class="metric-label">Items</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Overview -->
        <div class="row">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="section-title mb-0">Orders overview</h5>
                        <span class="completed-badge">
                            <i class="fas fa-check-circle me-1"></i> +30% this month
                        </span>
                    </div>
                    <div class="d-flex align-items-center mt-4">
                        <div class="icon me-3">
                            <i class="fas fa-bell text-primary"></i>
                        </div>
                        <div>
                            <strong>$2400, Design changes</strong>
                            <div class="small text-muted">22 DEC 7:20 PM</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-4">
                        <div class="icon me-3">
                            <i class="fas fa-archive text-success"></i>
                        </div>
                        <div>
                            <strong>New order #4219423</strong>
                            <div class="small text-muted">21 DEC 11:21 PM</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-4">
                        <div class="icon me-3">
                            <i class="fas fa-credit-card text-warning"></i>
                        </div>
                        <div>
                            <strong>Server Payments for April</strong>
                            <div class="small text-muted">21 DEC 9:28 PM</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Sales Overview Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Sales 2021',
                        data: [200, 250, 300, 350, 400, 500, 450, 400, 350, 300, 450, 500],
                        borderColor: '#4a89dc',
                        backgroundColor: 'rgba(74, 137, 220, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2
                    },
                    {
                        label: 'Sales 2020',
                        data: [300, 200, 250, 470, 300, 400, 550, 350, 450, 300, 400, 350],
                        borderColor: '#37ca82',
                        backgroundColor: 'rgba(55, 202, 130, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(200, 200, 200, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // User Activity Chart
        const userCtx = document.getElementById('userActivityChart').getContext('2d');
        const userChart = new Chart(userCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed'],
                datasets: [{
                    label: 'Active Users',
                    data: [320, 240, 100, 360, 200, 310, 250, 400, 320, 120],
                    backgroundColor: '#4a89dc',
                    borderRadius: 4,
                    borderWidth: 0,
                    barThickness: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(200, 200, 200, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Safety Chart (Doughnut)
        const safetyCtx = document.getElementById('safetyChart').getContext('2d');
        const safetyChart = new Chart(safetyCtx, {
            type: 'doughnut',
            data: {
                labels: ['Score', 'Remaining'],
                datasets: [{
                    data: [93, 7],
                    backgroundColor: ['#37ca82', '#e1f0ff'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '80%',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });

        // Satisfaction Chart (Semi-Circle)
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('satisfactionChart').getContext('2d');
            
            // Create gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, 150);
            gradient.addColorStop(0, 'rgba(74, 137, 220, 0.9)');
            gradient.addColorStop(1, 'rgba(55, 202, 130, 0.9)');
            
            // Draw arc
            ctx.beginPath();
            ctx.lineWidth = 20;
            ctx.strokeStyle = gradient;
            ctx.arc(150, 75, 60, Math.PI, 0, false);
            ctx.stroke();
            
            // Background arc
            ctx.beginPath();
            ctx.lineWidth = 20;
            ctx.strokeStyle = 'rgba(200, 200, 200, 0.2)';
            ctx.arc(150, 75, 60, 0, Math.PI, true);
            ctx.stroke();
        });
    </script>
</body>
</html>