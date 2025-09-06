<?php
session_start();
require 'dbconnect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Handle status update
if ($_POST && isset($_POST['update_status'])) {
    $catering_id = $_POST['catering_id'];
    $new_status = $_POST['status'];
    
    $sql = "UPDATE catering_services SET Status = ? WHERE Catering_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $catering_id);
    
    if ($stmt->execute()) {
        $message = "Catering status updated successfully!";
    } else {
        $message = "Error updating catering status.";
    }
}

// Handle cost update
if ($_POST && isset($_POST['update_cost'])) {
    $catering_id = $_POST['catering_id'];
    $new_cost = $_POST['total_cost'];
    
    $sql = "UPDATE catering_services SET Total_Cost = ? WHERE Catering_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $new_cost, $catering_id);
    
    if ($stmt->execute()) {
        $message = "Catering cost updated successfully!";
    } else {
        $message = "Error updating catering cost.";
    }
}

// Handle payment status update
if ($_POST && isset($_POST['update_payment'])) {
    $catering_id = $_POST['catering_id'];
    $advance_payment = $_POST['advance_payment'];
    $payment_status = $_POST['payment_status'];
    
    $sql = "UPDATE catering_services SET Advance_Payment = ?, Payment_Status = ? WHERE Catering_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dsi", $advance_payment, $payment_status, $catering_id);
    
    if ($stmt->execute()) {
        $message = "Payment information updated successfully!";
    } else {
        $message = "Error updating payment information.";
    }
}

// Handle catering deletion
if ($_POST && isset($_POST['delete_catering'])) {
    $catering_id = $_POST['catering_id'];
    
    $sql = "DELETE FROM catering_services WHERE Catering_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $catering_id);
    
    if ($stmt->execute()) {
        $message = "Catering service deleted successfully!";
    } else {
        $message = "Error deleting catering service.";
    }
}

// Get all catering services with customer details
$sql = "
    SELECT 
        cs.*,
        u.Name as Customer_Name,
        u.Email as Customer_Email,
        u.Address as Customer_Address
    FROM catering_services cs
    JOIN user u ON cs.Customer_ID = u.U_ID
    ORDER BY cs.Event_Date ASC, cs.Created_Date DESC
";

$catering_result = $conn->query($sql);

// Get catering statistics
$stats = [];
$stats['total_catering'] = $conn->query("SELECT COUNT(*) as count FROM catering_services")->fetch_assoc()['count'];
$stats['pending_catering'] = $conn->query("SELECT COUNT(*) as count FROM catering_services WHERE Status = 'Pending'")->fetch_assoc()['count'];
$stats['confirmed_catering'] = $conn->query("SELECT COUNT(*) as count FROM catering_services WHERE Status = 'Confirmed'")->fetch_assoc()['count'];
$stats['total_revenue'] = $conn->query("SELECT SUM(Total_Cost) as total FROM catering_services WHERE Status = 'Completed'")->fetch_assoc()['total'] ?? 0;
$stats['avg_people'] = $conn->query("SELECT AVG(Number_of_People) as avg FROM catering_services")->fetch_assoc()['avg'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catering Management - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="admin-header">
        <div class="admin-nav">
            <h1>Barir Swad - Catering Management</h1>
            <nav class="nav-links">
                <a href="admin_dash.php">Dashboard</a>
                <a href="admin_orders.php">Orders</a>
                <a href="admin_users.php">Users</a>
                <a href="admin_meals.php">Meals</a>
                <a href="admin_complaints.php">Complaints</a>
                <a href="admin_logout.php" class="logout-btn">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2>🎉 Catering Service Management</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="number"><?= $stats['total_catering'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Bookings</h3>
                <div class="number"><?= $stats['pending_catering'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Confirmed Bookings</h3>
                <div class="number"><?= $stats['confirmed_catering'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="number">৳<?= number_format($stats['total_revenue'], 0) ?></div>
            </div>
            <div class="stat-card">
                <h3>Avg. People</h3>
                <div class="number"><?= round($stats['avg_people']) ?></div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="catering-table">
            <?php if ($catering_result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Details</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Date & Time</th>
                            <th>People</th>
                            <th>Cost</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($catering = $catering_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $catering['Catering_ID'] ?></strong></td>
                                <td class="event-details">
                                    <strong><?= htmlspecialchars($catering['Event_Name']) ?></strong><br>
                                    <small><?= htmlspecialchars(substr($catering['Event_Location'], 0, 50)) ?>...</small>
                                    <?php if ($catering['Special_Requirements']): ?>
                                        <br><em style="color: #666;"><?= htmlspecialchars(substr($catering['Special_Requirements'], 0, 30)) ?>...</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($catering['Customer_Name']) ?><br>
                                    <small class="contact-info"><?= htmlspecialchars($catering['Customer_Email']) ?></small>
                                </td>
                                <td class="contact-info">
                                    <?= htmlspecialchars($catering['Contact_Person']) ?><br>
                                    <?= htmlspecialchars($catering['Contact_Phone']) ?>
                                </td>
                                <td>
                                    <?= date('M j, Y', strtotime($catering['Event_Date'])) ?><br>
                                    <small><?= date('g:i A', strtotime($catering['Event_Time'])) ?></small>
                                </td>
                                <td>
                                    <span class="people-count"><?= $catering['Number_of_People'] ?></span>
                                </td>
                                <td>
                                    <div class="cost-display">৳<?= number_format($catering['Total_Cost'], 2) ?></div>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="catering_id" value="<?= $catering['Catering_ID'] ?>">
                                        <input type="number" name="total_cost" value="<?= $catering['Total_Cost'] ?>" step="0.01" style="width: 80px;">
                                        <button type="submit" name="update_cost" class="btn-sm btn-warning">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="status <?= strtolower(str_replace(' ', '-', $catering['Status'])) ?>">
                                        <?= $catering['Status'] ?>
                                    </span>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="catering_id" value="<?= $catering['Catering_ID'] ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="">Change</option>
                                            <option value="Pending" <?= $catering['Status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Confirmed" <?= $catering['Status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="In Progress" <?= $catering['Status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="Completed" <?= $catering['Status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $catering['Status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <span class="payment-status <?= strtolower($catering['Payment_Status']) ?>">
                                        <?= $catering['Payment_Status'] ?>
                                    </span><br>
                                    <small>Advance: ৳<?= number_format($catering['Advance_Payment'], 2) ?></small>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="catering_id" value="<?= $catering['Catering_ID'] ?>">
                                        <input type="number" name="advance_payment" value="<?= $catering['Advance_Payment'] ?>" step="0.01" style="width: 70px;" placeholder="Advance">
                                        <select name="payment_status">
                                            <option value="Pending" <?= $catering['Payment_Status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Partial" <?= $catering['Payment_Status'] == 'Partial' ? 'selected' : '' ?>>Partial</option>
                                            <option value="Full" <?= $catering['Payment_Status'] == 'Full' ? 'selected' : '' ?>>Full</option>
                                        </select>
                                        <button type="submit" name="update_payment" class="btn-sm btn-success">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="catering_details.php?id=<?= $catering['Catering_ID'] ?>" class="btn-sm btn-primary">Details</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this catering booking?')">
                                            <input type="hidden" name="catering_id" value="<?= $catering['Catering_ID'] ?>">
                                            <button type="submit" name="delete_catering" class="btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 40px; text-align: center;">
                    <h3>No catering bookings found</h3>
                    <p>Catering bookings will appear here once customers start booking them.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>