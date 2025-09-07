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

    // Check for unique meal name across all cooks
    $check_sql = "SELECT Meal_ID FROM meal WHERE Name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $message = "A meal with this name already exists. Please choose a different name.";
    } elseif (!preg_match('/^\d+:\d+$/', $proportion)) {
        $message = "Proportion must be in the format num:num (e.g., 1:1)";
    } elseif (!is_numeric($price)) {
        $message = "Price must be a number";
    } else {
        // Handle image upload
        $image_name = strtolower(str_replace(' ', '-', $name)) . '.jpg';
        $target_dir = "";
        $target_file = $target_dir . basename($image_name);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $message = "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size (e.g., limit to 5MB)
        if ($_FILES["image"]["size"] > 5000000) {
            $message = "Sorry, your file is too large (max 5MB).";
            $uploadOk = 0;
        }

        // Allow only certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "jpeg") {
            $message = "Sorry, only JPG/JPEG files are allowed.";
            $uploadOk = 0;
        }

        if ($uploadOk) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO meal (Name, Description, Proportion, `Pricing`, Cuisine) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssds", $name, $description, $proportion, $price, $cuisine);

                if ($stmt->execute()) {
                    $meal_id = $stmt->insert_id;
                    $stmt2 = $conn->prepare("INSERT INTO user_cooks_meal (Cook_ID, Meal_ID) VALUES (?, ?)");
                    $stmt2->bind_param("ii", $cook_id, $meal_id);
                    $stmt2->execute();
                    $message = "Meal and image added successfully!";
                } else {
                    $message = "Error adding meal: " . $conn->error;
                    unlink($target_file); // Remove uploaded image if meal insert fails
                }
            } else {
                $message = "Sorry, there was an error uploading your image.";
            }
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
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a href="cook_dashboard.php" class="btn">Dashboard</a>
            <a href="logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="form-container">
    <h1>Add New Meal</h1>
    <?php if ($message) echo "<div class='message'>{$message}</div>"; ?>
    <form method="POST" enctype="multipart/form-data">
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

        <label>Upload Image (JPG/JPEG, max 5MB)</label>
        <input type="file" name="image" accept="image/jpeg,image/jpg" required>

        <button type="submit">Add Meal</button>
    </form>
</div>
</body>
</html>