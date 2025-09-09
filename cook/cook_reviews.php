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

// Fetch summary from view
$summary_sql = "SELECT * FROM cook_ratings_summary WHERE Cook_ID = ?";
$summary_stmt = $conn->prepare($summary_sql);
$summary_stmt->bind_param("i", $cook_id);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

// Fetch individual reviews
$reviews_sql = "
    SELECT r.*, u.Name AS Customer_Name, o.OrderID
    FROM customer_rates_cooks r
    INNER JOIN user u ON r.CustomerID = u.U_ID
    LEFT JOIN orders o ON r.Order_ID = o.OrderID
    WHERE r.CookID = ?
    ORDER BY r.Created_Date DESC
";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $cook_id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Barir Swad</title>
    <link rel="stylesheet" href="cook_styles.css">
    <link rel="stylesheet" href="cook_reviews_styles.css">
    <link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="nav">
            <div class="logo">🥘Barir Swad</div>
            <nav class="nav-links">
                <a href="cook_dashboard.php" class="btn">Dashboard</a>
                <a href="cook_profile.php" class="btn">My Profile</a>
                <a href="complaint_dashboard.php" class="btn">Complaint</a>
                <a href="logout.php" class="btn logout">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <h1>My Reviews</h1>
            <p>See what customers are saying about your cooking and service.</p>
        </div>

        <?php if ($summary && $summary['Total_Reviews'] > 0): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Average Rating</h3>
                    <div class="number"><?= number_format($summary['Average_Rating'], 1) ?> ★</div>
                </div>
                <div class="stat-card">
                    <h3>Food Quality</h3>
                    <div class="number"><?= number_format($summary['Avg_Food_Quality'], 1) ?> ★</div>
                </div>
                <div class="stat-card">
                    <h3>Service</h3>
                    <div class="number"><?= number_format($summary['Avg_Service_Rating'], 1) ?> ★</div>
                </div>
                <div class="stat-card">
                    <h3>Recommendations</h3>
                    <div class="number"><?= number_format($summary['Recommend_Percentage'], 1) ?>%</div>
                </div>
                <div class="stat-card">
                    <h3>Total Reviews</h3>
                    <div class="number"><?= $summary['Total_Reviews'] ?></div>
                </div>
            </div>

            <div class="reviews-grid">
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <h4><?= htmlspecialchars($review['Customer_Name']) ?></h4>
                            <div class="review-rating"><?= number_format($review['Rating'], 1) ?> ★</div>
                        </div>
                        <div class="review-ratings">
                            <p>Food Quality: <?= number_format($review['Food_Quality_Rating'], 1) ?> ★</p>
                            <p>Service: <?= number_format($review['Service_Rating'], 1) ?> ★</p>
                            <p>Would Recommend: <?= $review['Would_Recommend'] ? 'Yes' : 'No' ?></p>
                        </div>
                        <p class="review-comment"><?= htmlspecialchars($review['Comment']) ?: 'No comment provided.' ?></p>
                        <div class="review-footer">
                            <small><?= date('M d, Y H:i', strtotime($review['Created_Date'])) ?></small>
                            <?php if ($review['OrderID']): ?>
                                <small>Order #<?= $review['OrderID'] ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-reviews">You have no reviews yet. Keep cooking great meals!</p>
        <?php endif; ?>
    </div>
</body>
</html>