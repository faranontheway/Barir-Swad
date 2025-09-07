<?php
session_start();
require 'dbconnect.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Customer') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['user_name'];

// Get customer's recent orders
$recent_orders_sql = "
    SELECT 
        o.OrderID, 
        o.Cost, 
        o.Status, 
        o.Date,
        GROUP_CONCAT(m.Name SEPARATOR ', ') as Meals
    FROM orders o
    LEFT JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
    LEFT JOIN meal m ON ohm.M_ID = m.Meal_ID
    WHERE o.Customer_ID = ?
    GROUP BY o.OrderID, o.Cost, o.Status, o.Date
    ORDER BY o.Date DESC
    LIMIT 20
";

$stmt = $conn->prepare($recent_orders_sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$recent_orders = $stmt->get_result();

// Get customer statistics
$stats = [];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE Customer_ID = $customer_id");
$stats['total_orders'] = $result->fetch_assoc()['count'];

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE Customer_ID = $customer_id AND Status = 'Pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Delivered orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE Customer_ID = $customer_id AND Status = 'Delivered'");
$stats['delivered_orders'] = $result->fetch_assoc()['count'];

// Total spent
$result = $conn->query("SELECT SUM(Cost) as total FROM orders WHERE Customer_ID = $customer_id AND Status = 'Delivered'");
$stats['total_spent'] = $result->fetch_assoc()['total'] ?? 0;

// Get popular meals for recommendations
$popular_meals = $conn->query("
    SELECT m.*, COUNT(ohm.M_ID) as order_count
    FROM meal m
    LEFT JOIN orders_have_meal ohm ON m.Meal_ID = ohm.M_ID
    GROUP BY m.Meal_ID, m.Name, m.Description, m.`Pricing`, m.Cuisine
    ORDER BY order_count DESC, m.Name
    LIMIT 50
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="nav">
            <div class="logo">Barir Swad</div>
            <nav class="nav-links">
                <a href="customer_dashboard.php">Dashboard</a>
                <a href="meal.php">Browse Meals</a>
                <a href="view_cart.php" class="cart-indicator">Cart</a>
                <a href="customer_catering.php">Catering Services</a>
                <a href="review.php">Write Review</a>
                <a href="complaint_dashboard.php">Complaints</a>
                <a href="customer_profile.php">Your Profile</a>
                    <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                        <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_logout.php" class="btn logout">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <h1>Welcome back, <?= htmlspecialchars($customer_name) ?>!</h1>
            <p>Ready to order some delicious homemade food?</p>
            <div class="quick-actions">
                <a href="meal.php" class="btn btn-primary">Browse Meals</a>
                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <a href="view_cart.php" class="btn btn-success">View Cart (<?= count($_SESSION['cart']) ?> items)</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="number"><?= $stats['total_orders'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <div class="number"><?= $stats['pending_orders'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Delivered Orders</h3>
                <div class="number"><?= $stats['delivered_orders'] ?></div>
            </div>
            <div class="stat-card money">
                <h3>Total Spent</h3>
                <div class="number">৳<?= number_format($stats['total_spent'], 2) ?></div>
            </div>
        </div>

        <div class="dashboard-sections">
            <div class="section-card">
                <h3>Recent Orders</h3>
                <?php if ($recent_orders->num_rows > 0): ?>
                    <?php while($order = $recent_orders->fetch_assoc()): ?>
                        <div class="order-item">
                            <div class="order-header">
                                <span class="order-id">Order #<?= $order['OrderID'] ?></span>
                                <span class="order-status <?= strtolower(str_replace(' ', '-', $order['Status'])) ?>">
                                    <?= $order['Status'] ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>৳<?= number_format($order['Cost'], 2) ?></strong><br>
                                    <small><?= date('M j, Y', strtotime($order['Date'])) ?></small>
                                </div>
                                <div style="text-align: right; max-width: 200px;">
                                    <small><?= $order['Meals'] ? htmlspecialchars($order['Meals']) : 'No meals listed' ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666;">No orders yet. <a href="meal.php" style="color: #667eea;">Start by browsing our meals!</a></p>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <h3>Popular Meals</h3>
                <?php if ($popular_meals->num_rows > 0): ?>
                    <div class="meal-grid">
                        <?php while($meal = $popular_meals->fetch_assoc()): ?>
                            <div class="meal-card">
                                <h4><?= htmlspecialchars($meal['Name']) ?></h4>
                                <div class="meal-cuisine"><?= htmlspecialchars($meal['Cuisine']) ?></div>
                                <div class="meal-price">৳<?= number_format($meal['Pricing'], 2) ?></div>
                                <a href="add_to_cart.php?meal_id=<?= $meal['Meal_ID'] ?>" class="btn-order">Add to Cart</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="meal.php" style="color: #667eea; text-decoration: none;">View All Meals →</a>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #666;">No meals available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>