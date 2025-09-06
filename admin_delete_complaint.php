<?php
session_start();
require 'dbconnect.php';

// Check if logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Get the complaint ID
if (!isset($_GET['id'])) {
    header("Location: admin_complaints.php");
    exit();
}
$complaint_id = intval($_GET['id']);

// Delete the complaint (no user restriction)
$delete_sql = "DELETE FROM complaint_support WHERE Complaint_ID = ?";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("i", $complaint_id);
$stmt->execute();

// Redirect back to admin complaints page
header("Location: admin_complaint_dashboard.php?deleted=1");
exit();
?>
