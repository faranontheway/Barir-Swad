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
$old_meal_name = $meal['Name']; // Store old meal name for image deletion

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $proportion = trim($_POST['proportion']);
    $price = trim($_POST['price']);
    $cuisine = trim($_POST['cuisine']);

    // Check for unique meal name (excluding the current meal)
    $check_sql = "SELECT Meal_ID FROM meal WHERE Name = ? AND Meal_ID != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $name, $meal_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $message = "A meal with this name already exists. Please choose a different name.";
    } elseif (!preg_match('/^\d+:\d+$/', $proportion)) {
        $message = "Proportion must be in the format num:num (e.g., 1:1)";
    } elseif (!is_numeric($price) || $price <= 0) {
        $message = "Price must be a positive number";
    } else {
        // Handle image upload if a new image is provided
        $image_uploaded = false;
        $target_dir = "";
        $image_name = strtolower(str_replace(' ', '-', $name)) . '.jpg';
        $target_file = $target_dir . basename($image_name);

        if (!empty($_FILES["image"]["name"])) {
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));

            // Check if image file is an actual image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                $message = "File is not an image.";
                $uploadOk = 0;
            }

            // Check file size (limit to 5MB)
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
                // Delete the old image if it exists
                $old_image = $target_dir . strtolower(str_replace(' ', '-', $old_meal_name)) . '.jpg';
                if (file_exists($old_image) && $old_meal_name !== $name) {
                    unlink($old_image);
                }

                // Upload new image
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_uploaded = true;
                } else {
                    $message = "Sorry, there was an error uploading your image.";
                    $uploadOk = 0;
                }
            }
        }

        // Proceed with updating the meal if there are no issues
        if ($message === '') {
            $stmt = $conn->prepare("
                UPDATE meal 
                SET Name = ?, Description = ?, Proportion = ?, `Pricing` = ?, Cuisine = ?
                WHERE Meal_ID = ?
            ");
            $stmt->bind_param("sssdsi", $name, $description, $proportion, $price, $cuisine, $meal_id);

            if ($stmt->execute()) {
                $message = "Meal updated successfully!" . ($image_uploaded ? " Image updated as well!" : "");
                // Refresh the meal data after update
                $meal = ['Name' => $name, 'Description' => $description, 'Proportion' => $proportion, 'Pricing' => $price, 'Cuisine' => $cuisine];
                $old_meal_name = $name; // Update old meal name for subsequent form displays
            } else {
                $message = "Error updating meal: " . $conn->error;
                // Remove uploaded image if meal update fails
                if ($image_uploaded) {
                    unlink($target_file);
                }
            }
        }
    }
    $check_stmt->close();
}
$stmt->close();
$conn->close();
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
                <a href="logout.php" class="btn logout">Logout</a>
            </nav>
        </div>
    </header>

    <div class="form-container">
        <h1>Edit Meal</h1>
        <?php if ($message) echo "<div class='message'>{$message}</div>"; ?>
        <form method="POST" enctype="multipart/form-data">
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

            <label>Upload New Image (JPG/JPEG, max 5MB, optional)</label>
            <input type="file" name="image" accept="image/jpeg,image/jpg">

            <button type="submit">Update</button>
        </form>
    </div>
</body>
</html>