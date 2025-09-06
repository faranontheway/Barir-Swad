<?php
session_start();
require 'dbconnect.php';

// Check if cook is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Cook') {
    header("Location: login.php");
    exit();
}

$cook_id = $_SESSION['user_id'];
$cook_name = $_SESSION['user_name'];
$message = '';

// Handle order acceptance
if ($_POST && isset($_POST['accept_order'])) {
    $order_id = $_POST['order_id'];
    
    $conn->begin_transaction();
    try {
        // Check if order is still available
        $check = $conn->query("SELECT Status, Customer_ID FROM orders WHERE OrderID = $order_id AND Status = 'Pending'");
        
        if ($check->num_rows > 0) {
            $order_data = $check->fetch_assoc();
            $customer_id = $order_data['Customer_ID'];
            
            // Update order to accepted
            $conn->query("UPDATE orders SET Status = 'Accepted' WHERE OrderID = $order_id");
            
            // Close all cook notifications for this order
            $conn->query("UPDATE complaint_support SET Status = 'Closed' 
                         WHERE Description = 'NEW_ORDER' 
                         AND JSON_EXTRACT(Messages, '$.order_id') = '$order_id'");
            
            // Create assignment record
            $assign_id = rand(1000, 99999);
            $assign_data = json_encode([
                'order_id' => $order_id, 
                'cook_id' => $cook_id, 
                'assigned_date' => date('Y-m-d H:i:s')
            ]);
            $conn->query("INSERT INTO complaint_support (User_ID, Complaint_ID, Description, Status, Submitted_Date, Messages) 
                         VALUES ($cook_id, $assign_id, 'ORDER_ASSIGNMENT', 'In Progress', CURDATE(), '$assign_data')");
            
            // Notify customer
            $customer_notification_id = rand(1000, 99999);
            $customer_data = json_encode([
                'title' => 'Order Accepted!',
                'message' => "Cook $cook_name has accepted your order #$order_id and will start preparing it soon.",
                'related_id' => $order_id,
                'type' => 'notification'
            ]);
            $conn->query("INSERT INTO complaint_support (User_ID, Complaint_ID, Description, Status, Submitted_Date, Messages) 
                         VALUES ($customer_id, $customer_notification_id, 'NOTIFICATION: Order Accepted!', 'Open', CURDATE(), '$customer_data')");
            
            $message = "Order #$order_id accepted successfully!";
        } else {
            $message = "Order is no longer available.";
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

// Handle order completion
if ($_POST && isset($_POST['complete_order'])) {
    $order_id = $_POST['order_id'];
    
    // Update order status
    $conn->query("UPDATE orders SET Status = 'On the way' WHERE OrderID = $order_id");
    
    // Notify admin
    $admin_id = 101; // Your admin ID
    $admin_complaint_id = rand(1000, 99999);
    $admin_data = json_encode([
        'order_id' => $order_id,
        'cook_id' => $cook_id,
        'cook_name' => $cook_name,
        'completed_date' => date('Y-m-d H:i:s')
    ]);
    $conn->query("INSERT INTO complaint_support (User_ID, Complaint_ID, Description, Status, Submitted_Date, Messages) 
                 VALUES ($admin_id, $admin_complaint_id, 'ORDER_COMPLETED', 'Open', CURDATE(), '$admin_data')");
    
    $message = "Order #$order_id marked as completed! Admin has been notified for delivery.";
}

// Get available orders for this cook
$available_orders = $conn->query("
    SELECT DISTINCT o.*, u.Name as customer_name,
           GROUP_CONCAT(m.Name, ' x', ohm.Quantity SEPARATOR ', ') as meals
    FROM orders o
    JOIN user u ON o.Customer_ID = u.U_ID
    JOIN complaint_support cs ON JSON_EXTRACT(cs.Messages, '$.order_id') = o.OrderID
    LEFT JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
    LEFT JOIN meal m ON ohm.M_ID = m.Meal_ID
    WHERE cs.User_ID = $cook_id 
    AND cs.Description = 'NEW_ORDER' 
    AND cs.Status = 'Open' 
    AND o.Status = 'Pending'
    GROUP BY o.OrderID
    ORDER BY o.Date DESC, o.OrderID DESC
");

// Get accepted orders for this cook
$my_orders = $conn->query("
    SELECT DISTINCT o.*, u.Name as customer_name, u.Address as customer_address,
           GROUP_CONCAT(m.Name, ' x', ohm.Quantity SEPARATOR ', ') as meals
    FROM orders o
    JOIN user u ON o.Customer_ID = u.U_ID
    JOIN complaint_support cs ON JSON_EXTRACT(cs.Messages, '$.order_id') = o.OrderID 
                              AND JSON_EXTRACT(cs.Messages, '$.cook_id') = $cook_id
    LEFT JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
    LEFT JOIN meal m ON ohm.M_ID = m.Meal_ID
    WHERE cs.Description = 'ORDER_ASSIGNMENT' 
    AND o.Status IN ('Accepted', 'On the way')
    GROUP BY o.OrderID
    ORDER BY o.Date DESC, o.OrderID DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cook Orders - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo">Barir Swad</div>
            <div class="nav-links">
                <a href="cook_dashboard.php">Dashboard</a>
                <a href="cook_orders.php" style="background: #ff6b35; color: white;">Orders</a>
                <a href="cook_profile.php">Profile</a>
                <a href="admin_logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Cook Orders Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($cook_name) ?>!</p>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Available Orders</h2>
            <?php if ($available_orders->num_rows > 0): ?>
                <?php while ($order = $available_orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?= $order['OrderID'] ?></div>
                            <div class="status pending"><?= $order['Status'] ?></div>
                        </div>
                        
                        <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                        <p><strong>Total Cost:</strong> ৳<?= number_format($order['Cost'], 2) ?></p>
                        <p><strong>Meals:</strong> <?= htmlspecialchars($order['meals']) ?></p>
                        <p><strong>Order Date:</strong> <?= date('M j, Y', strtotime($order['Date'])) ?></p>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?= $order['OrderID'] ?>">
                            <button type="submit" name="accept_order" class="btn">Accept This Order</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-orders">
                    <h3>No New Orders Available</h3>
                    <p>Check back later for new orders from customers.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>My Accepted Orders</h2>
            <?php if ($my_orders->num_rows > 0): ?>
                <?php while ($order = $my_orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?= $order['OrderID'] ?></div>
                            <div class="status <?= strtolower(str_replace(' ', '-', $order['Status'])) ?>"><?= $order['Status'] ?></div>
                        </div>
                        
                        <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($order['customer_address']) ?></p>
                        <p><strong>Total Cost:</strong> ৳<?= number_format($order['Cost'], 2) ?></p>
                        <p><strong>Meals:</strong> <?= htmlspecialchars($order['meals']) ?></p>
                        <p><strong>Order Date:</strong> <?= date('M j, Y', strtotime($order['Date'])) ?></p>
                        
                        <?php if ($order['Status'] == 'Accepted'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?= $order['OrderID'] ?>">
                                <button type="submit" name="complete_order" class="btn btn-complete">Mark as Completed & Ready for Delivery</button>
                            </form>
                            <p><small style="color: #666;">Click when you have finished cooking and the food is ready for pickup/delivery.</small></p>
                        <?php elseif ($order['Status'] == 'On the way'): ?>
                            <p style="color: #17a2b8; font-weight: bold;">✓ Order completed! Admin has been notified for delivery.</p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-orders">
                    <h3>No Accepted Orders</h3>
                    <p>Accept orders from the available orders section above.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <br>
        <a href="cook_dashboard.php" style="color: #007bff; text-decoration: none;">← Back to Cook Dashboard</a>
    </div>
</body>
</html>