<?php
session_start();
require 'dbconnect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Handle meal deletion
if ($_POST && isset($_POST['delete_meal'])) {
    $meal_id = $_POST['meal_id'];
    
    $sql = "DELETE FROM meal WHERE Meal_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $meal_id);
    
    if ($stmt->execute()) {
        $message = "Meal deleted successfully!";
    } else {
        $message = "Error deleting meal. It may be referenced in orders.";
    }
}

// Handle meal update
if ($_POST && isset($_POST['update_meal'])) {
    $meal_id = $_POST['meal_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $proportion = $_POST['proportion'];
    $price = $_POST['price'];
    $cuisine = $_POST['cuisine'];
    
    $sql = "UPDATE meal SET Name = ?, Description = ?, Proportion = ?, `Pricing` = ?, Cuisine = ? WHERE Meal_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdsi", $name, $description, $proportion, $price, $cuisine, $meal_id);
    
    if ($stmt->execute()) {
        $message = "Meal updated successfully!";
    } else {
        $message = "Error updating meal.";
    }
}

// Handle new meal addition
if ($_POST && isset($_POST['add_meal'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $proportion = $_POST['proportion'];
    $price = $_POST['price'];
    $cuisine = $_POST['cuisine'];
    
    $sql = "INSERT INTO meal (Name, Description, Proportion, `Pricing`, Cuisine) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssds", $name, $description, $proportion, $price, $cuisine);
    
    if ($stmt->execute()) {
        $message = "Meal added successfully!";
    } else {
        $message = "Error adding meal.";
    }
}

// Get all meals
$sql = "SELECT * FROM meal ORDER BY Cuisine, Name";
$meals_result = $conn->query($sql);

// Get meal statistics by cuisine
$cuisine_stats = $conn->query("
    SELECT Cuisine, COUNT(*) as count, AVG(`Pricing`) as avg_price 
    FROM meal 
    GROUP BY Cuisine 
    ORDER BY count DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Management - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="admin-header">
        <div class="admin-nav">
            <h1>🍽️ Barir Swad - Meal Management</h1>
            <nav class="nav-links">
                <a href="admin_dash.php">Dashboard</a>
                <a href="admin_orders.php">Orders</a>
                <a href="admin_users.php">Users</a>
                <a href="admin_meals.php" class="active">Meals</a>
                <a href="admin_complaints.php">Complaints</a>
                <a href="admin_logout.php" class="logout-btn">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2>🍽️ Meal Management</h2>
        
        <div class="stats-cards">
            <?php if ($cuisine_stats->num_rows > 0): ?>
                <?php while($stat = $cuisine_stats->fetch_assoc()): ?>
                    <div class="stat-card">
                        <h3><?= htmlspecialchars($stat['Cuisine']) ?></h3>
                        <p><strong><?= $stat['count'] ?></strong> meals</p>
                        <p>Avg Price: <strong>৳<?= number_format($stat['avg_price'], 2) ?></strong></p>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Add New Meal Form -->
        <div class="add-meal-section">
            <h3>➕ Add New Meal</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Meal Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="cuisine">Cuisine</label>
                        <select id="cuisine" name="cuisine" required>
                            <option value="">Select Cuisine</option>
                            <option value="Bengali">Bengali</option>
                            <option value="Indian">Indian</option>
                            <option value="Chinese">Chinese</option>
                            <option value="Korean">Korean</option>
                            <option value="Burmese">Burmese</option>
                            <option value="Italian">Italian</option>
                            <option value="Continental">Continental</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="price">Price (৳)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="proportion">Proportion</label>
                        <input type="text" id="proportion" name="proportion" value="1:1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <button type="submit" name="add_meal" class="btn-success btn-sm" style="padding: 10px 20px; font-size: 14px;">Add Meal</button>
            </form>
        </div>

        <!-- Meals Table -->
        <div class="meals-table">
            <?php if ($meals_result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Proportion</th>
                            <th>Price</th>
                            <th>Cuisine</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $meals_result = $conn->query("SELECT * FROM meal ORDER BY Cuisine, Name");
                        while($meal = $meals_result->fetch_assoc()): 
                        ?>
                            <tr id="row-<?= $meal['Meal_ID'] ?>">
                                <td><strong><?= $meal['Meal_ID'] ?></strong></td>
                                <td class="meal-name"><?= htmlspecialchars($meal['Name']) ?></td>
                                <td class="meal-desc"><?= htmlspecialchars($meal['Description']) ?></td>
                                <td class="meal-prop"><?= htmlspecialchars($meal['Proportion']) ?></td>
                                <td class="price-cell meal-price">৳<?= number_format($meal['Pricing'], 2) ?></td>
                                <td>
                                    <span class="cuisine-badge <?= strtolower($meal['Cuisine']) ?>">
                                        <?= $meal['Cuisine'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="editMeal(<?= $meal['Meal_ID'] ?>)" class="btn-sm btn-primary">Edit</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this meal?')">
                                            <input type="hidden" name="meal_id" value="<?= $meal['Meal_ID'] ?>">
                                            <button type="submit" name="delete_meal" class="btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 40px; text-align: center;">
                    <h3>No meals found</h3>
                    <p>Add your first meal using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function editMeal(mealId) {
            const row = document.getElementById('row-' + mealId);
            const name = row.querySelector('.meal-name').textContent;
            const desc = row.querySelector('.meal-desc').textContent;
            const prop = row.querySelector('.meal-prop').textContent;
            const price = row.querySelector('.meal-price').textContent.replace('৳', '').replace(',', '');
            
            // Create edit form
            row.classList.add('edit-row');
            row.innerHTML = `
                <form method="POST" style="display: contents;">
                    <input type="hidden" name="meal_id" value="${mealId}">
                    <td><strong>${mealId}</strong></td>
                    <td><input type="text" name="name" value="${name}" required></td>
                    <td><textarea name="description" required>${desc}</textarea></td>
                    <td><input type="text" name="proportion" value="${prop}" required></td>
                    <td><input type="number" name="price" value="${price}" step="0.01" required></td>
                    <td>
                        <select name="cuisine" required>
                            <option value="Bengali">Bengali</option>
                            <option value="Indian">Indian</option>
                            <option value="Chinese">Chinese</option>
                            <option value="Korean">Korean</option>
                            <option value="Burmese">Burmese</option>
                            <option value="Italian">Italian</option>
                            <option value="Continental">Continental</option>
                        </select>
                    </td>
                    <td>
                        <button type="submit" name="update_meal" class="btn-sm btn-success">Save</button>
                        <button type="button" onclick="location.reload()" class="btn-sm btn-danger">Cancel</button>
                    </td>
                </form>
            `;
        }
    </script>
</body>
</html>