<?php
session_start();
require_once 'Config/db.php';
require_once 'components/check_session.php';

// Check session
checkSession();

// Initialize variables
$filter_status = $_GET['status'] ?? '';
$filter_payment = $_GET['payment'] ?? '';
$search_term = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'order_id';
$sort_order = $_GET['order'] ?? 'DESC';
$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$total_query = "SELECT COUNT(*) FROM orders WHERE 1=1";
if (!empty($filter_status)) {
    $total_query .= " AND status = :status";
}
if (!empty($filter_payment)) {
    $total_query .= " AND payment_status = :payment";
}
if (!empty($search_term)) {
    $total_query .= " AND (order_id LIKE :search OR total_amount LIKE :search)";
}

$total_stmt = $pdo->prepare($total_query);

if (!empty($filter_status)) {
    $total_stmt->bindParam(':status', $filter_status);
}
if (!empty($filter_payment)) {
    $total_stmt->bindParam(':payment', $filter_payment);
}
if (!empty($search_term)) {
    $search_param = "%$search_term%";
    $total_stmt->bindParam(':search', $search_param);
}

$total_stmt->execute();
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get orders
$query = "SELECT o.*, u.full_name, u.username 
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.user_id
          WHERE 1=1";

if (!empty($filter_status)) {
    $query .= " AND o.status = :status";
}
if (!empty($filter_payment)) {
    $query .= " AND o.payment_status = :payment";
}
if (!empty($search_term)) {
    $query .= " AND (o.order_id LIKE :search OR o.total_amount LIKE :search)";
}

$query .= " ORDER BY o.$sort_by $sort_order LIMIT :offset, :limit";

$stmt = $pdo->prepare($query);

if (!empty($filter_status)) {
    $stmt->bindParam(':status', $filter_status);
}
if (!empty($filter_payment)) {
    $stmt->bindParam(':payment', $filter_payment);
}
if (!empty($search_term)) {
    $search_param = "%$search_term%";
    $stmt->bindParam(':search', $search_param);
}

$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    $update = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $update->execute([$new_status, $order_id]);
    
    header("Location: order.php?msg=status_updated");
    exit;
}

// Handle payment status update
if (isset($_POST['update_payment'])) {
    $order_id = $_POST['order_id'];
    $new_payment_status = $_POST['new_payment_status'];
    
    $update = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE order_id = ?");
    $update->execute([$new_payment_status, $order_id]);
    
    header("Location: order.php?msg=payment_updated");
    exit;
}

