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

// Handle review submission
if ($_POST && isset($_POST['submit_review'])) {
    $cook_id = $_POST['cook_id'];
    $order_id = !empty($_POST['order_id']) ? $_POST['order_id'] : null;
    $rating = $_POST['rating'];
    $review_title = $_POST['review_title'];
    $comment = $_POST['comment'];
    $food_quality_rating = !empty($_POST['food_quality_rating']) ? $_POST['food_quality_rating'] : null;
    $service_rating = !empty($_POST['service_rating']) ? $_POST['service_rating'] : null;
    $would_recommend = isset($_POST['would_recommend']) ? 1 : 0;
    
    // Check if customer already reviewed this cook for the same order
    $check_sql = "SELECT Review_ID FROM customer_rates_cooks WHERE CustomerID = ? AND CookID = ?";
    $params = [$customer_id, $cook_id];
    $param_types = "ii";
    
    if ($order_id) {
        $check_sql .= " AND Order_ID = ?";
        $params[] = $order_id;
        $param_types .= "i";
    } else {
        $check_sql .= " AND Order_ID IS NULL";
    }
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param($param_types, ...$params);
    $check_stmt->execute();
    $existing_review = $check_stmt->get_result();
    
    if ($existing_review->num_rows > 0) {
        $error = "You have already reviewed this cook" . ($order_id ? " for this order" : "") . ".";
    } else {
        $sql = "INSERT INTO customer_rates_cooks (CustomerID, CookID, Order_ID, Rating, Review_Title, Comment, Food_Quality_Rating, Service_Rating, Would_Recommend) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiidssddi", $customer_id, $cook_id, $order_id, $rating, $review_title, $comment, $food_quality_rating, $service_rating, $would_recommend);
        
        if ($stmt->execute()) {
            $message = "Review submitted successfully!";
        } else {
            $error = "Error submitting review. Please try again.";
        }
    }
}

// Handle review update
if ($_POST && isset($_POST['update_review'])) {
    $review_id = $_POST['review_id'];
    $rating = $_POST['rating'];
    $review_title = $_POST['review_title'];
    $comment = $_POST['comment'];
    $food_quality_rating = !empty($_POST['food_quality_rating']) ? $_POST['food_quality_rating'] : null;
    $service_rating = !empty($_POST['service_rating']) ? $_POST['service_rating'] : null;
    $would_recommend = isset($_POST['would_recommend']) ? 1 : 0;
    
    $sql = "UPDATE customer_rates_cooks SET Rating = ?, Review_Title = ?, Comment = ?, Food_Quality_Rating = ?, Service_Rating = ?, Would_Recommend = ? WHERE Review_ID = ? AND CustomerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dssddiiii", $rating, $review_title, $comment, $food_quality_rating, $service_rating, $would_recommend, $review_id, $customer_id);
    
    if ($stmt->execute()) {
        $message = "Review updated successfully!";
        header("Location: customer_review.php");
        exit();
    } else {
        $error = "Error updating review.";
    }
}

// Handle review deletion (set status to Hidden)
if ($_POST && isset($_POST['delete_review'])) {
    $review_id = $_POST['review_id'];
    
    $sql = "UPDATE customer_rates_cooks SET Status = 'Hidden' WHERE Review_ID = ? AND CustomerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $review_id, $customer_id);
    
    if ($stmt->execute()) {
        $message = "Review hidden successfully!";
    } else {
        $error = "Error hiding review.";
    }
}

// Get available cooks for review
$cooks_sql = "SELECT DISTINCT u.U_ID, u.Name, u.Exp_Years FROM user u WHERE u.Type = 'Cook' ORDER BY u.Name";
$cooks_result = $conn->query($cooks_sql);

// Get customer's delivered orders for context
$orders_sql = "SELECT o.OrderID, o.Date, o.Cost, GROUP_CONCAT(m.Name SEPARATOR ', ') as Meals 
               FROM orders o 
               LEFT JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID 
               LEFT JOIN meal m ON ohm.M_ID = m.Meal_ID 
               WHERE o.Customer_ID = ? AND o.Status = 'Delivered' 
               GROUP BY o.OrderID, o.Date, o.Cost 
               ORDER BY o.Date DESC 
               LIMIT 20";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $customer_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

