<?php
session_start();
require 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    // Store the page they wanted to visit
    $_SESSION['redirect_after_login'] = "checkout.php";
    header("Location: login.php");
    exit;
}


// --- Safety check: cart must exist and not be empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    die("❌ Your cart is empty. <a href='meal.php'>Go back to menu</a>");
}

// --- Calculate total cost from cart
$grand_total = 0;
foreach ($_SESSION['cart'] as $meal) {
    $grand_total += $meal['qty'] * $meal['price'];
}

// --- Insert into orders table
$order_sql = "INSERT INTO orders (customer_id, Cost, Status, Date) VALUES (?, ?, ?, NOW())";
$stmt_order = $conn->prepare($order_sql);

$uid = $_SESSION['user_id']; 
$status = "Pending";

$stmt_order->bind_param("ids", $uid, $grand_total, $status);
$stmt_order->execute();

// --- Get the newly created OrderID
$order_id = $stmt_order->insert_id;

// notify all cooks about the new order
$cook_sql = "SELECT U_ID FROM user WHERE Type = 'Cook'";
$cooks = $conn->query($cook_sql);

// Prepare meal list for notification
$meal_names = [];
foreach ($_SESSION['cart'] as $meal_id => $meal) {
    $meal_names[] = $meal['name'] . " x" . $meal['qty'];
}
$meal_list = implode(", ", $meal_names);

// Create notification data
$order_info = json_encode([
    'order_id' => $order_id,
    'customer' => $_SESSION['user_name'],
    'cost' => $grand_total,
    'meals' => $meal_list
]);

// Send notification to each cook
while ($cook = $cooks->fetch_assoc()) {
    $complaint_id = rand(1000, 99999);
    $notification_sql = "INSERT INTO complaint_support (User_ID, Complaint_ID, Description, Status, Submitted_Date, Messages) VALUES (?, ?, 'NEW_ORDER', 'Open', CURDATE(), ?)";
    $stmt = $conn->prepare($notification_sql);
    $stmt->bind_param("iis", $cook['U_ID'], $complaint_id, $order_info);
    $stmt->execute();
}


// --- Insert meals into orders_have_meal
$item_sql = "INSERT INTO orders_have_meal (OrderID, M_ID, Quantity, Price) VALUES (?, ?, ?, ?)";
$stmt_item = $conn->prepare($item_sql);

foreach ($_SESSION['cart'] as $meal_id => $meal) {
    $qty = $meal['qty'];
    $price = $meal['price'];
    $stmt_item->bind_param("iiid", $order_id, $meal_id, $qty, $price);
    $stmt_item->execute();
}

// --- Clear cart after checkout
unset($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Confirmation</title>
  <style>
  body {
    font-family: Arial, sans-serif;
    background: #FFF8EE; /* lily */
    text-align: center;
    margin: 50px;
    color: #953029; /* hibiscus */
  }

  .box {
    background: #fff;
    border: 2px solid #E2A48A; /* desert rose */
    border-radius: 12px;
    padding: 30px;
    display: inline-block;
    box-shadow: 0 4px 10px rgba(149, 48, 41, 0.15); /* hibiscus shadow */
  }

  h1 {
    color: #953029; /* hibiscus */
    margin-bottom: 20px;
  }

  p {
    font-size: 1.1em;
    margin: 10px 0;
  }

  a {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 24px;
    text-decoration: none;
    color: #FFF8EE;
    background: #D6877F; /* rosewood */
    border-radius: 6px;
    font-weight: bold;
    transition: background 0.3s ease;
  }

  a:hover {
    background: #BA5448; /* dahlia */
  }
</style>
</head>
<body>
  <div class="box">
    <h1>✅ Order Placed Successfully!</h1>
    <p>Your Order ID is: <strong><?php echo $order_id; ?></strong></p>
    <p>Total Amount: ৳ <?php echo number_format($grand_total, 2); ?></p>
    <a href="meal.php">🍴 Go Back to Menu</a>
    <a href="customer_dashboard.php">🏠 Go Back to My Dashboard</a>
  </div>
</body>
</html>