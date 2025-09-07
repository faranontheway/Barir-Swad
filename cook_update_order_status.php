<?php
session_start();
require 'dbconnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Cook') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    $allowed = ['Pending', 'Accepted', 'On the way', 'Delivered', 'Cancelled'];
    if (!in_array($status, $allowed)) {
        die("Invalid status.");
    }

    // Verify order belongs to cook
    $check_sql = "
        SELECT COUNT(*) as count 
        FROM orders o
        INNER JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
        INNER JOIN user_cooks_meal ucm ON ohm.M_ID = ucm.Meal_ID
        WHERE ucm.Cook_ID = ? AND o.OrderID = ?
    ";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $_SESSION['user_id'], $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        $stmt = $conn->prepare("UPDATE orders SET Status=? WHERE OrderID=?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();

        header("Location: cook_orders.php?message=Status updated successfully");
        exit();
    } else {
        die("Unauthorized access to this order.");
    }
}
?>