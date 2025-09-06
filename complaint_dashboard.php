<?php
session_start();
require 'dbconnect.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Complaint stats
$stats_sql = "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN Status = 'Open' THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN Status = 'In Progress' THEN 1 ELSE 0 END) AS inprogress_count,
                SUM(CASE WHEN Status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_count,
                SUM(CASE WHEN Status = 'Closed' THEN 1 ELSE 0 END) AS closed_count
              FROM complaint_support
              WHERE User_ID = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Recent complaints
$list_sql = "SELECT Complaint_ID, Description, Status, Submitted_Date, Messages
             FROM complaint_support
             WHERE User_ID = ?
             ORDER BY Submitted_Date DESC
             LIMIT 5";
$stmt = $conn->prepare($list_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$complaints = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complaint Dashboard</title>
<link rel="stylesheet" href="cook_styles.css">
<link rel="stylesheet" href="complaint_styles.css">
<link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
<style>
.status-inprogress { background-color: #ffc107; color: #212529; }
.no-data { text-align: center; padding: 20px; font-size: 16px; color: #666; background: #f9f9f9; border-radius: 10px; margin-top: 15px; }
</style>
</head>
<body>
<header class="header">
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a href="customer_dashboard.php" class="btn">Dashboard</a>
            <a href="logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="container">

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card"><h3>Total</h3><div class="number"><?= $stats['total'] ?: '—' ?></div></div>
        <div class="stat-card"><h3>Open</h3><div class="number"><?= $stats['open_count'] ?: '—' ?></div></div>
        <div class="stat-card"><h3>In Progress</h3><div class="number"><?= $stats['inprogress_count'] ?: '—' ?></div></div>
        <div class="stat-card"><h3>Resolved</h3><div class="number"><?= $stats['resolved_count'] ?: '—' ?></div></div>
        <div class="stat-card"><h3>Closed</h3><div class="number"><?= $stats['closed_count'] ?: '—' ?></div></div>
    </div>

    <!-- Complaint List -->
    <div class="complaints-section">
        <div class="dashboard-header">
            <h2>Recent Complaints and Notifications</h2>
            <a href="add_complaint.php" class="btn">➕ Add Complaint</a>
        </div>

        <?php if ($complaints->num_rows > 0): ?>
            <?php $serial = 1; ?>
            <?php while ($row = $complaints->fetch_assoc()): ?>
                <div class="complaint-card">
                    <h3>Complaint/Notifications #<?= $serial ?></h3>
                    <div class="complaint-meta">
                        Submitted: <?= date("d M Y", strtotime($row['Submitted_Date'])) ?>
                    </div>
                    <p><?= nl2br(htmlspecialchars(mb_strimwidth($row['Description'],0,100,"..."))) ?></p>

                    <?php 
                        $statusClass = '';
                        switch ($row['Status']) {
                            case 'Open': $statusClass = 'status-pending'; break;
                            case 'In Progress': $statusClass = 'status-inprogress'; break;
                            case 'Resolved': $statusClass = 'status-resolved'; break;
                            case 'Closed': $statusClass = 'status-rejected'; break;
                        }
                    ?>
                    <span class="status-badge <?= $statusClass ?>"><?= $row['Status'] ?></span>

                    <!-- Admin reply indicator -->
                    <?php if (!empty($row['Messages'])): ?>
                        <span class="status-badge" style="background:#17a2b8; margin-left:5px;">💬 Reply</span>
                    <?php endif; ?>

                    <div class="card-actions">
                        <a href="view_complaint.php?id=<?= $row['Complaint_ID'] ?>&num=<?= $serial ?>" class="btn small">👁 View</a>
                        <a href="edit_complaint.php?id=<?= $row['Complaint_ID'] ?>" class="btn small">✏️ Edit</a>
                        <a href="delete_complaint.php?id=<?= $row['Complaint_ID'] ?>" class="btn small danger" onclick="return confirm('Are you sure you want to delete this complaint?');">🗑 Delete</a>
                    </div>
                </div>
                <?php $serial++; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-data">😶 No complaints submitted yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
