<?php
session_start();
require 'dbconnect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Handle review status update
if ($_POST && isset($_POST['update_status'])) {
    $review_id = $_POST['review_id'];
    $new_status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    $sql = "UPDATE customer_rates_cooks SET Status = ?, AdminNotes = ? WHERE ReviewID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $new_status, $admin_notes, $review_id);
    
    if ($stmt->execute()) {
        $message = "Review status updated successfully!";
    } else {
        $message = "Error updating review status.";
    }
}

// Handle review deletion
if ($_POST && isset($_POST['delete_review'])) {
    $review_id = $_POST['review_id'];
    
    $sql = "DELETE FROM customer_rates_cooks WHERE ReviewID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $review_id);
    
    if ($stmt->execute()) {
        $message = "Review deleted successfully!";
    } else {
        $message = "Error deleting review.";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$cook_filter = $_GET['cook'] ?? 'all';
$rating_filter = $_GET['rating'] ?? 'all';

// Build WHERE clause for filters
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "r.Status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($cook_filter !== 'all') {
    $where_conditions[] = "r.CookID = ?";
    $params[] = $cook_filter;
    $param_types .= 'i';
}

if ($rating_filter !== 'all') {
    if ($rating_filter === 'high') {
        $where_conditions[] = "r.Rating >= 4.0";
    } elseif ($rating_filter === 'medium') {
        $where_conditions[] = "r.Rating >= 3.0 AND r.Rating < 4.0";
    } elseif ($rating_filter === 'low') {
        $where_conditions[] = "r.Rating < 3.0";
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all reviews with customer and cook details
$sql = "
    SELECT 
        r.*,
        c.Name as CustomerName,
        c.Email as CustomerEmail,
        cook.Name as CookName,
        cook.Email as CookEmail
    FROM customer_rates_cooks r
    JOIN user c ON r.CustomerID = c.U_ID
    JOIN user cook ON r.CookID = cook.U_ID
    $where_clause
    ORDER BY r.Created_Date DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $reviews_result = $stmt->get_result();
} else {
    $reviews_result = $conn->query($sql);
}

// Get review statistics
$stats = [];
$stats['total_reviews'] = $conn->query("SELECT COUNT(*) as count FROM customer_rates_cooks")->fetch_assoc()['count'];
$stats['active_reviews'] = $conn->query("SELECT COUNT(*) as count FROM customer_rates_cooks WHERE Status = 'Active'")->fetch_assoc()['count'];
$stats['reported_reviews'] = $conn->query("SELECT COUNT(*) as count FROM customer_rates_cooks WHERE Status = 'Reported'")->fetch_assoc()['count'];
$stats['avg_rating'] = $conn->query("SELECT ROUND(AVG(Rating), 1) as avg FROM customer_rates_cooks WHERE Status = 'Active'")->fetch_assoc()['avg'] ?? 0;

// Get cooks for filter dropdown
$cooks_for_filter = $conn->query("SELECT U_ID, Name FROM user WHERE Type = 'Cook' ORDER BY Name");

// Get top rated cooks
$top_cooks = $conn->query("
    SELECT 
        cook.Name,
        COUNT(r.ReviewID) as ReviewCount,
        ROUND(AVG(r.Rating), 1) as AvgRating,
        COUNT(CASE WHEN r.WouldRecommend = 1 THEN 1 END) as RecommendCount
    FROM user cook
    LEFT JOIN customer_rates_cooks r ON cook.U_ID = r.CookID AND r.Status = 'Active'
    WHERE cook.Type = 'Cook'
    GROUP BY cook.U_ID, cook.Name
    HAVING COUNT(r.ReviewID) > 0
    ORDER BY AvgRating DESC, ReviewCount DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Management - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="admin-header">
        <div class="admin-nav">
            <h1>⭐ Barir Swad - Review Management</h1>
            <nav class="nav-links">
                <a href="admin_dash.php">Dashboard</a>
                <a href="admin_orders.php">Orders</a>
                <a href="admin_users.php">Users</a>
                <a href="admin_meals.php">Meals</a>
                <a href="admin_catering.php">Catering</a>
                <a href="admin_reviews.php" class="active">Reviews</a>
                <a href="admin_complaint_dashboard.php">Complaints</a>
                <a href="admin_logout.php" class="logout-btn">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2>⭐ Review & Rating Management</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Reviews</h3>
                <div class="number"><?= $stats['total_reviews'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Reviews</h3>
                <div class="number"><?= $stats['active_reviews'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Reported Reviews</h3>
                <div class="number"><?= $stats['reported_reviews'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Average Rating</h3>
                <div class="number"><?= $stats['avg_rating'] ?>/5</div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Top Cooks Section -->
        <div class="top-cooks-section">
            <h3>🏆 Top Rated Cooks</h3>
            <?php if ($top_cooks->num_rows > 0): ?>
                <ul class="top-cooks-list">
                    <?php while($cook = $top_cooks->fetch_assoc()): ?>
                        <li>
                            <div>
                                <div class="cook-info"><?= htmlspecialchars($cook['Name']) ?></div>
                                <div class="cook-stats"><?= $cook['ReviewCount'] ?> reviews | <?= $cook['RecommendCount'] ?> recommendations</div>
                            </div>
                            <div class="rating-display">
                                <span class="stars">⭐</span>
                                <strong><?= $cook['AvgRating'] ?>/5</strong>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No reviews available yet.</p>
            <?php endif; ?>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <h3>🔍 Filter Reviews</h3>
            <form method="GET">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="Active" <?= $status_filter == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Hidden" <?= $status_filter == 'Hidden' ? 'selected' : '' ?>>Hidden</option>
                            <option value="Reported" <?= $status_filter == 'Reported' ? 'selected' : '' ?>>Reported</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Cook</label>
                        <select name="cook" onchange="this.form.submit()">
                            <option value="all" <?= $cook_filter == 'all' ? 'selected' : '' ?>>All Cooks</option>
                            <?php while($cook = $cooks_for_filter->fetch_assoc()): ?>
                                <option value="<?= $cook['U_ID'] ?>" <?= $cook_filter == $cook['U_ID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cook['Name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Rating</label>
                        <select name="rating" onchange="this.form.submit()">
                            <option value="all" <?= $rating_filter == 'all' ? 'selected' : '' ?>>All Ratings</option>
                            <option value="high" <?= $rating_filter == 'high' ? 'selected' : '' ?>>High (4.0+)</option>
                            <option value="medium" <?= $rating_filter == 'medium' ? 'selected' : '' ?>>Medium (3.0-3.9)</option>
                            <option value="low" <?= $rating_filter == 'low' ? 'selected' : '' ?>>Low (Below 3.0)</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <a href="admin_reviews.php" class="btn-sm btn-primary" style="padding: 8px 16px; text-align: center;">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Reviews Table -->
        <div class="reviews-table">
            <?php if ($reviews_result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Cook</th>
                            <th>Review</th>
                            <th>Ratings</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($review = $reviews_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $review['ReviewID'] ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($review['CustomerName']) ?><br>
                                    <small style="color: #666;"><?= htmlspecialchars($review['CustomerEmail']) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($review['CookName']) ?><br>
                                    <small style="color: #666;"><?= htmlspecialchars($review['CookEmail']) ?></small>
                                </td>
                                <td class="review-content">
                                    <div class="review-title"><?= htmlspecialchars($review['ReviewTitle']) ?></div>
                                    <div class="review-text"><?= htmlspecialchars(substr($review['Comment'], 0, 100)) ?><?= strlen($review['Comment']) > 100 ? '...' : '' ?></div>
                                    <?php if ($review['OrderID']): ?>
                                        <small style="color: #007bff;">Order: #<?= $review['OrderID'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="rating-display">
                                        <span class="stars">⭐</span>
                                        <strong><?= $review['Rating'] ?>/5</strong>
                                    </div>
                                    <?php if ($review['FoodQualityRating'] || $review['ServiceRating']): ?>
                                        <div class="rating-breakdown">
                                            <?php if ($review['FoodQualityRating']): ?>Food: <?= $review['FoodQualityRating'] ?><br><?php endif; ?>
                                            <?php if ($review['ServiceRating']): ?>Service: <?= $review['ServiceRating'] ?><br><?php endif; ?>
                                            Recommend: <?= $review['WouldRecommend'] ? 'Yes' : 'No' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status <?= strtolower($review['Status']) ?>">
                                        <?= $review['Status'] ?>
                                    </span>
                                    <?php if ($review['AdminNotes']): ?>
                                        <br><small title="<?= htmlspecialchars($review['AdminNotes']) ?>">📝 Notes</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('M j, Y', strtotime($review['Created_At'])) ?><br>
                                    <small><?= date('g:i A', strtotime($review['Created_At'])) ?></small>
                                    <?php if ($review['Updated_At'] != $review['Created_At']): ?>
                                        <br><small style="color: #666;">Updated: <?= date('M j', strtotime($review['Updated_At'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="toggleAdminForm(<?= $review['ReviewID'] ?>)" class="btn-sm btn-primary">Manage</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this review?')">
                                            <input type="hidden" name="review_id" value="<?= $review['ReviewID'] ?>">
                                            <button type="submit" name="delete_review" class="btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                    
                                    <div id="adminForm<?= $review['ReviewID'] ?>" class="admin-form" style="display: none;">
                                        <form method="POST">
                                            <input type="hidden" name="review_id" value="<?= $review['ReviewID'] ?>">
                                            <select name="status" required>
                                                <option value="Active" <?= $review['Status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                                <option value="Hidden" <?= $review['Status'] == 'Hidden' ? 'selected' : '' ?>>Hidden</option>
                                                <option value="Reported" <?= $review['Status'] == 'Reported' ? 'selected' : '' ?>>Reported</option>
                                            </select>
                                            <textarea name="admin_notes" placeholder="Admin notes..."><?= htmlspecialchars($review['AdminNotes'] ?? '') ?></textarea>
                                            <button type="submit" name="update_status" class="btn-sm btn-warning">Update</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 40px; text-align: center;">
                    <h3>No reviews found</h3>
                    <p>No reviews match your current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleAdminForm(reviewId) {
            const form = document.getElementById('adminForm' + reviewId);
            if (form.style.display === 'none') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html>