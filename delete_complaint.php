<?php
session_start();
require '../dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Get the complaint ID from the URL
if (!isset($_GET['id'])) {
    header("Location: complaint_dashboard.php"); // redirect if no ID
    exit();
}
$complaint_id = intval($_GET['id']);

// Verify the complaint belongs to the logged-in user
$sql = "SELECT * FROM complaint_support WHERE Complaint_ID = ? AND User_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Complaint not found or does not belong to user
    header("Location: complaint_dashboard.php");
    exit();
}

// Delete the complaint
$delete_sql = "DELETE FROM complaint_support WHERE Complaint_ID = ? AND User_ID = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $complaint_id, $user_id);
$delete_stmt->execute();

// Redirect back to complaint dashboard
header("Location: complaint_dashboard.php?deleted=1");
exit();
?>
