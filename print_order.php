<?php
session_start();
require_once 'Config/db.php';
require_once 'components/check_session.php';

// Check session
checkSession();

// Validate order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('<div class="alert alert-danger">Invalid order ID</div>');
}

$order_id = (int)$_GET['id'];

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.username, u.email, u.phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('<div class="alert alert-danger">Order not found</div>');
}

// Get order items - FIX OPTION 1: Use the column name without alias
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.price
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order['order_id'] ?> - MediLinkX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .print-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .print-only {
            display: block;
        }
        .order-details {
            margin-bottom: 30px;
        }
        .customer-details, .order-summary {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                margin: 15mm 10mm;
            }
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="no-print text-center mb-3">
            <button onclick="window.print()" class="btn btn-primary">Print Order</button>
            <a href="order.php" class="btn btn-secondary ms-2">Back to Orders</a>
        </div>
        
        <div class="print-header">
            <div class="company-name">MediLinkX</div>
            <div>Order Receipt</div>
        </div>
        
        <div class="order-details">
            <div class="row">
                <div class="col-md-6">
                    <h5>Order #<?= $order['order_id'] ?></h5>
                    <p>Date: <?= date('F j, Y h:i A', strtotime($order['order_date'])) ?></p>
                    <p>Status: <?= ucfirst($order['status']) ?></p>
                    <p>Payment Status: <?= ucfirst($order['payment_status']) ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <h5>Customer</h5>
                    <p><?= !empty($order['full_name']) ? htmlspecialchars($order['full_name']) : htmlspecialchars($order['username']) ?></p>
                    <?php if (!empty($order['email'])): ?>
                        <p><?= htmlspecialchars($order['email']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($order['phone'])): ?>
                        <p><?= htmlspecialchars($order['phone']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="item-details">
            <h5>Order Items</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    foreach ($orderItems as $item): 
                        $itemTotal = $item['quantity'] * $item['price'];
                        $subtotal += $itemTotal;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end"><?= formatCurrency($item['price']) ?></td>
                        <td class="text-end"><?= formatCurrency($itemTotal) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="text-end">Total:</td>
                        <td class="text-end"><?= formatCurrency($order['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="footer">
            <p>Thank you for your purchase!</p>
            <p>For any questions, please contact support@medilinkx.com</p>
            <p>Printed on: <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Uncomment the line below to automatically open print dialog
            // window.print();
        };
    </script>
</body>
</html>