// Handle order deletion
if (isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    
    // Add safety check - only delete if status is 'cancelled'
    $check = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
    $check->execute([$order_id]);
    $current_status = $check->fetchColumn();
    
    if ($current_status === 'cancelled') {
        $delete = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
        $delete->execute([$order_id]);
        header("Location: order.php?msg=order_deleted");
    } else {
        header("Location: order.php?error=order_not_cancelled");
    }
    exit;
}

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
    <title>Order Management | MediLinkX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php require_once __DIR__ . '/components/styles.php'; ?>
    <style>
        .filter-container {
            background-color: var(--light-color);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        
        .order-table {
            background-color: var(--light-color);
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        
        .order-table th {
            color: var(--light-text);
            font-weight: 500;
            border: none;
            padding-bottom: 1rem;
        }
        
        .order-table td {
            border-color: #f1f5f9;
            padding: 1rem 0.5rem;
            vertical-align: middle;
        }
        
        .badge-pending {
            background-color: var(--warning-color);
            color: #845d00;
        }
        
        .badge-processing {
            background-color: #b7ebff;
            color: #006c99;
        }
        
        .badge-completed {
            background-color: #d1f5df;
            color: #0c753a;
        }
        
        .badge-cancelled {
            background-color: #ffd2d2;
            color: #a30000;
        }
        
        .badge-paid {
            background-color: #d1f5df;
            color: #0c753a;
        }
        
        .badge-failed {
            background-color: #ffd2d2;
            color: #a30000;
        }
        
        .order-actions {
            display: flex;
            gap: 6px;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 1.5rem;
        }
        
        .modal-header, .modal-footer {
            border: none;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/components/header.php'; ?>

        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title">Order Management</h1>
            </div>
            
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    $msg = $_GET['msg'];
                    switch ($msg) {
                        case 'status_updated':
                            echo "Order status updated successfully!";
                            break;
                        case 'payment_updated':
                            echo "Payment status updated successfully!";
                            break;
                        case 'order_deleted':
                            echo "Order deleted successfully!";
                            break;
                        default:
                            echo "Action completed successfully!";
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    $error = $_GET['error'];
                    switch ($error) {
                        case 'order_not_cancelled':
                            echo "Only cancelled orders can be deleted!";
                            break;
                        default:
                            echo "An error occurred. Please try again.";
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filter-container">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Order Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $filter_status === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="payment" class="form-label">Payment Status</label>
                        <select name="payment" id="payment" class="form-select">
                            <option value="">All Payment Statuses</option>
                            <option value="pending" <?= $filter_payment === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= $filter_payment === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="failed" <?= $filter_payment === 'failed' ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search Orders</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Order ID or Amount" value="<?= htmlspecialchars($search_term) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="sort" class="form-label">Sort By</label>
                        <select name="sort" id="sort" class="form-select">
                            <option value="order_date" <?= $sort_by === 'order_date' ? 'selected' : '' ?>>Order Date</option>
                            <option value="order_id" <?= $sort_by === 'order_id' ? 'selected' : '' ?>>Order ID</option>
                            <option value="total_amount" <?= $sort_by === 'total_amount' ? 'selected' : '' ?>>Total Amount</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="order.php" class="btn btn-outline-secondary ms-2">Reset</a> 
                    </div>
                </form>
            </div>
            
            <!-- Order Table -->
            <div class="order-table">
                <table class="table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['order_id'] ?></td>
                                    <td>
                                        <?= !empty($order['full_name']) ? htmlspecialchars($order['full_name']) : htmlspecialchars($order['username'] ?? 'Unknown') ?>
                                    </td>
                                    <td><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatCurrency($order['total_amount']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $order['payment_status'] ?>">
                                            <?= ucfirst($order['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="order-actions">
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal" 
                                                    onclick="viewOrder(<?= $order['order_id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#statusModal" 
                                                    onclick="prepareOrderStatus(<?= $order['order_id'] ?>, '<?= $order['status'] ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal"
                                                    onclick="preparePaymentStatus(<?= $order['order_id'] ?>, '<?= $order['payment_status'] ?>')">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    onclick="prepareDelete(<?= $order['order_id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i>
                                    <p class="mb-0">No orders found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $filter_status ?>&payment=<?= $filter_payment ?>&search=<?= $search_term ?>&sort=<?= $sort_by ?>&order=<?= $sort_order ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $filter_status ?>&payment=<?= $filter_payment ?>&search=<?= $search_term ?>&sort=<?= $sort_by ?>&order=<?= $sort_order ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $filter_status ?>&payment=<?= $filter_payment ?>&search=<?= $search_term ?>&sort=<?= $sort_by ?>&order=<?= $sort_order ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- View Order Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading order details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="printOrderBtn" class="btn btn-primary">
                        <i class="fas fa-print me-1"></i> Print
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" id="updateStatusForm">
                        <input type="hidden" name="order_id" id="status_order_id">
                        <input type="hidden" name="update_status" value="1">
                        
                        <div class="mb-3">
                            <label for="new_status" class="form-label">Select New Status</label>
                            <select class="form-select" name="new_status" id="new_status" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="updateStatusForm" class="btn btn-primary">Update Status</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Payment Status Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Payment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" id="updatePaymentForm">
                        <input type="hidden" name="order_id" id="payment_order_id">
                        <input type="hidden" name="update_payment" value="1">
                        
                        <div class="mb-3">
                            <label for="new_payment_status" class="form-label">Select New Payment Status</label>
                            <select class="form-select" name="new_payment_status" id="new_payment_status" required>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="updatePaymentForm" class="btn btn-primary">Update Payment</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                        <h5>Are you sure you want to delete this order?</h5>
                        <p class="text-muted">This action can only be performed on cancelled orders and cannot be undone.</p>
                    </div>
                    
                    <form action="" method="POST" id="deleteForm">
                        <input type="hidden" name="order_id" id="delete_order_id">
                        <input type="hidden" name="delete_order" value="1">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="deleteForm" class="btn btn-danger">Delete Order</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Prepare status update modal
    function prepareOrderStatus(orderId, currentStatus) {
        document.getElementById('status_order_id').value = orderId;
        document.getElementById('new_status').value = currentStatus;
    }
    
    // Prepare payment status update modal
    function preparePaymentStatus(orderId, currentStatus) {
        document.getElementById('payment_order_id').value = orderId;
        document.getElementById('new_payment_status').value = currentStatus;
    }
    
    // Prepare delete modal
    function prepareDelete(orderId) {
        document.getElementById('delete_order_id').value = orderId;
    }
    
    // View order details 
    function viewOrder(orderId) {
        const detailsContainer = document.getElementById('orderDetails');
        const printBtn = document.getElementById('printOrderBtn');
        
        // Update print button href
        printBtn.href = `print_order.php?id=${orderId}`;
        
        // Show loading indicator
        detailsContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading order details...</p>
            </div>
        `;
        
        // Fetch order details
        fetch(`get_order_details.php?id=${orderId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                detailsContainer.innerHTML = html;
            })
            .catch(error => {
                detailsContainer.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-circle text-danger fa-3x mb-3"></i>
                        <p>Error loading order details: ${error.message}</p>
                    </div>
                `;
            });
    }
    
    // Format currency function
    function formatCurrency(amount) {
        return '₱' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
</script>
 
</body>
</html>