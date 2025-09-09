<?php
session_start();
require 'dbconnect.php';

// Check if cook is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Cook') {
    header("Location: login.php");
    exit();
}

$cook_id = $_SESSION['user_id'];

if (isset($_GET['meal_id'])) {
    $meal_id = intval($_GET['meal_id']);

    // Make sure this meal belongs to this cook
    $stmt = $conn->prepare("SELECT * FROM user_cooks_meal WHERE Cook_ID = ? AND Meal_ID = ?");
    $stmt->bind_param("ii", $cook_id, $meal_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Get the meal name to construct the image filename
        $stmt = $conn->prepare("SELECT Name FROM meal WHERE Meal_ID = ?");
        $stmt->bind_param("i", $meal_id);
        $stmt->execute();
        $meal_result = $stmt->get_result();
        
        if ($meal_result->num_rows > 0) {
            $meal = $meal_result->fetch_assoc();
            $image_name = strtolower(str_replace(' ', '-', $meal['Name'])) . '.jpg';
            $image_path = $image_name; // Assuming images are stored in the same directory as add_meal.php

            // Delete the image file if it exists
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Delete from meal table
        $stmt = $conn->prepare("DELETE FROM meal WHERE Meal_ID = ?");
        $stmt->bind_param("i", $meal_id);
        $stmt->execute();

        // Delete mapping from user_cooks_meal
        $stmt = $conn->prepare("DELETE FROM user_cooks_meal WHERE Meal_ID = ?");
        $stmt->bind_param("i", $meal_id);
        $stmt->execute();
    }
}

header("Location: cook_dashboard.php");
exit();
?>