// Get customer's existing reviews
$reviews_sql = "SELECT r.*, u.Name as CookName 
                FROM customer_rates_cooks r 
                JOIN user u ON r.CookID = u.U_ID 
                WHERE r.CustomerID = ? AND r.Status = 'Active' 
                ORDER BY r.Created_Date DESC";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $customer_id);
$reviews_stmt->execute();
$my_reviews = $reviews_stmt->get_result();

// Get edit review data if editing
$edit_review = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_sql = "SELECT * FROM customer_rates_cooks WHERE Review_ID = ? AND CustomerID = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("ii", $edit_id, $customer_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_review = $edit_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_review ? 'Edit Review' : 'Write Review' ?> - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="nav">
            <div class="logo">Barir Swad</div>
            <nav class="nav-links">
                <a href="customer_dashboard.php">Dashboard</a>
                <a href="meal.php">Browse Meals</a>
                <a href="customer_review.php" class="active">Reviews</a>
                <a href="view_cart.php">Cart</a>
                <span>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
                <a href="admin_logout.php" class="btn logout">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1><?= $edit_review ? 'Edit Your Review' : 'Write a Review' ?></h1>
            <p>Share your experience with our cooks to help other customers</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="review-sections">
            <!-- Review Form -->
            <div class="review-form-section">
                <h3><?= $edit_review ? 'Edit Review' : 'Write New Review' ?></h3>
                
                <form method="POST">
                    <?php if ($edit_review): ?>
                        <input type="hidden" name="review_id" value="<?= $edit_review['Review_ID'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="cook_id">Select Cook</label>
                        <select id="cook_id" name="cook_id" required <?= $edit_review ? 'disabled' : '' ?>>
                            <option value="">Choose a cook...</option>
                            <?php 
                            // Reset the result pointer for cooks
                            $cooks_result = $conn->query("SELECT DISTINCT u.U_ID, u.Name, u.Exp_Years FROM user u WHERE u.Type = 'Cook' ORDER BY u.Name");
                            while($cook = $cooks_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $cook['U_ID'] ?>" 
                                    <?= ($edit_review && $edit_review['CookID'] == $cook['U_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cook['Name']) ?> (<?= $cook['Exp_Years'] ?> years exp.)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if ($edit_review): ?>
                            <input type="hidden" name="cook_id" value="<?= $edit_review['CookID'] ?>">
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="order_id">Related Order (Optional)</label>
                        <select id="order_id" name="order_id">
                            <option value="">No specific order</option>
                            <?php 
                            // Reset orders result pointer  
                            $orders_result = $conn->query("SELECT o.OrderID, o.Date, o.Cost, GROUP_CONCAT(m.Name SEPARATOR ', ') as Meals 
                                                          FROM orders o 
                                                          LEFT JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID 
                                                          LEFT JOIN meal m ON ohm.M_ID = m.Meal_ID 
                                                          WHERE o.Customer_ID = $customer_id AND o.Status = 'Delivered' 
                                                          GROUP BY o.OrderID, o.Date, o.Cost 
                                                          ORDER BY o.Date DESC 
                                                          LIMIT 20");
                            while($order = $orders_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $order['OrderID'] ?>" 
                                    <?= ($edit_review && $edit_review['Order_ID'] == $order['OrderID']) ? 'selected' : '' ?>>
                                    Order #<?= $order['OrderID'] ?> - <?= date('M j, Y', strtotime($order['Date'])) ?> 
                                    <?= $order['Meals'] ? '(' . htmlspecialchars(substr($order['Meals'], 0, 30)) . '...)' : '' ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="review_title">Review Title</label>
                        <input type="text" id="review_title" name="review_title" required 
                               value="<?= $edit_review ? htmlspecialchars($edit_review['Review_Title']) : '' ?>"
                               placeholder="Brief title for your review">
                    </div>
                    
                    <div class="form-group">
                        <label>Overall Rating</label>
                        <select name="rating" required>
                            <option value="">Select Rating</option>
                            <?php for($i = 1; $i <= 5; $i += 0.5): ?>
                                <option value="<?= $i ?>" 
                                    <?= ($edit_review && $edit_review['Rating'] == $i) ? 'selected' : '' ?>>
                                    <?= $i ?> Star<?= $i != 1 ? 's' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Detailed Ratings (Optional)</label>
                        <div class="rating-group">
                            <div>
                                <label>Food Quality</label>
                                <select name="food_quality_rating">
                                    <option value="">N/A</option>
                                    <?php for($i = 1; $i <= 5; $i += 0.5): ?>
                                        <option value="<?= $i ?>" 
                                            <?= ($edit_review && $edit_review['Food_Quality_Rating'] == $i) ? 'selected' : '' ?>>
                                            <?= $i ?> Star<?= $i != 1 ? 's' : '' ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label>Service</label>
                                <select name="service_rating">
                                    <option value="">N/A</option>
                                    <?php for($i = 1; $i <= 5; $i += 0.5): ?>
                                        <option value="<?= $i ?>" 
                                            <?= ($edit_review && $edit_review['Service_Rating'] == $i) ? 'selected' : '' ?>>
                                            <?= $i ?> Star<?= $i != 1 ? 's' : '' ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment">Your Review</label>
                        <textarea id="comment" name="comment" required 
                                  placeholder="Share your experience with this cook..."><?= $edit_review ? htmlspecialchars($edit_review['Comment']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="would_recommend" name="would_recommend" 
                                   <?= (!$edit_review || $edit_review['Would_Recommend']) ? 'checked' : '' ?>>
                            <label for="would_recommend">I would recommend this cook to others</label>
                        </div>
                    </div>
                    
                    <button type="submit" name="<?= $edit_review ? 'update_review' : 'submit_review' ?>" class="submit-btn">
                        <?= $edit_review ? 'Update Review' : 'Submit Review' ?>
                    </button>
                    
                    <?php if ($edit_review): ?>
                        <a href="customer_review.php" class="btn btn-secondary" style="display: block; text-align: center; margin-top: 10px; text-decoration: none;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- My Reviews -->
            <div class="my-reviews-section">
                <h3>My Reviews</h3>
                
                <?php if ($my_reviews->num_rows > 0): ?>
                    <?php while($review = $my_reviews->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-title"><?= htmlspecialchars($review['Review_Title']) ?></div>
                                <div class="review-rating">
                                    <div class="stars">
                                        <?php 
                                        $rating = $review['Rating'];
                                        for($i = 1; $i <= 5; $i++) {
                                            if($i <= $rating) echo '★';
                                            elseif($i - 0.5 <= $rating) echo '☆';
                                            else echo '☆';
                                        }
                                        ?>
                                    </div>
                                    <span><?= $review['Rating'] ?>/5</span>
                                </div>
                            </div>
                            
                            <div class="review-meta">
                                For: <strong><?= htmlspecialchars($review['CookName']) ?></strong> | 
                                Posted: <?= date('M j, Y', strtotime($review['Created_Date'])) ?>
                                <?php if ($review['Updated_Date'] != $review['Created_Date']): ?>
                                    | Updated: <?= date('M j, Y', strtotime($review['Updated_Date'])) ?>
                                <?php endif; ?>
                                <?php if ($review['Order_ID']): ?>
                                    | Order: #<?= $review['Order_ID'] ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($review['Food_Quality_Rating'] || $review['Service_Rating']): ?>
                                <div class="rating-breakdown">
                                    <?php if ($review['Food_Quality_Rating']): ?>
                                        <div class="rating-item">
                                            <span>Food Quality:</span>
                                            <span><?= $review['Food_Quality_Rating'] ?>/5 ★</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($review['Service_Rating']): ?>
                                        <div class="rating-item">
                                            <span>Service:</span>
                                            <span><?= $review['Service_Rating'] ?>/5 ★</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="rating-item">
                                        <span>Would Recommend:</span>
                                        <span><?= $review['Would_Recommend'] ? 'Yes ✓' : 'No ✗' ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="review-text"><?= nl2br(htmlspecialchars($review['Comment'])) ?></div>
                            
                            <div class="review-actions">
                                <a href="customer_review.php?edit=<?= $review['Review_ID'] ?>" class="btn btn-primary">Edit</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to hide this review?')">
                                    <input type="hidden" name="review_id" value="<?= $review['Review_ID'] ?>">
                                    <button type="submit" name="delete_review" class="btn btn-danger">Hide</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h4>No reviews yet</h4>
                        <p>Write your first review using the form on the left!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="customer_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>