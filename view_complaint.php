<?php
session_start();
require 'dbconnect.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Check for complaint ID in GET
if (!isset($_GET['id'])) {
    header("Location: complaint_dashboard.php");
    exit();
}
$complaint_id = intval($_GET['id']);
$complaint_num = isset($_GET['num']) ? intval($_GET['num']) : $complaint_id;

// Fetch the complaint
$sql = "SELECT * FROM complaint_support WHERE Complaint_ID = ? AND User_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: complaint_dashboard.php?error=notfound");
    exit();
}

$complaint = $result->fetch_assoc();

// Helper function to get status badge class
function getStatusClass($status) {
    switch ($status) {
        case 'Open': return 'status-pending';
        case 'In Progress': return 'status-inprogress';
        case 'Resolved': return 'status-resolved';
        case 'Closed': return 'status-rejected';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Complaint</title>
<link rel="stylesheet" href="cook_styles.css">
<link rel="stylesheet" href="complaint_styles.css">
<link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
</head>
<body>
<header class="header">
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a href="complaint_dashboard.php" class="btn">Dashboard</a>
            <a href="edit_complaint.php?id=<?= $complaint_id ?>" class="btn">✏️ Edit</a>
            <a href="logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="complaint-card form-narrow">
        <h2>Complaint #<?= $complaint_num ?></h2>
        <div class="complaint-meta">
            Submitted: <?= date("d M Y", strtotime($complaint['Submitted_Date'])) ?>
        </div>
        <div class="status-badge <?= getStatusClass($complaint['Status']) ?>">
            <?= htmlspecialchars($complaint['Status']) ?>
        </div>

        <h3 class="section-title">Description</h3>
        <p><?= nl2br(htmlspecialchars($complaint['Description'])) ?></p>

        <?php if (!empty($complaint['Messages'])): ?>
            <h3 class="section-title">Messages</h3>
            <p><?= nl2br(htmlspecialchars($complaint['Messages'])) ?></p>
        <?php endif; ?>

        <div class="card-actions">
            <a href="complaint_dashboard.php" class="btn small danger">⬅ Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
