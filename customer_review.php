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

/* Handle New Review Submission */
if ($_POST && isset($_POST['submit_review'])) {
    $cook_id = $_POST['cook_id'];
    $order_id = !empty($_POST['order_id']) ? $_POST['order_id'] : null;
    $rating = $_POST['rating'];
    $review_title = $_POST['review_title'];
    $comment = $_POST['comment'];
    $food_quality_rating = !empty($_POST['food_quality_rating']) ? $_POST['food_quality_rating'] : null;
    $service_rating = !empty($_POST['service_rating']) ? $_POST['service_rating'] : null;
    $would_recommend = isset($_POST['would_recommend']) ? 1 : 0;

    // Prevent duplicate review for same cook/order
    $check_sql = "SELECT Review_ID FROM customer_rates_cooks WHERE CustomerID = ? AND CookID = ?";
    $params = [$customer_id, $cook_id];
    $types = "ii";

    if ($order_id) {
        $check_sql .= " AND Order_ID = ?";
        $params[] = $order_id;
        $types .= "i";
    } else {
        $check_sql .= " AND Order_ID IS NULL";
    }

    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param($types, ...$params);
    $check_stmt->execute();
    $existing = $check_stmt->get_result();

    if ($existing->num_rows > 0) {
        $error = "You have already reviewed this cook" . ($order_id ? " for this order." : ".");
    } else {
        $sql = "INSERT INTO customer_rates_cooks 
                (CustomerID, CookID, Order_ID, Rating, Review_Title, Comment, Food_Quality_Rating, Service_Rating, Would_Recommend) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiidssddi", $customer_id, $cook_id, $order_id, $rating, $review_title, $comment, $food_quality_rating, $service_rating, $would_recommend);

        if ($stmt->execute()) {
            $message = "Review submitted successfully!";
        } else {
            $error = "Error submitting review. Please try again.";
        }
    }
}

/*Handle Review Update */
if ($_POST && isset($_POST['update_review'])) {
    $review_id = $_POST['review_id'];
    $rating = $_POST['rating'];
    $review_title = $_POST['review_title'];
    $comment = $_POST['comment'];
    $food_quality_rating = !empty($_POST['food_quality_rating']) ? $_POST['food_quality_rating'] : null;
    $service_rating = !empty($_POST['service_rating']) ? $_POST['service_rating'] : null;
    $would_recommend = isset($_POST['would_recommend']) ? 1 : 0;

    $sql = "UPDATE customer_rates_cooks 
            SET Rating=?, Review_Title=?, Comment=?, Food_Quality_Rating=?, Service_Rating=?, Would_Recommend=?, Updated_Date=NOW() 
            WHERE Review_ID=? AND CustomerID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dssddiii", $rating, $review_title, $comment, $food_quality_rating, $service_rating, $would_recommend, $review_id, $customer_id);

    if ($stmt->execute()) {
        $message = "Review updated successfully!";
        header("Location: customer_review.php");
        exit();
    } else {
        $error = "Error updating review.";
    }
}

/* Handle Review Deletion */
if ($_POST && isset($_POST['delete_review'])) {
    $review_id = $_POST['review_id'];

    $sql = "DELETE FROM customer_rates_cooks WHERE Review_ID=? AND CustomerID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $review_id, $customer_id);

    if ($stmt->execute()) {
        $message = "Review deleted successfully!";
    } else {
        $error = "Error deleting review.";
    }
}

/* Fetch Data */
// All cooks for dropdown
$cooks_result = $conn->query("SELECT U_ID, Name, Exp_Years FROM user WHERE Type='Cook' ORDER BY Name");

