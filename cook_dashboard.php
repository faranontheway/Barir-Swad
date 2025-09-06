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

// Get cook's meals
$meals_sql = "
    SELECT m.Meal_ID, m.Name, m.Description, m.Proportion, m.`Pricing`, m.Cuisine
    FROM meal m
    INNER JOIN user_cooks_meal ucm ON m.Meal_ID = ucm.Meal_ID
    WHERE ucm.Cook_ID = ?";
$stmt = $conn->prepare($meals_sql);
$stmt->bind_param("i", $cook_id);
$stmt->execute();
$meals = $stmt->get_result();

// Cook statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM user_cooks_meal WHERE Cook_ID = $cook_id");
$stats['total_meals'] = $result->fetch_assoc()['count'];

$result = $conn->query("
    SELECT COUNT(DISTINCT o.OrderID) as count
    FROM orders o
    INNER JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
    INNER JOIN user_cooks_meal ucm ON ohm.M_ID = ucm.Meal_ID
    WHERE ucm.Cook_ID = $cook_id
");
$stats['total_orders'] = $result->fetch_assoc()['count'];

$result = $conn->query("
    SELECT SUM(o.Cost) as total
    FROM orders o
    INNER JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
    INNER JOIN user_cooks_meal ucm ON ohm.M_ID = ucm.Meal_ID
    WHERE ucm.Cook_ID = $cook_id AND o.Status = 'Delivered'
");
$stats['total_earned'] = $result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cook Dashboard - Barir Swad</title>
<link rel="stylesheet" href="cook_styles.css">
<link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
</head>
<body>
<header class="header">
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a class="btn" href="cook_dashboard.php">Dashboard</a>
            <a class="btn" href="complaint_dashboard.php">Complaint</a>
            <a class="btn" href="cook_profile.php">Your Profile</a>
            <a class="btn" href="cook_orders.php">Orders</a>
            <a href="logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="welcome-section">
        <h1>Welcome back, <?= htmlspecialchars($cook_name) ?>!</h1>
        <p>Manage your meals and track orders.</p>
        <div class="quick-actions">
            <a href="add_meal.php" class="btn">Add New Meal</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Meals</h3>
            <div class="number"><?= $stats['total_meals'] ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="number"><?= $stats['total_orders'] ?></div>
        </div>
        <div class="stat-card money">
            <h3>Total Earned</h3>
            <div class="number">৳<?= number_format($stats['total_earned'],2) ?></div>
        </div>
    </div>

    <div class="meal-grid">
        <?php if($meals->num_rows > 0): ?>
            <?php while($meal = $meals->fetch_assoc()): ?>
                <div class="meal-card">
                    <h4><?= htmlspecialchars($meal['Name']) ?></h4>
                    <div class="meal-cuisine"><?= htmlspecialchars($meal['Cuisine']) ?></div>
                    <div class="meal-price">৳<?= number_format($meal['Pricing'],2) ?></div>
                    <p><?= htmlspecialchars($meal['Description']) ?></p>
                    <a href="edit_meal.php?meal_id=<?= $meal['Meal_ID'] ?>" class="btn-action">Edit</a>
                    <a href="delete_meal.php?meal_id=<?= $meal['Meal_ID'] ?>" 
                        class="btn-action btn-delete" 
                        onclick="return confirm('Are you sure you want to delete this meal?');">
                        Delete
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#666;">You have no meals yet. <a href="add_meal.php" class="btn-link">Add a meal now!</a></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
