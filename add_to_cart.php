<?php
session_start();
require 'dbconnect.php';

if (isset($_GET['meal_id'])) {
    $meal_id = intval($_GET['meal_id']);

    // Fetch meal info from DB
    $sql = "SELECT * FROM meal WHERE Meal_ID = $meal_id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $meal = $result->fetch_assoc();

        // If cart not created yet
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // If already in cart, increase qty
        if (isset($_SESSION['cart'][$meal_id])) {
            $_SESSION['cart'][$meal_id]['qty'] += 1;
        } else {
            $_SESSION['cart'][$meal_id] = [
                "name" => $meal['Name'],
                "price" => $meal['Pricing'],
                "qty" => 1
            ];
        }
    }
}

// Redirect to cart
header("Location: view_cart.php");
exit;

