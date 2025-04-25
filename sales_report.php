<?php
session_start();
require_once __DIR__ . '/components/db_connect.php';
require_once __DIR__ . '/components/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get date range with default values
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$period = isset($_GET['period']) ? $_GET['period'] : 'daily';

// Get sales data with grouping by period
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

// Get top selling products
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
            GROUP BY p.product_id, p.name
            ORDER BY total_quantity DESC
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll();
}

// Get detailed sales orders
function getDetailedSales($start_date, $end_date) {
    global $pdo;
    
    $sql = "SELECT 
                o.order_id,
                o.order_date,
                o.total_amount,
                o.status,
                o.payment_status,
                u.username,
                u.full_name,
                COUNT(oi.order_item_id) as items_count,
                GROUP_CONCAT(DISTINCT CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as products
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.product_id
            WHERE o.order_date BETWEEN ? AND ?
            GROUP BY o.order_id, o.order_date, o.total_amount, o.status, 
                     o.payment_status, u.username, u.full_name
            ORDER BY o.order_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll();
}

// Get comparison with previous period
function getPreviousPeriodComparison($start_date, $end_date) {
    global $pdo;
    
    $period_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
    $prev_start = date('Y-m-d', strtotime($start_date . " - {$period_days} days"));
    $prev_end = date('Y-m-d', strtotime($start_date . " - 1 day"));
    
    $sql = "SELECT 
                SUM(total_amount) as total_sales,
                COUNT(*) as total_orders
            FROM orders 
            WHERE order_date BETWEEN ? AND ?";
    
    // Current period
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    $current = $stmt->fetch();
    
    // Previous period
    $stmt->execute([$prev_start, $prev_end]);
    $previous = $stmt->fetch();
    
    return [
        'sales_change' => $previous['total_sales'] > 0 ? 
            (($current['total_sales'] - $previous['total_sales']) / $previous['total_sales'] * 100) : 0,
        'orders_change' => $previous['total_orders'] > 0 ? 
            (($current['total_orders'] - $previous['total_orders']) / $previous['total_orders'] * 100) : 0
    ];
}

// Get data
$sales_data = getSalesData($start_date, $end_date, $period);
$top_products = getTopProducts($start_date, $end_date);
$detailed_sales = getDetailedSales($start_date, $end_date);
$comparison = getPreviousPeriodComparison($start_date, $end_date);

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
    <style>
        .report-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .table tbody tr:hover {
            background-color: rgba(74, 137, 220, 0.05);
        }

        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }

        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-group-sm > .btn {
            padding: 0.25rem 0.5rem;
        }

        @media print {
            .sidebar, .header, .filter-section, .btn-group {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .report-card {
                box-shadow: none !important;
            }
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .data-table th {
            cursor: pointer;
        }

        .data-table th:hover {
            background-color: #e9ecef;
        }

        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>

        <div class="container-fluid">
            <!-- Date Range Filter -->
            <div class="filter-section">
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
                            <i class="fas fa-filter me-2"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="report-card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total Sales</h6>
                            <h3 class="mb-0">₱<?= number_format($total_sales, 2) ?></h3>
                            <small class="<?= $comparison['sales_change'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <i class="fas fa-arrow-<?= $comparison['sales_change'] >= 0 ? 'up' : 'down' ?>"></i>
                                <?= number_format(abs($comparison['sales_change']), 1) ?>% from previous period
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="report-card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total Orders</h6>
                            <h3 class="mb-0"><?= number_format($total_orders) ?></h3>
                            <small class="<?= $comparison['orders_change'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <i class="fas fa-arrow-<?= $comparison['orders_change'] >= 0 ? 'up' : 'down' ?>"></i>
                                <?= number_format(abs($comparison['orders_change']), 1) ?>% from previous period
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="report-card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Items Sold</h6>
                            <h3 class="mb-0"><?= number_format($total_items) ?></h3>
                            <small class="text-muted">Total units sold</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="report-card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Average Order Value</h6>
                            <h3 class="mb-0">₱<?= number_format($avg_order, 2) ?></h3>
                            <small class="text-muted">Per order</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="report-card">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0">Sales Trend</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="report-card">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0">Top Selling Products</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_products as $product): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td><?= number_format($product['total_quantity']) ?></td>
                                            <td>₱<?= number_format($product['total_revenue'], 2) ?></td>
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
            <div class="report-card">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sales Orders</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Report
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-2"></i>Export Excel
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover data-table" id="salesTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Products</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detailed_sales as $sale): 
                                    $statusClass = match($sale['status']) {
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                    $paymentClass = match($sale['payment_status']) {
                                        'paid' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>
                                <tr>
                                    <td>#<?= str_pad($sale['order_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td>
                                        <div><?= date('M d, Y', strtotime($sale['order_date'])) ?></div>
                                        <small class="text-muted">
                                            <?= date('h:i A', strtotime($sale['order_date'])) ?>
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($sale['full_name'] ?: $sale['username']) ?></td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                              title="<?= htmlspecialchars($sale['products']) ?>">
                                            <?= htmlspecialchars($sale['products']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= $sale['items_count'] ?> items
                                        </span>
                                    </td>
                                    <td>
                                        <strong>₱<?= number_format($sale['total_amount'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= ucfirst($sale['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $paymentClass ?>">
                                            <?= ucfirst($sale['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewOrder(<?= $sale['order_id'] ?>)" 
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="printInvoice(<?= $sale['order_id'] ?>)" 
                                                    title="Print Invoice">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </td>
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
                    label: 'Sales (₱)',
                    data: <?= json_encode(array_column($sales_data, 'total_sales')) ?>,
                    borderColor: '#4a89dc',
                    backgroundColor: 'rgba(74, 137, 220, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Functions for actions
        function viewOrder(orderId) {
            window.location.href = `view_order.php?id=${orderId}`;
        }

        function printInvoice(orderId) {
            window.open(`print_invoice.php?id=${orderId}`, '_blank');
        }

        function exportToExcel() {
            let table = document.getElementById('salesTable');
            let html = table.outerHTML;
            
            let url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            let downloadLink = document.createElement("a");
            downloadLink.href = url;
            downloadLink.download = 'sales_report.xls';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        // Table sorting
        document.addEventListener('DOMContentLoaded', function() {
            const getCellValue = (tr, idx) => tr.children[idx].innerText || tr.children[idx].textContent;
            
            const comparer = (idx, asc) => (a, b) => ((v1, v2) => 
                v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
            )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));
            
            document.querySelectorAll('.data-table th').forEach(th => th.addEventListener('click', (() => {
                const table = th.closest('table');
                const tbody = table.querySelector('tbody');
                Array.from(tbody.querySelectorAll('tr'))
                    .sort(comparer(Array.from(th.parentNode.children).indexOf(th), this.asc = !this.asc))
                    .forEach(tr => tbody.appendChild(tr));
            })));
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>