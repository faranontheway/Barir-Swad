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
