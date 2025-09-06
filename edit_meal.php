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

// Check if meal_id is provided
if (!isset($_GET['meal_id'])) {
    header("Location: cook_dashboard.php");
    exit();
}

$meal_id = intval($_GET['meal_id']);
$message = '';

// Fetch existing meal data
$stmt = $conn->prepare("
    SELECT m.Name, m.Description, m.Proportion, m.`Pricing`, m.Cuisine
    FROM meal m
    INNER JOIN user_cooks_meal ucm ON m.Meal_ID = ucm.Meal_ID
    WHERE m.Meal_ID = ? AND ucm.Cook_ID = ?
");
$stmt->bind_param("ii", $meal_id, $cook_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Meal not found or you don't have permission to edit it.");
}

$meal = $result->fetch_assoc();

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
        $stmt = $conn->prepare("
            UPDATE meal 
            SET Name = ?, Description = ?, Proportion = ?, `Pricing` = ?, Cuisine = ?
            WHERE Meal_ID = ?
        ");
        $stmt->bind_param("sssdsi", $name, $description, $proportion, $price, $cuisine, $meal_id);

        if ($stmt->execute()) {
            $message = "Meal updated successfully!";
            // Refresh the meal data after update
            $meal = ['Name'=>$name,'Description'=>$description,'Proportion'=>$proportion,'Pricing'=>$price,'Cuisine'=>$cuisine];
        } else {
            $message = "Error updating meal: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Meal - Barir Swad</title>
<link rel="stylesheet" href="cook_styles.css">
<link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
</head>
<body>
<header class="header">
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a href="cook_dashboard.php" class="btn">Dashboard</a>
            <a href="admin_logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="form-container">
    <h1>Edit Meal</h1>
    <?php if($message) echo "<div class='message'>{$message}</div>"; ?>
    <form method="POST">
        <label>Meal Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($meal['Name']) ?>" required>

        <label>Description</label>
        <textarea name="description" required><?= htmlspecialchars($meal['Description']) ?></textarea>

        <label>Proportion (e.g., 1:1)</label>
        <input type="text" name="proportion" pattern="\d+:\d+" title="Format must be num:num" value="<?= htmlspecialchars($meal['Proportion']) ?>" required>

        <label>Price (৳)</label>
        <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($meal['Pricing']) ?>" required>

        <label>Cuisine</label>
        <input type="text" name="cuisine" value="<?= htmlspecialchars($meal['Cuisine']) ?>" required>

        <button type="submit">Update</button>
    </form>
</div>
</body>
</html>
