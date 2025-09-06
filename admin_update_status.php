<?php
session_start();
require 'dbconnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['status'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $status = $_POST['status'];

    $allowed = ['Open','In Progress','Resolved','Closed'];
    if (!in_array($status, $allowed)) {
        die("Invalid status.");
    }

    $stmt = $conn->prepare("UPDATE complaint_support SET Status=? WHERE Complaint_ID=?");
    $stmt->bind_param("si", $status, $complaint_id);
    $stmt->execute();

    header("Location: admin_complaint_dashboard.php");
    exit();
}
?>
