<?php
session_start();
require 'dbconnect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get dashboard statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM user");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total customers
$result = $conn->query("SELECT COUNT(*) as count FROM user WHERE Type = 'Customer'");
$stats['total_customers'] = $result->fetch_assoc()['count'];

// Total cooks
$result = $conn->query("SELECT COUNT(*) as count FROM user WHERE Type = 'Cook'");
$stats['total_cooks'] = $result->fetch_assoc()['count'];

// Total meals
$result = $conn->query("SELECT COUNT(*) as count FROM meal");
$stats['total_meals'] = $result->fetch_assoc()['count'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $result->fetch_assoc()['count'];

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE Status = 'Pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Total complaints
$result = $conn->query("SELECT COUNT(*) as count FROM complaint_support");
$stats['total_complaints'] = $result->fetch_assoc()['count'];

// Open complaints
$result = $conn->query("SELECT COUNT(*) as count FROM complaint_support WHERE Status = 'Open'");
$stats['open_complaints'] = $result->fetch_assoc()['count'];

// Recent orders
$recent_orders = $conn->query("
    SELECT o.OrderID, u.Name as Customer, o.Cost, o.Status, o.Date 
    FROM orders o 
    JOIN user u ON o.Customer_ID = u.U_ID 
    ORDER BY o.Date DESC 
    LIMIT 50
");

// Recent users
$recent_users = $conn->query("
    SELECT Name, Email, Type, Address 
    FROM user 
    WHERE Type IN ('Customer', 'Cook') 
    ORDER BY U_ID DESC 
    LIMIT 50
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
    
</head>
<body>
    <header class="admin-header">
        <div class="admin-nav">
            <h1> Barir Swad - Admin Panel</h1>
            <nav class="nav-links">                
                <a href="admin_users.php">Users</a>
                <a href="admin_orders.php">Orders</a>
                <a href="admin_delivery.php">Delievery Management</a>
                <a href="admin_meals.php">Meals</a>
                <a href="admin_catering.php">Catering</a>
                <a href="cook_ratings.php">Reviews</a>
                <a href="admin_complaint_dashboard.php">Complaints</a>
                <a href="admin_logout.php" class="logout-btn">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2>Welcome back <?= htmlspecialchars($_SESSION['admin_name']) ?>! </h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?= $stats['total_users'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Customers</h3>
                <div class="number"><?= $stats['total_customers'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Cooks</h3>
                <div class="number"><?= $stats['total_cooks'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Meals</h3>
                <div class="number"><?= $stats['total_meals'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="number"><?= $stats['total_orders'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <div class="number"><?= $stats['pending_orders'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Complaints</h3>
                <div class="number"><?= $stats['total_complaints'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Open Complaints</h3>
                <div class="number"><?= $stats['open_complaints'] ?></div>
            </div>
        </div>

        <div class="dashboard-sections">
            <div class="section-card">
                <h3> Recent Orders</h3>
                <?php if ($recent_orders->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Cost</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $order['OrderID'] ?></td>
                                    <td><?= htmlspecialchars($order['Customer']) ?></td>
                                    <td>৳<?= number_format($order['Cost'], 2) ?></td>
                                    <td><span class="status <?= strtolower($order['Status']) ?>"><?= $order['Status'] ?></span></td>
                                    <td><?= $order['Date'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No orders found.</p>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <h3>Recent Users</h3>
                <?php if ($recent_users->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['Name']) ?></td>
                                    <td><?= htmlspecialchars($user['Email']) ?></td>
                                    <td><?= $user['Type'] ?></td>
                                    <td><?= htmlspecialchars($user['Address']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>