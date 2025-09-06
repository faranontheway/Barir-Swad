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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $proportion = trim($_POST['proportion']);
    $price = trim($_POST['price']);
    $cuisine = trim($_POST['cuisine']);

    if (!preg_match('/^\d+:\d+$/', $proportion)) {
        $message = "Proportion must be in the format num:num (e.g., 1:1)";
    } elseif (!is_numeric($price)) {
        $message = "Price must be a number";
    } else {
        $stmt = $conn->prepare("INSERT INTO meal (Name, Description, Proportion, `Pricing`, Cuisine) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssds", $name, $description, $proportion, $price, $cuisine);

        if ($stmt->execute()) {
            $meal_id = $stmt->insert_id;
            $stmt2 = $conn->prepare("INSERT INTO user_cooks_meal (Cook_ID, Meal_ID) VALUES (?, ?)");
            $stmt2->bind_param("ii", $cook_id, $meal_id);
            $stmt2->execute();
            $message = "Meal added successfully!";
        } else {
            $message = "Error adding meal: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Meal - Barir Swad</title>
<link rel="stylesheet" href="cook_styles.css">
<link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
</head>
<body>
<header class="header">
    <!-- NAVBAR -->
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a href="cook_dashboard.php" class="btn">Dashboard</a>
            <a href="admin_logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="form-container">
    <h1>Add New Meal</h1>
    <?php if($message) echo "<div class='message'>{$message}</div>"; ?>
    <!-- FORM -->
    <form method="POST">
        <label>Meal Name</label>
        <input type="text" name="name" required>

        <label>Description</label>
        <textarea name="description" required></textarea>

        <label>Proportion (e.g., 1:1)</label>
        <input type="text" name="proportion" pattern="\d+:\d+" title="Format must be num:num" required>

        <label>Price (৳)</label>
        <input type="number" name="price" step="0.01" min="0" required>

        <label>Cuisine</label>
        <input type="text" name="cuisine" required>

        <button type="submit">Add Meal</button>
    </form>
</div>
</body>
</html>
