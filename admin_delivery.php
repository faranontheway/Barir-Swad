<?php
session_start();
require 'dbconnect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Handle delivery confirmation
if ($_POST && isset($_POST['mark_delivered'])) {
    $order_id = $_POST['order_id'];
    
    // Update order status to delivered
    $conn->query("UPDATE orders SET Status = 'Delivered' WHERE OrderID = $order_id");
    
    // Close admin notification
    $conn->query("UPDATE complaint_support SET Status = 'Closed' 
                 WHERE Description = 'ORDER_COMPLETED' 
                 AND JSON_EXTRACT(Messages, '$.order_id') = '$order_id'");
    
    $message = "Order #$order_id marked as delivered successfully!";
}

// Get orders ready for delivery (simple query)
$ready_orders = $conn->query("
    SELECT o.*, u.Name as customer_name, u.Address as customer_address,
           GROUP_CONCAT(m.Name, ' x', ohm.Quantity SEPARATOR ', ') as meals
    FROM orders o
    JOIN user u ON o.Customer_ID = u.U_ID
    LEFT JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
    LEFT JOIN meal m ON ohm.M_ID = m.Meal_ID
    WHERE o.Status = 'On the way'
    GROUP BY o.OrderID
    ORDER BY o.Date DESC
");

// Get delivery statistics
$stats = [];
$stats['ready_for_delivery'] = $conn->query("SELECT COUNT(*) as count FROM orders WHERE Status = 'On the way'")->fetch_assoc()['count'];
$stats['delivered_today'] = $conn->query("SELECT COUNT(*) as count FROM orders WHERE Status = 'Delivered' AND Date = CURDATE()")->fetch_assoc()['count'];
$stats['total_delivered'] = $conn->query("SELECT COUNT(*) as count FROM orders WHERE Status = 'Delivered'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Management - Barir Swad Admin</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="admin-header">
        <nav class="admin-nav">
            <div class="logo">Barir Swad Admin</div>
            <div class="nav-links">
                <a href="admin_dash.php">Dashboard</a>
                <a href="admin_orders.php">Orders</a>
                <a href="admin_delivery.php" class="active">Delivery</a>
                <a href="admin_users.php">Users</a>
                <a href="admin_meals.php">Meals</a>
                <a href="admin_complaints.php">Complaints</a>
                <a href="admin_logout.php" class="logout-btn">Logout</a>
            </div>
        </nav>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h1>Delivery Management</h1>
            <p>Manage orders that are ready for delivery</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Ready for Delivery</h3>
                <div class="number"><?= $stats['ready_for_delivery'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Delivered Today</h3>
                <div class="number"><?= $stats['delivered_today'] ?></div>
            </div>
            <div class="stat-card money">
                <h3>Total Delivered</h3>
                <div class="number"><?= $stats['total_delivered'] ?></div>
            </div>
        </div>
        
        <div class="section-card">
            <h3>Orders Ready for Delivery</h3>
            
            <?php if ($ready_orders->num_rows > 0): ?>
                <?php while ($order = $ready_orders->fetch_assoc()): ?>
                    <div class="delivery-card">
                        <div class="delivery-header">
                            <div class="order-id">Order #<?= $order['OrderID'] ?></div>
                            <div class="priority">Ready for Delivery</div>
                        </div>
                        
                        <div class="customer-info">
                            <h4>Customer Information</h4>
                            <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($order['customer_address']) ?></p>
                            <p><strong>Order Total:</strong> ৳<?= number_format($order['Cost'], 2) ?></p>
                        </div>
                        
                        <p><strong>Meals:</strong> <?= htmlspecialchars($order['meals']) ?></p>
                        <p><strong>Order Date:</strong> <?= date('M j, Y', strtotime($order['Date'])) ?></p>
                        
                        <div class="quick-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?= $order['OrderID'] ?>">
                                <button type="submit" name="mark_delivered" class="btn btn-success" 
                                        onclick="return confirm('Confirm that order #<?= $order['OrderID'] ?> has been delivered to <?= htmlspecialchars($order['customer_name']) ?>?')">
                                    Mark as Delivered
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-deliveries">
                    <h3>No Orders Ready for Delivery</h3>
                    <p>Orders will appear here when cooks mark them as completed.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="quick-actions">
            <a href="admin_dash.php" class="btn btn-secondary">← Back to Admin Dashboard</a>
            <a href="admin_orders.php" class="btn btn-primary">View All Orders →</a>
        </div>
    </div>
</body>
</html>