// Customer’s delivered orders
$orders_stmt = $conn->prepare("
    SELECT o.OrderID, o.Date, o.Cost, GROUP_CONCAT(m.Name SEPARATOR ', ') as Meals
    FROM orders o
    LEFT JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
    LEFT JOIN meal m ON ohm.M_ID = m.Meal_ID
    WHERE o.Customer_ID=? AND o.Status='Delivered'
    GROUP BY o.OrderID, o.Date, o.Cost
    ORDER BY o.Date DESC LIMIT 20
");
$orders_stmt->bind_param("i", $customer_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

// Customer’s reviews
$reviews_stmt = $conn->prepare("
    SELECT r.*, u.Name as CookName
    FROM customer_rates_cooks r
    JOIN user u ON r.CookID=u.U_ID
    WHERE r.CustomerID=?
    ORDER BY r.Created_Date DESC
");
$reviews_stmt->bind_param("i", $customer_id);
$reviews_stmt->execute();
$my_reviews = $reviews_stmt->get_result();

// Check if editing
$edit_review = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_stmt = $conn->prepare("SELECT * FROM customer_rates_cooks WHERE Review_ID=? AND CustomerID=?");
    $edit_stmt->bind_param("ii", $edit_id, $customer_id);
    $edit_stmt->execute();
    $result = $edit_stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_review = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Reviews - Barir Swad</title>
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
            <a href="logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="container">
    <h1><?= $edit_review ? "Edit Review" : "Write a Review" ?></h1>

    <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Review Form -->
    <form method="POST">
        <?php if ($edit_review): ?>
            <input type="hidden" name="review_id" value="<?= $edit_review['Review_ID'] ?>">
        <?php endif; ?>

        <label>Cook:</label>
        <select name="cook_id" required <?= $edit_review ? 'disabled' : '' ?>>
            <option value="">Select Cook</option>
            <?php while($cook = $cooks_result->fetch_assoc()): ?>
                <option value="<?= $cook['U_ID'] ?>" 
                    <?= ($edit_review && $edit_review['CookID'] == $cook['U_ID']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cook['Name']) ?> (<?= $cook['Exp_Years'] ?> yrs exp.)
                </option>
            <?php endwhile; ?>
        </select>
        <?php if ($edit_review): ?>
            <input type="hidden" name="cook_id" value="<?= $edit_review['CookID'] ?>">
        <?php endif; ?>

        <label>Order (optional):</label>
        <select name="order_id">
            <option value="">No specific order</option>
            <?php while($order = $orders_result->fetch_assoc()): ?>
                <option value="<?= $order['OrderID'] ?>"
                    <?= ($edit_review && $edit_review['Order_ID'] == $order['OrderID']) ? 'selected' : '' ?>>
                    Order #<?= $order['OrderID'] ?> - <?= date("M j, Y", strtotime($order['Date'])) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Review Title:</label>
        <input type="text" name="review_title" required
               value="<?= $edit_review ? htmlspecialchars($edit_review['Review_Title']) : '' ?>">

        <label>Rating:</label>
        <select name="rating" required>
            <option value="">Select Rating</option>
            <?php for($i=1; $i<=5; $i+=0.5): ?>
                <option value="<?= $i ?>" <?= ($edit_review && $edit_review['Rating']==$i) ? 'selected' : '' ?>>
                    <?= $i ?> Stars
                </option>
            <?php endfor; ?>
        </select>

        <label>Comment:</label>
        <textarea name="comment" required><?= $edit_review ? htmlspecialchars($edit_review['Comment']) : '' ?></textarea>

        <label><input type="checkbox" name="would_recommend" 
            <?= (!$edit_review || $edit_review['Would_Recommend']) ? 'checked' : '' ?>> Recommend this cook</label>

        <button type="submit" name="<?= $edit_review ? 'update_review' : 'submit_review' ?>">
            <?= $edit_review ? 'Update Review' : 'Submit Review' ?>
        </button>
    </form>

    <!-- My Reviews -->
    <h2>My Reviews</h2>
    <?php if ($my_reviews->num_rows > 0): ?>
        <?php while($r = $my_reviews->fetch_assoc()): ?>
            <div class="review-card">
                <h3><?= htmlspecialchars($r['Review_Title']) ?> (<?= $r['Rating'] ?>/5)</h3>
                <p><strong>Cook:</strong> <?= htmlspecialchars($r['CookName']) ?></p>
                <p><?= nl2br(htmlspecialchars($r['Comment'])) ?></p>
                <div class="actions">
                    <a href="customer_review.php?edit=<?= $r['Review_ID'] ?>" class="btn">Edit</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this review permanently?')">
                        <input type="hidden" name="review_id" value="<?= $r['Review_ID'] ?>">
                        <button type="submit" name="delete_review" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No reviews yet. Write your first review above!</p>
    <?php endif; ?>
</div>
</body>
</html>
