<?php
session_start();
require 'dbconnect.php';

// Check if cook is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Cook') {
    header("Location: login.php");
    exit();
}

$cook_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $exp_years = intval($_POST['exp_years']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $phone = trim($_POST['phone']);

    // Validate current password
    $check_sql = "SELECT Password FROM user WHERE U_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $cook_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user_data = $result->fetch_assoc();

    if ($current_password !== $user_data['Password']) {
        $error = "Current password is incorrect.";
    } else {
        // Check if email is unique (excluding current user)
        $email_check_sql = "SELECT U_ID FROM user WHERE Email = ? AND U_ID != ?";
        $email_check_stmt = $conn->prepare($email_check_sql);
        $email_check_stmt->bind_param("si", $email, $cook_id);
        $email_check_stmt->execute();
        $email_result = $email_check_stmt->get_result();

        if ($email_result->num_rows > 0) {
            $error = "Email already exists. Please choose a different email.";
        } else {
            $conn->begin_transaction();
            try {
                // Update user information
                $password_to_use = !empty($new_password) ? $new_password : $current_password;
                
                $update_sql = "UPDATE user SET Name = ?, Email = ?, Address = ?, Exp_Years = ?, Password = ? WHERE U_ID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssisi", $name, $email, $address, $exp_years, $password_to_use, $cook_id);
                $update_stmt->execute();

                // Update phone number
                if (!empty($phone)) {
                    // Remove existing phone
                    $delete_phone_sql = "DELETE FROM user_phone_no WHERE User_ID = ?";
                    $delete_phone_stmt = $conn->prepare($delete_phone_sql);
                    $delete_phone_stmt->bind_param("i", $cook_id);
                    $delete_phone_stmt->execute();

                    // Insert new phone
                    $phone_sql = "INSERT INTO user_phone_no (User_ID, Phone_No) VALUES (?, ?)";
                    $phone_stmt = $conn->prepare($phone_sql);
                    $phone_stmt->bind_param("is", $cook_id, $phone);
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
$user_stmt->bind_param("i", $cook_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Get cook statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT ucm.Meal_ID) as total_meals,
    COUNT(DISTINCT o.OrderID) as total_orders,
    COUNT(DISTINCT r.Review_ID) as total_reviews,
    COALESCE(AVG(r.Rating), 0) as avg_rating,
    COALESCE(SUM(CASE WHEN o.Status = 'Delivered' THEN o.Cost END), 0) as total_earned
FROM user u
LEFT JOIN user_cooks_meal ucm ON u.U_ID = ucm.Cook_ID
LEFT JOIN orders_have_meal ohm ON ucm.Meal_ID = ohm.M_ID
LEFT JOIN orders o ON ohm.OrderID = o.OrderID 
LEFT JOIN customer_rates_cooks r ON u.U_ID = r.CookID AND r.Status = 'Active'
WHERE u.U_ID = ?";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $cook_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Barir Swad Cook</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .cook-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #953029, #BA5448);
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
        }
        
        .experience-badge {
            background: #953029;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        
        .profile-sections {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        .stats-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #FFF8EE;
            border-radius: 8px;
            border-left: 4px solid #953029;
        }
        
        .stat-label {
            font-weight: bold;
            color: #333;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #953029;
        }
        
        .profile-form {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #953029;
            padding-bottom: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #953029;
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .password-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .password-note {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .submit-btn {
            background: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: #218838;
        }
        
        .message, .error {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .quick-actions {
            margin-top: 20px;
        }
        
        .action-btn {
            display: block;
            background: #953029;
            color: white;
            padding: 12px;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            margin-bottom: 10px;
            transition: background 0.3s;
        }
        
        .action-btn:hover {
            background: #ce1e1e;
        }
        
        @media (max-width: 768px) {
            .profile-sections {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav">
            <div class="logo">🥘Barir Swad</div>
            <nav class="nav-links">
                <a href="cook_dashboard.php" class="btn">Dashboard</a>
                <a href="cook_orders.php" class="btn">Orders</a>
                <a href="cook_profile.php" class="btn" style="background: #ce1e1e;">Profile</a>
                <a href="admin_logout.php" class="btn logout">Logout</a>
                <a href="cook_notifications.php" class="btn">Notifications</a>
            </nav>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <div class="cook-avatar">
                <?= strtoupper(substr($user_data['Name'], 0, 1)) ?>
            </div>
            <h1><?= htmlspecialchars($user_data['Name']) ?></h1>
            <p>Professional Cook</p>
            <div class="experience-badge">
                <?= $user_data['Exp_Years'] ?> Years Experience
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="profile-sections">
            <div class="stats-section">
                <h3 style="margin-bottom: 20px; color: #333;">My Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">Total Meals</div>
                        <div class="stat-value"><?= $stats['total_meals'] ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Orders Completed</div>
                        <div class="stat-value"><?= $stats['total_orders'] ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Customer Reviews</div>
                        <div class="stat-value"><?= $stats['total_reviews'] ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Average Rating</div>
                        <div class="stat-value"><?= number_format($stats['avg_rating'], 1) ?>/5</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Total Earned</div>
                        <div class="stat-value">৳<?= number_format($stats['total_earned'], 0) ?></div>
                    </div>
                </div>

                <div class="quick-actions">
                    <h4 style="margin-bottom: 15px; color: #333;">Quick Actions</h4>
                    <a href="add_meal.php" class="action-btn">Add New Meal</a>
                    <a href="cook_notifications.php" class="action-btn">Check Orders</a>
                    <a href="cook_reviews.php" class="action-btn">View Reviews</a>
                    <a href="cook_dashboard.php" class="action-btn">Dashboard</a>
                </div>
            </div>

            <div class="profile-form">
                <h3 style="margin-bottom: 20px; color: #333;">Edit Profile</h3>
                
                <form method="POST">
                    <div class="form-section">
                        <div class="section-title">Professional Information</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user_data['Name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="exp_years">Years of Experience</label>
                                <input type="number" id="exp_years" name="exp_years" value="<?= $user_data['Exp_Years'] ?>" min="0" max="50" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['Email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user_data['Phone_No'] ?? '') ?>" placeholder="01XXXXXXXXX">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" required><?= htmlspecialchars($user_data['Address']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Security Settings</div>
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