<?php
session_start();
require 'dbconnect.php';

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Check for complaint ID in GET
if (!isset($_GET['id'])) {
    header("Location: admin_complaint_dashboard.php");
    exit();
}

$complaint_id = intval($_GET['id']);
$complaint_num = isset($_GET['num']) ? intval($_GET['num']) : $complaint_id;

// Handle Save Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_reply'], $_POST['complaint_id'])) {
    $admin_reply = trim($_POST['admin_reply']);
    $update_sql = "UPDATE complaint_support SET Messages = ? WHERE Complaint_ID = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $admin_reply, $_POST['complaint_id']);
    $stmt->execute();

    header("Location: admin_view_complaint.php?id={$_POST['complaint_id']}&num={$complaint_num}&updated=1");
    exit();
}

// Fetch complaint with user info
$sql = "SELECT c.Complaint_ID, c.User_ID, c.Description, c.Status, c.Submitted_Date, c.Messages, u.Name, u.Type
        FROM complaint_support c
        JOIN user u ON c.User_ID = u.U_ID
        WHERE c.Complaint_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_complaint_dashboard.php?error=notfound");
    exit();
}

$complaint = $result->fetch_assoc();

// Status badge helper
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
<title>Admin View Complaint</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="header">
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a href="admin_complaint_dashboard.php" class="btn">Dashboard</a>
            <a href="logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="complaint-card no-hover form-narrow">
        <h2>Complaint #<?= $complaint_num ?> (<?= htmlspecialchars($complaint['Name']) ?> - <?= htmlspecialchars($complaint['Type']) ?>)</h2>
        <div class="complaint-meta">
            Submitted: <?= date("d M Y", strtotime($complaint['Submitted_Date'])) ?>
        </div>
        <div class="status-badge <?= getStatusClass($complaint['Status']) ?>">
            <?= htmlspecialchars($complaint['Status']) ?>
        </div>

        <?php if (!empty($complaint['Messages'])): ?>
            <span class="status-badge replied">✅ Replied</span>
        <?php endif; ?>

        <h3 class="section-title">Description</h3>
        <p><?= nl2br(htmlspecialchars($complaint['Description'])) ?></p>

        <!-- Admin reply form -->
        <h3 class="section-title">Admin Reply</h3>
        <form method="POST" class="admin-reply-form">
            <input type="hidden" name="complaint_id" value="<?= $complaint['Complaint_ID'] ?>">
            <textarea id="description" name="admin_reply" rows="6"><?= htmlspecialchars($complaint['Messages']) ?></textarea>
            <button type="submit" class="btn">Save Reply</button>
        </form>

        <div class="card-actions" style="margin-top:15px;">
            <a href="admin_complaint_dashboard.php" class="btn small danger">⬅ Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
