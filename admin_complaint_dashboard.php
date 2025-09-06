<?php
session_start();
require 'dbconnect.php';

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Complaint stats
$stats_sql = "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN Status = 'Open' THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN Status = 'In Progress' THEN 1 ELSE 0 END) AS inprogress_count,
                SUM(CASE WHEN Status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_count,
                SUM(CASE WHEN Status = 'Closed' THEN 1 ELSE 0 END) AS closed_count
              FROM complaint_support";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Fetch all complaints with user info
$list_sql = "SELECT c.Complaint_ID, c.User_ID, c.Description, c.Status, c.Submitted_Date, c.Messages, u.Name, u.Type
             FROM complaint_support c
             JOIN user u ON c.User_ID = u.U_ID
             ORDER BY c.Submitted_Date DESC";
$complaints_result = $conn->query($list_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Complaint Dashboard</title>
<link rel="stylesheet" href="cook_styles.css">
<link rel="stylesheet" href="complaint_styles.css">
<link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
<style>
    select { padding: 5px 8px; border-radius: 5px; border: 1px solid #ccc; cursor: pointer; }
</style>
</head>
<body>
<header class="header">
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a href="admin_dash.php" class="btn">Admin Panel</a>
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
            <h2>All Complaints</h2>
        </div>

        <?php if ($complaints_result->num_rows > 0): ?>
            <?php $serial = 1; ?>
            <?php while ($row = $complaints_result->fetch_assoc()): ?>
                <?php
                    $statusClass = '';
                    switch ($row['Status']) {
                        case 'Open': $statusClass = 'status-pending'; break;
                        case 'In Progress': $statusClass = 'status-inprogress'; break;
                        case 'Resolved': $statusClass = 'status-resolved'; break;
                        case 'Closed': $statusClass = 'status-rejected'; break;
                    }
                ?>
                <div class="complaint-card">
                    <h3>Complaint #<?= $serial ?> (<?= htmlspecialchars($row['Name']) ?> - <?= htmlspecialchars($row['Type']) ?>)</h3>
                    <div class="complaint-meta">
                        Submitted: <?= date("d M Y", strtotime($row['Submitted_Date'])) ?>
                    </div>
                    <p><?= nl2br(htmlspecialchars(mb_strimwidth($row['Description'],0,100,"..."))) ?></p>

                    <!-- Status badge -->
                    <span class="status-badge <?= $statusClass ?>"><?= $row['Status'] ?></span>

                    <?php if (!empty($row['Messages'])): ?>
                        <span class="status-badge replied">✅ Replied</span>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="card-actions">
                        <a href="admin_view_complaint.php?id=<?= $row['Complaint_ID'] ?>&num=<?= $serial ?>" class="btn small">👁 View / 💬 Reply</a>
                                                <a href="admin_delete_complaint.php?id=<?= $row['Complaint_ID'] ?>" class="btn small danger" onclick="return confirm('Are you sure you want to delete this complaint?');">🗑 Delete</a>

                        <!-- Status dropdown -->
                        <form action="admin_update_status.php" method="POST" style="display:inline-block;">
                            <input type="hidden" name="complaint_id" value="<?= $row['Complaint_ID'] ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="Open" <?= $row['Status']=='Open'?'selected':'' ?>>Open</option>
                                <option value="In Progress" <?= $row['Status']=='In Progress'?'selected':'' ?>>In Progress</option>
                                <option value="Resolved" <?= $row['Status']=='Resolved'?'selected':'' ?>>Resolved</option>
                                <option value="Closed" <?= $row['Status']=='Closed'?'selected':'' ?>>Closed</option>
                            </select>
                        </form>

                    </div>
                </div>
                <?php $serial++; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-data">😶 No complaints found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
