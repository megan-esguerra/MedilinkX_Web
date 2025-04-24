<?php
session_start();
require_once __DIR__ . '/components/db_connect.php';
require_once __DIR__ . '/components/functions.php';

// Get date range
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$period = isset($_GET['period']) ? $_GET['period'] : 'daily';

// Get sales data
function getSalesData($start_date, $end_date, $period) {
    global $pdo;
    
    $group_by = $period == 'monthly' ? 'MONTH(o.order_date)' : 
               ($period == 'weekly' ? 'WEEK(o.order_date)' : 'DATE(o.order_date)');
    
    $sql = "SELECT 
                {$group_by} as date_group,
                COUNT(DISTINCT o.order_id) as total_orders,
                SUM(o.total_amount) as total_sales,
                SUM(oi.quantity) as items_sold,
                AVG(o.total_amount) as avg_order_value
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            WHERE o.order_date BETWEEN ? AND ?
            GROUP BY date_group
            ORDER BY date_group DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll();
}

// Get top products
function getTopProducts($start_date, $end_date) {
    global $pdo;
    
    $sql = "SELECT 
                p.name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.price_per_unit) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            JOIN orders o ON oi.order_id = o.order_id
            WHERE o.order_date BETWEEN ? AND ?
            GROUP BY p.product_id
            ORDER BY total_quantity DESC
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll();
}

$sales_data = getSalesData($start_date, $end_date, $period);
$top_products = getTopProducts($start_date, $end_date);

// Calculate totals
$total_sales = array_sum(array_column($sales_data, 'total_sales'));
$total_orders = array_sum(array_column($sales_data, 'total_orders'));
$total_items = array_sum(array_column($sales_data, 'items_sold'));
$avg_order = $total_orders > 0 ? $total_sales / $total_orders : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - MediLinkX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php require_once __DIR__ . '/components/styles.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>

        <div class="container-fluid">
            <!-- Date Range Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Period</label>
                            <select name="period" class="form-select">
                                <option value="daily" <?= $period == 'daily' ? 'selected' : '' ?>>Daily</option>
                                <option value="weekly" <?= $period == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                <option value="monthly" <?= $period == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total Sales</h6>
                            <h3 class="mb-0">$<?= number_format($total_sales, 2) ?></h3>
                            <small class="text-success">
                                <i class="fas fa-arrow-up"></i> 12% from previous period
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total Orders</h6>
                            <h3 class="mb-0"><?= number_format($total_orders) ?></h3>
                            <small class="text-success">
                                <i class="fas fa-arrow-up"></i> 5% from previous period
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Items Sold</h6>
                            <h3 class="mb-0"><?= number_format($total_items) ?></h3>
                            <small class="text-danger">
                                <i class="fas fa-arrow-down"></i> 3% from previous period
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Average Order Value</h6>
                            <h3 class="mb-0">$<?= number_format($avg_order, 2) ?></h3>
                            <small class="text-success">
                                <i class="fas fa-arrow-up"></i> 8% from previous period
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Chart -->
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Sales Trend</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Top Selling Products</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_products as $product): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td><?= number_format($product['total_quantity']) ?></td>
                                            <td>$<?= number_format($product['total_revenue'], 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Sales Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Detailed Sales Data</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Items Sold</th>
                                    <th>Total Sales</th>
                                    <th>Avg Order Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_data as $data): ?>
                                <tr>
                                    <td><?= $data['date_group'] ?></td>
                                    <td><?= number_format($data['total_orders']) ?></td>
                                    <td><?= number_format($data['items_sold']) ?></td>
                                    <td>$<?= number_format($data['total_sales'], 2) ?></td>
                                    <td>$<?= number_format($data['avg_order_value'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($sales_data, 'date_group')) ?>,
                datasets: [{
                    label: 'Sales ($)',
                    data: <?= json_encode(array_column($sales_data, 'total_sales')) ?>,
                    borderColor: '#4a89dc',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>