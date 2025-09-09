<?php
session_start();
require 'dbconnect.php';

// Get cook ratings summary using the view we created
$cook_ratings_sql = "SELECT * FROM cook_ratings_summary ORDER BY Average_Rating DESC, Total_Reviews DESC";
$cook_ratings = $conn->query($cook_ratings_sql);

// Get recent reviews for display
$recent_reviews_sql = "
    SELECT 
        r.Review_Title,
        r.Rating,
        r.Comment,
        r.Created_Date,
        r.Would_Recommend,
        c.Name as CustomerName,
        cook.Name as CookName
    FROM customer_rates_cooks r
    JOIN user c ON r.CustomerID = c.U_ID
    JOIN user cook ON r.CookID = cook.U_ID
    WHERE r.Status = 'Active'
    ORDER BY r.Created_Date DESC
    LIMIT 10
";
$recent_reviews = $conn->query($recent_reviews_sql);

// Get specific cook details if cook_id is provided
$selected_cook = null;
$cook_reviews = null;
if (isset($_GET['cook_id']) && is_numeric($_GET['cook_id'])) {
    $cook_id = $_GET['cook_id'];
    
    // Get cook details
    $cook_sql = "SELECT * FROM cook_ratings_summary WHERE Cook_ID = ?";
    $cook_stmt = $conn->prepare($cook_sql);
    $cook_stmt->bind_param("i", $cook_id);
    $cook_stmt->execute();
    $cook_result = $cook_stmt->get_result();
    
    if ($cook_result->num_rows > 0) {
        $selected_cook = $cook_result->fetch_assoc();
        
        // Get all reviews for this cook
        $reviews_sql = "
            SELECT 
                r.*,
                c.Name as CustomerName
            FROM customer_rates_cooks r
            JOIN user c ON r.CustomerID = c.U_ID
            WHERE r.CookID = ? AND r.Status = 'Active'
            ORDER BY r.Created_Date DESC
        ";
        $reviews_stmt = $conn->prepare($reviews_sql);
        $reviews_stmt->bind_param("i", $cook_id);
        $reviews_stmt->execute();
        $cook_reviews = $reviews_stmt->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $selected_cook ? htmlspecialchars($selected_cook['CookName']) . ' - Reviews' : 'Cook Ratings' ?> - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="cookrating_styles.css">
</head>
<body>
    <header class="header">
        <div class="nav">
            <div class="logo">Barir Swad</div>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="meal.php">Menu</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_type'] == 'Customer'): ?>
                        <a href="customer_dashboard.php">Dashboard</a>
                        <a href="customer_review.php">Write Review</a>
                    <?php elseif ($_SESSION['user_type'] == 'Admin'): ?>
                        <a href="admin_dash.php">Admin Panel</a>
                    <?php endif; ?>
                    <a href="admin_logout.php" class="btn logout">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if ($selected_cook): ?>
            <!-- Individual Cook Details -->
            <a href="cook_ratings.php" class="back-btn">← Back to All Cooks</a>
            
            <div class="cook-detail-section">
                <div class="cook-header">
                    <div class="cook-avatar">
                        <?= strtoupper(substr($selected_cook['Cook_Name'], 0, 1)) ?>
                    </div>
                    <div class="cook-info">
                        <h2><?= htmlspecialchars($selected_cook['Cook_Name']) ?></h2>
                        <p><?= $selected_cook['Exp_Years'] ?> years of experience</p>
                        <p><?= htmlspecialchars($selected_cook['Cook_Email']) ?></p>
                    </div>
                    <div class="rating-summary">
                        <div class="main-rating"><?= $selected_cook['Average_Rating'] ?: 'N/A' ?>/5</div>
                        <div>⭐⭐⭐⭐⭐</div>
                    </div>
                </div>
                
                <div class="rating-details">
                    <div class="rating-card">
                        <h4>Total Reviews</h4>
                        <div class="value"><?= $selected_cook['Total_Reviews'] ?></div>
                    </div>
                    <div class="rating-card">
                        <h4>Food Quality</h4>
                        <div class="value"><?= $selected_cook['Avg_Food_Quality'] ?: 'N/A' ?>/5</div>
                    </div>
                    <div class="rating-card">
                        <h4>Service Rating</h4>
                        <div class="value"><?= $selected_cook['Avg_Service_Rating'] ?: 'N/A' ?>/5</div>
                    </div>
                    <div class="rating-card">
                        <h4>Recommendations</h4>
                        <div class="value"><?= $selected_cook['Recommend_Percentage'] ?: 0 ?>%</div>
                    </div>
                </div>
            </div>
            
            <!-- Cook's Reviews -->
            <div class="section-title">
                <h3>Customer Reviews (<?= $selected_cook['Total_Reviews'] ?>)</h3>
            </div>
            
            <?php if ($cook_reviews && $cook_reviews->num_rows > 0): ?>
                <div class="reviews-grid">
                    <?php while($review = $cook_reviews->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-title"><?= htmlspecialchars($review['Review_Title']) ?></div>
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= $review['Rating'] ? '⭐' : '☆' ?>
                                    <?php endfor; ?>
                                    <?= $review['Rating'] ?>
                                </div>
                            </div>
                            
                            <div class="review-meta">
                                By <?= htmlspecialchars($review['CustomerName']) ?> 
                                <?= date('F j, Y', strtotime($review['Created_Date'])) ?>
                                <?php if ($review['Order_ID']): ?>
                                     Order #<?= $review['Order_ID'] ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="review-text"><?= nl2br(htmlspecialchars($review['Comment'])) ?></div>
                            
                            <?php if ($review['Food_Quality_Rating'] || $review['Service_Rating']): ?>
                                <div style="margin: 10px 0; font-size: 14px; color: #666;">
                                    <?php if ($review['Food_Quality_Rating']): ?>
                                        Food Quality: <?= $review['Food_Quality_Rating'] ?>/5 • 
                                    <?php endif; ?>
                                    <?php if ($review['Service_Rating']): ?>
                                        Service: <?= $review['Service_Rating'] ?>/5
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($review['Would_Recommend']): ?>
                                <div class="review-recommend">✓ Recommends this cook</div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: white; border-radius: 10px;">
                    <h3>No reviews yet</h3>
                    <p>This cook hasn't received any reviews yet.</p>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- All Cooks Overview -->
            <div class="page-header">
                <h1>Our Cook Ratings & Reviews</h1>
                <p>Discover what our customers say about our talented cooks</p>
            </div>
            
            <div class="section-title">
                <h3>All Cooks</h3>
            </div>
            
            <?php if ($cook_ratings->num_rows > 0): ?>
                <div class="cooks-grid">
                    <?php while($cook = $cook_ratings->fetch_assoc()): ?>
                        <div class="cook-card">
                            <div class="cook-card-header">
                                <div class="cook-name"><?= htmlspecialchars($cook['Cook_Name']) ?></div>
                                <div class="cook-rating">
                                    ⭐ <?= $cook['Average_Rating'] ?: 'No rating' ?>
                                </div>
                            </div>
                            
                            <div class="cook-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?= $cook['Total_Reviews'] ?></div>
                                    <div class="stat-label">Reviews</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $cook['Exp_Years'] ?></div>
                                    <div class="stat-label">Years Exp.</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $cook['Recommend_Percentage'] ?: 0 ?>%</div>
                                    <div class="stat-label">Recommend</div>
                                </div>
                            </div>
                            
                            <a href="cook_ratings.php?cook_id=<?= $cook['Cook_ID'] ?>" class="view-details-btn">
                                View Reviews & Details
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: white; border-radius: 10px;">
                    <h3>No cooks found</h3>
                    <p>No cook ratings are available at the moment.</p>
                </div>
            <?php endif; ?>
            
            <!-- Recent Reviews Section -->
            <div class="section-title" style="margin-top: 40px;">
                <h3>Recent Reviews</h3>
            </div>
            
            <?php if ($recent_reviews->num_rows > 0): ?>
                <div class="reviews-grid">
                    <?php while($review = $recent_reviews->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-title"><?= htmlspecialchars($review['Review_Title']) ?></div>
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= $review['Rating'] ? '⭐' : '☆' ?>
                                    <?php endfor; ?>
                                    <?= $review['Rating'] ?>
                                </div>
                            </div>
                            
                            <div class="review-meta">
                                <?= htmlspecialchars($review['CustomerName']) ?> about 
                                <strong><?= htmlspecialchars($review['CookName']) ?></strong> • 
                                <?= date('M j, Y', strtotime($review['Created_Date'])) ?>
                            </div>
                            
                            <div class="review-text">
                                <?= nl2br(htmlspecialchars(substr($review['Comment'], 0, 150))) ?>
                                <?= strlen($review['Comment']) > 150 ? '...' : '' ?>
                            </div>
                            
                            <?php if ($review['Would_Recommend']): ?>
                                <div class="review-recommend">✓ Recommends this cook</div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: white; border-radius: 10px;">
                    <h3>No recent reviews</h3>
                    <p>No recent reviews are available.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 40px 0;">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'Customer'): ?>
                <a href="customer_review.php" class="view-details-btn" style="display: inline-block;">Write a Review</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>