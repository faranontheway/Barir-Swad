<?php
session_start();
require 'dbconnect.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Customer') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['user_name'];

$message = '';

// Handle booking cancellation
if ($_POST && isset($_POST['cancel_booking'])) {
    $catering_id = $_POST['catering_id'];
    
    // Only allow cancellation if status is Pending or Confirmed
    $check_sql = "SELECT Status FROM catering_services WHERE Catering_ID = ? AND Customer_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $catering_id, $customer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $booking = $check_result->fetch_assoc();
        if (in_array($booking['Status'], ['Pending', 'Confirmed'])) {
            $cancel_sql = "UPDATE catering_services SET Status = 'Cancelled' WHERE Catering_ID = ? AND Customer_ID = ?";
            $cancel_stmt = $conn->prepare($cancel_sql);
            $cancel_stmt->bind_param("ii", $catering_id, $customer_id);
            
            if ($cancel_stmt->execute()) {
                $message = "Booking cancelled successfully.";
            } else {
                $message = "Error cancelling booking.";
            }
        } else {
            $message = "Cannot cancel booking with status: " . $booking['Status'];
        }
    }
}

// Get customer's catering bookings
$catering_sql = "
    SELECT cs.*, 
           COUNT(chm.Meal_ID) as assigned_meals_count,
           SUM(chm.Total_Price) as calculated_cost
    FROM catering_services cs
    LEFT JOIN catering_has_meals chm ON cs.Catering_ID = chm.Catering_ID
    WHERE cs.Customer_ID = ?
    GROUP BY cs.Catering_ID
    ORDER BY cs.Event_Date DESC, cs.Created_Date DESC
";

$stmt = $conn->prepare($catering_sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$catering_bookings = $stmt->get_result();

// Get customer catering statistics
$stats = [];

// Total bookings
$result = $conn->query("SELECT COUNT(*) as count FROM catering_services WHERE Customer_ID = $customer_id");
$stats['total_bookings'] = $result->fetch_assoc()['count'];

// Upcoming events
$result = $conn->query("SELECT COUNT(*) as count FROM catering_services WHERE Customer_ID = $customer_id AND Event_Date >= CURDATE() AND Status NOT IN ('Cancelled', 'Completed')");
$stats['upcoming_events'] = $result->fetch_assoc()['count'];

// Completed events
$result = $conn->query("SELECT COUNT(*) as count FROM catering_services WHERE Customer_ID = $customer_id AND Status = 'Completed'");
$stats['completed_events'] = $result->fetch_assoc()['count'];

// Total spent on catering
$result = $conn->query("SELECT SUM(Total_Cost) as total FROM catering_services WHERE Customer_ID = $customer_id AND Status = 'Completed'");
$stats['total_spent'] = $result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Catering Bookings - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="nav">
            <div class="logo">Barir Swad - Catering</div>
            <nav class="nav-links">
                <a href="customer_dashboard.php">Dashboard</a>
                <a href="meal.php">Browse Meals</a>
                <a href="catering.php">Book Catering</a>
                <a href="customer_catering.php">My Catering</a>
                <span>Welcome, <?= htmlspecialchars($customer_name) ?>!</span>
                <a href="admin_logout.php" class="btn logout">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>My Catering Bookings</h1>
            <p>Manage your catering service requests and track event planning</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="number"><?= $stats['total_bookings'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Upcoming Events</h3>
                <div class="number"><?= $stats['upcoming_events'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Completed Events</h3>
                <div class="number"><?= $stats['completed_events'] ?></div>
            </div>
            <div class="stat-card money">
                <h3>Total Spent</h3>
                <div class="number">৳<?= number_format($stats['total_spent'], 2) ?></div>
            </div>
        </div>
        
        <div class="quick-actions">
            <a href="catering.php" class="btn btn-success">Book New Catering Service</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'Error') !== false ? 'error' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="bookings-section">
            <div class="bookings-header">
                <h2>Your Catering Bookings</h2>
            </div>
            
            <?php if ($catering_bookings->num_rows > 0): ?>
                <?php while($booking = $catering_bookings->fetch_assoc()): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div>
                                <div class="booking-id">Booking #<?= $booking['Catering_ID'] ?></div>
                                <h3><?= htmlspecialchars($booking['Event_Name']) ?></h3>
                            </div>
                            <div class="booking-status <?= strtolower(str_replace(' ', '-', $booking['Status'])) ?>">
                                <?= $booking['Status'] ?>
                            </div>
                        </div>
                        
                        <div class="booking-details">
                            <div class="detail-item">
                                <div class="detail-label">Event Date</div>
                                <div class="detail-value"><?= date('F j, Y', strtotime($booking['Event_Date'])) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Event Time</div>
                                <div class="detail-value"><?= date('g:i A', strtotime($booking['Event_Time'])) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Number of People</div>
                                <div class="detail-value"><?= $booking['Number_of_People'] ?> guests</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Total Cost</div>
                                <div class="detail-value">৳<?= number_format($booking['Total_Cost'], 2) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Contact Person</div>
                                <div class="detail-value"><?= htmlspecialchars($booking['Contact_Person']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Meals Assigned</div>
                                <div class="detail-value"><?= $booking['assigned_meals_count'] ?> items</div>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Event Location</div>
                            <div class="detail-value"><?= htmlspecialchars($booking['Event_Location']) ?></div>
                        </div>
                        
                        <?php if ($booking['Special_Requirements']): ?>
                            <div class="detail-item" style="margin-top: 15px;">
                                <div class="detail-label">Special Requirements</div>
                                <div class="detail-value"><?= htmlspecialchars($booking['Special_Requirements']) ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['Total_Cost'] > 0): ?>
                            <div class="payment-info">
                                <h4>Payment Information</h4>
                                <div class="payment-row">
                                    <span>Total Cost:</span>
                                    <span><strong>৳<?= number_format($booking['Total_Cost'], 2) ?></strong></span>
                                </div>
                                <div class="payment-row">
                                    <span>Advance Paid:</span>
                                    <span>৳<?= number_format($booking['Advance_Payment'], 2) ?></span>
                                </div>
                                <div class="payment-row">
                                    <span>Remaining:</span>
                                    <span><strong>৳<?= number_format($booking['Total_Cost'] - $booking['Advance_Payment'], 2) ?></strong></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="booking-actions">
                            <?php 
                            $can_cancel = in_array($booking['Status'], ['Pending', 'Confirmed']);
                            $is_upcoming = strtotime($booking['Event_Date']) >= strtotime('today');
                            ?>
                            
                            <?php if ($can_cancel && $is_upcoming): ?>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to cancel this booking?')">
                                    <input type="hidden" name="catering_id" value="<?= $booking['Catering_ID'] ?>">
                                    <button type="submit" name="cancel_booking" class="btn btn-danger">
                                        Cancel Booking
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($booking['Status'] == 'Confirmed' && $booking['assigned_meals_count'] > 0): ?>
                                <button class="btn btn-primary" onclick="viewMealDetails(<?= $booking['Catering_ID'] ?>)">
                                    View Menu
                                </button>
                            <?php endif; ?>
                            
                            <small style="color: #666; margin-left: 10px;">
                                Booked on <?= date('M j, Y', strtotime($booking['Created_Date'])) ?>
                            </small>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <h3>No catering bookings yet</h3>
                    <p>Start planning your special event with our catering services!</p>
                    <a href="catering.php" class="btn btn-success" style="margin-top: 20px;">
                        Book Your First Catering Service
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>