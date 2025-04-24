<?php
session_start();
require_once 'Config/db.php';
require_once 'components/check_session.php';

// Check session
checkSession();

// Validate order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="alert alert-danger">Invalid order ID</div>';
    exit;
}

$order_id = (int)$_GET['id'];

try {
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
        echo '<div class="alert alert-danger">Order not found</div>';
        exit;
    }
    
    // Get order items - FIXED QUERY: Changed "p.price as product_price" to "p.price"
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
    
    // Order status badge
    $statusBadge = 'badge badge-' . $order['status'];
    $paymentBadge = 'badge badge-' . $order['payment_status'];
    
    // Display order details
    ?>
    <div class="order-detail-container">
        <div class="row mb-3">
            <div class="col-12">
                <h4>Order #<?= $order['order_id'] ?></h4>
                <p class="text-muted mb-1">
                    <i class="far fa-calendar-alt me-2"></i>
                    <?= date('F j, Y h:i A', strtotime($order['order_date'])) ?>
                </p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="mb-2">Status</h6>
                <span class="<?= $statusBadge ?> px-3 py-2">
                    <?= ucfirst($order['status']) ?>
                </span>
            </div>
            <div class="col-md-6">
                <h6 class="mb-2">Payment</h6>
                <span class="<?= $paymentBadge ?> px-3 py-2">
                    <?= ucfirst($order['payment_status']) ?>
                </span>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="mb-2">Customer Details</h6>
                <p class="mb-0">
                    <strong>Name:</strong> 
                    <?= !empty($order['full_name']) ? htmlspecialchars($order['full_name']) : htmlspecialchars($order['username']) ?>
                </p>
                <?php if (!empty($order['email'])): ?>
                    <p class="mb-0">
                        <strong>Email:</strong> 
                        <?= htmlspecialchars($order['email']) ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($order['phone'])): ?>
                    <p class="mb-0">
                        <strong>Phone:</strong> 
                        <?= htmlspecialchars($order['phone']) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="mb-3">Order Items</h6>
                <?php if (count($orderItems) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Qty</th>
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
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">No items found for this order</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 offset-md-6">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-end">Subtotal:</td>
                            <td class="text-end" width="100"><?= formatCurrency($order['total_amount']) ?></td>
                        </tr>
                        <tr class="fw-bold">
                            <td class="text-end">Total:</td>
                            <td class="text-end"><?= formatCurrency($order['total_amount']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error fetching order details: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>