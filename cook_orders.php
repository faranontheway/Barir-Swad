<?php
session_start();
require 'dbconnect.php';

// Check if cook is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Cook') {
    header("Location: login.php");
    exit();
}

// Function to get status class for orders
function getStatusClass($status) {
    switch ($status) {
        case 'Pending': return 'status-pending';
        case 'Accepted': return 'status-accepted';
        case 'On the way': return 'status-on-the-way';
        case 'Delivered': return 'status-delivered';
        case 'Cancelled': return 'status-cancelled';
        default: return '';
    }
}

$cook_id = $_SESSION['user_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    if ($new_status !== 'Delivered') {
        // Verify order belongs to cook
        $check_sql = "
            SELECT COUNT(*) as count 
            FROM orders o
            INNER JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
            INNER JOIN user_cooks_meal ucm ON ohm.M_ID = ucm.Meal_ID
            WHERE ucm.Cook_ID = ? AND o.OrderID = ?
        ";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $cook_id, $order_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            $update_sql = "UPDATE orders SET Status = ? WHERE OrderID = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();
            // Redirect to refresh
            header("Location: cook_orders.php?message=Status updated successfully");
            exit();
        }
    }
}

// Get stats
$stats_sql = "
    SELECT 
        COUNT(DISTINCT o.OrderID) AS total,
        SUM(CASE WHEN o.Status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN o.Status = 'Accepted' THEN 1 ELSE 0 END) AS accepted,
        SUM(CASE WHEN o.Status = 'On the way' THEN 1 ELSE 0 END) AS preparing,
        SUM(CASE WHEN o.Status = 'Delivered' THEN 1 ELSE 0 END) AS delivered,
        SUM(CASE WHEN o.Status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
    FROM orders o
    INNER JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
    INNER JOIN user_cooks_meal ucm ON ohm.M_ID = ucm.Meal_ID
    WHERE ucm.Cook_ID = ?
";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $cook_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get orders
$orders_sql = "
    SELECT DISTINCT o.OrderID, o.Date, o.Status, o.Cost, u.Name AS customer_name,
    GROUP_CONCAT(CONCAT(m.Name, ' x', ohm.Quantity) SEPARATOR ', ') AS meals
    FROM orders o
    INNER JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
    INNER JOIN user_cooks_meal ucm ON ohm.M_ID = ucm.Meal_ID
    INNER JOIN user u ON o.Customer_ID = u.U_ID
    INNER JOIN meal m ON ohm.M_ID = m.Meal_ID
    WHERE ucm.Cook_ID = ?
    GROUP BY o.OrderID
    ORDER BY o.Date DESC
";
$stmt = $conn->prepare($orders_sql);
$stmt->bind_param("i", $cook_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cook Orders - Barir Swad</title>
<link rel="stylesheet" href="cook_styles.css">
<link rel="stylesheet" href="cook_order_styles.css">
<link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
</head>
<body>
<header class="header">
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a class="btn" href="cook_dashboard.php">Dashboard</a>
            <a class="btn" href="cook_profile.php">My Profile</a>
            <a class="btn" href="complaint_dashboard.php">Complaint</a>
            <a href="logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="container">
    <!-- Success Message -->
    <?php if (isset($_GET['message'])): ?>
        <div class="success-message"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card"><h3>Total</h3><div class="number"><?= $stats['total'] ?: '—' ?></div></div>
        <div class="stat-card"><h3>Pending</h3><div class="number"><?= $stats['pending'] ?: '—' ?></div></div>
        <div class="stat-card"><h3>Accepted</h3><div class="number"><?= $stats['accepted'] ?: '—' ?></div></div>
        <div class="stat-card"><h3>On the way</h3><div class="number"><?= $stats['preparing'] ?: '—' ?></div></div>
        <div class="stat-card"><h3>Delivered</h3><div class="number"><?= $stats['delivered'] ?: '—' ?></div></div>
        <div class="stat-card"><h3>Cancelled</h3><div class="number"><?= $stats['cancelled'] ?: '—' ?></div></div>
    </div>

    <!-- Orders List -->
    <div class="orders-section">
        <h2>Your Orders</h2>

        <?php if ($orders->num_rows > 0): ?>
            <?php while ($row = $orders->fetch_assoc()): ?>
                <div class="complaint-card">
                    <h3>Order #<?= $row['OrderID'] ?></h3>
                    <div class="complaint-meta">
                        Date: <?= $row['Date'] ? date("d M Y", strtotime($row['Date'])) : 'N/A' ?> | 
                        Customer: <?= htmlspecialchars($row['customer_name']) ?> | 
                        Total: ৳<?= number_format($row['Cost'], 2) ?>
                    </div>
                    <p>Meals: <?= htmlspecialchars($row['meals']) ?></p>
                    
                    <!-- Status badge -->
                    <?php 
                    $status_class = getStatusClass($row['Status']);
                    ?>
                    <span class="status-badge <?= $status_class ?>"><?= $row['Status'] ?></span>

                    <?php if ($row['Status'] !== 'Delivered' && $row['Status'] !== 'Cancelled'): ?>
                        <!-- Status dropdown -->
                        <form action="cook_update_order_status.php" method="POST" class="update-form">
                            <input type="hidden" name="order_id" value="<?= $row['OrderID'] ?>">
                            <select name="status" onchange="this.form.submit()">
                                <?php
                                $statuses = ['Pending', 'Accepted', 'On the way', 'Delivered', 'Cancelled'];
                                foreach ($statuses as $st) {
                                    $selected = ($row['Status'] == $st) ? 'selected' : '';
                                    $disabled = ($st === 'Delivered') ? 'disabled' : '';
                                    echo "<option value=\"$st\" $selected $disabled>$st</option>";
                                }
                                ?>
                            </select>
                        </form>
                    <?php else: ?>
                        <p style="color: #666; font-style: italic;">Status update not available for <?= $row['Status'] ?> orders.</p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-data">😶 No orders yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>