<?php
session_start();
require 'dbconnect.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Customer') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $phone = trim($_POST['phone']);

    // Validate current password
    $check_sql = "SELECT Password FROM user WHERE U_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $customer_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user_data = $result->fetch_assoc();

    if ($current_password !== $user_data['Password']) {
        $error = "Current password is incorrect.";
    } else {
        // Check if email is unique (excluding current user)
        $email_check_sql = "SELECT U_ID FROM user WHERE Email = ? AND U_ID != ?";
        $email_check_stmt = $conn->prepare($email_check_sql);
        $email_check_stmt->bind_param("si", $email, $customer_id);
        $email_check_stmt->execute();
        $email_result = $email_check_stmt->get_result();

        if ($email_result->num_rows > 0) {
            $error = "Email already exists. Please choose a different email.";
        } else {
            $conn->begin_transaction();
            try {
                // Update user information
                $password_to_use = !empty($new_password) ? $new_password : $current_password;
                
                $update_sql = "UPDATE user SET Name = ?, Email = ?, Address = ?, Password = ? WHERE U_ID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssssi", $name, $email, $address, $password_to_use, $customer_id);
                $update_stmt->execute();

                // Update phone number
                if (!empty($phone)) {
                    // Remove existing phone
                    $delete_phone_sql = "DELETE FROM user_phone_no WHERE User_ID = ?";
                    $delete_phone_stmt = $conn->prepare($delete_phone_sql);
                    $delete_phone_stmt->bind_param("i", $customer_id);
                    $delete_phone_stmt->execute();

                    // Insert new phone
                    $phone_sql = "INSERT INTO user_phone_no (User_ID, Phone_No) VALUES (?, ?)";
                    $phone_stmt = $conn->prepare($phone_sql);
                    $phone_stmt->bind_param("is", $customer_id, $phone);
                    $phone_stmt->execute();
                }

                $conn->commit();
                
                // Update session
                $_SESSION['user_name'] = $name;
                
                $message = "Profile updated successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error updating profile: " . $e->getMessage();
            }
        }
    }
}

// Get current user data
$user_sql = "SELECT u.*, up.Phone_No FROM user u LEFT JOIN user_phone_no up ON u.U_ID = up.User_ID WHERE u.U_ID = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $customer_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Get customer statistics for profile overview
$stats_sql = "SELECT 
    COUNT(DISTINCT o.OrderID) as total_orders,
    COUNT(DISTINCT cs.Catering_ID) as total_catering,
    COUNT(DISTINCT r.Review_ID) as total_reviews,
    COALESCE(SUM(CASE WHEN o.Status = 'Delivered' THEN o.Cost END), 0) as total_spent
FROM user u
LEFT JOIN orders o ON u.U_ID = o.Customer_ID
LEFT JOIN catering_services cs ON u.U_ID = cs.Customer_ID
LEFT JOIN customer_rates_cooks r ON u.U_ID = r.CustomerID
WHERE u.U_ID = ?";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $customer_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="nav">
            <div class="logo">Barir Swad</div>
            <nav class="nav-links">
                <a href="customer_dashboard.php">Dashboard</a>
                <a href="meal.php">Browse Meals</a>
                <a href="view_cart.php">Cart</a>
                <a href="customer_catering.php">Catering Services</a>
                <a href="review.php">Write Review</a>
                <span>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
                <a href="admin_logout.php" class="btn logout">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="profile-header">
            <h1><?= htmlspecialchars($user_data['Name']) ?></h1>
            <p>Customer since <?= date('F Y', strtotime('2025-01-01')) ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="profile-sections">
            <div class="stats-section">
                <h3 style="margin-bottom: 20px; color: #333;">My Activity</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= $stats['total_orders'] ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $stats['total_catering'] ?></div>
                        <div class="stat-label">Catering Bookings</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $stats['total_reviews'] ?></div>
                        <div class="stat-label">Reviews Written</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">৳<?= number_format($stats['total_spent'], 0) ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                </div>
            </div>

            <div class="profile-form">
                <h3 style="margin-bottom: 20px; color: #333;">Edit Profile</h3>
                
                <form method="POST">
                    <div class="form-section">
                        <div class="section-title">Basic Information</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user_data['Name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['Email']) ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" required><?= htmlspecialchars($user_data['Address']) ?></textarea>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user_data['Phone_No'] ?? '') ?>" placeholder="01XXXXXXXXX">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Security</div>
                        <div class="password-section">
                            <div class="password-note">
                                Enter your current password to save changes. Leave new password blank to keep current password.
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                <div class="form-group">
                                    <label for="new_password">New Password (Optional)</label>
                                    <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="submit-btn">
                        Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>