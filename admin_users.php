<?php
session_start();
require 'dbconnect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Handle user deletion
if ($_POST && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    $sql = "DELETE FROM user WHERE U_ID = ? AND Type IN ('Customer', 'Cook')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $message = "User deleted successfully!";
    } else {
        $message = "Error deleting user.";
    }
}

// Handle user type update
if ($_POST && isset($_POST['update_user_type'])) {
    $user_id = $_POST['user_id'];
    $new_type = $_POST['user_type'];
    
    $sql = "UPDATE user SET Type = ? WHERE U_ID = ? AND Type IN ('Customer', 'Cook')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_type, $user_id);
    
    if ($stmt->execute()) {
        $message = "User type updated successfully!";
    } else {
        $message = "Error updating user type.";
    }
}

// Get all users (customers and cooks) with their phone numbers
$sql = "
    SELECT 
        u.U_ID, 
        u.Name, 
        u.Email, 
        u.Type, 
        u.Address, 
        u.Exp_Years,
        GROUP_CONCAT(up.Phone_No SEPARATOR ', ') as Phone_Numbers
    FROM user u
    LEFT JOIN user_phone_no up ON u.U_ID = up.User_ID
    WHERE u.Type IN ('Customer', 'Cook')
    GROUP BY u.U_ID, u.Name, u.Email, u.Type, u.Address, u.Exp_Years
    ORDER BY u.Type, u.Name
";

$users_result = $conn->query($sql);

// Get user statistics
$customer_count = $conn->query("SELECT COUNT(*) as count FROM user WHERE Type = 'Customer'")->fetch_assoc()['count'];
$cook_count = $conn->query("SELECT COUNT(*) as count FROM user WHERE Type = 'Cook'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="admin-header">
        <div class="admin-nav">
            <h1>👥 Barir Swad - User Management</h1>
            <nav class="nav-links">
                <a href="admin_dash.php">Dashboard</a>
                <a href="admin_orders.php">Orders</a>
                <a href="admin_users.php" class="active">Users</a>
                <a href="admin_meals.php">Meals</a>
                <a href="admin_complaints.php">Complaints</a>
                <a href="admin_logout.php" class="logout-btn">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2>👥 User Management</h2>
        
        <div class="stats-cards">
            <div class="stat-card">
                <h3>Total Customers</h3>
                <div class="number"><?= $customer_count ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Cooks</h3>
                <div class="number"><?= $cook_count ?></div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="users-table">
            <?php if ($users_result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Experience</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= $user['U_ID'] ?></strong></td>
                                <td><?= htmlspecialchars($user['Name']) ?></td>
                                <td><?= htmlspecialchars($user['Email']) ?></td>
                                <td>
                                    <span class="user-type <?= strtolower($user['Type']) ?>">
                                        <?= $user['Type'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($user['Address']) ?></td>
                                <td>
                                    <?= $user['Phone_Numbers'] ? htmlspecialchars($user['Phone_Numbers']) : 'N/A' ?>
                                </td>
                                <td>
                                    <?php if ($user['Type'] == 'Cook'): ?>
                                        <span class="exp-badge"><?= $user['Exp_Years'] ?> years</span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" class="type-form">
                                            <input type="hidden" name="user_id" value="<?= $user['U_ID'] ?>">
                                            <select name="user_type" class="type-select" onchange="this.form.submit()">
                                                <option value="">Change Type</option>
                                                <option value="Customer" <?= $user['Type'] == 'Customer' ? 'selected' : '' ?>>Customer</option>
                                                <option value="Cook" <?= $user['Type'] == 'Cook' ? 'selected' : '' ?>>Cook</option>
                                            </select>
                                            <input type="hidden" name="update_user_type" value="1">
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            <input type="hidden" name="user_id" value="<?= $user['U_ID'] ?>">
                                            <button type="submit" name="delete_user" class="btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 40px; text-align: center;">
                    <h3>No users found</h3>
                    <p>Users will appear here once they register.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>