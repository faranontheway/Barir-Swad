<?php
session_start();
require 'dbconnect.php';

// check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// get complaint id
if (!isset($_GET['id'])) {
    header("Location: complaint_dashboard.php");
    exit();
}
$complaint_id = intval($_GET['id']);

// fetch the complaint
$sql = "SELECT * FROM complaint_support WHERE Complaint_ID = ? AND User_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Complaint not found.";
    exit();
}

$complaint = $result->fetch_assoc();
$error = '';

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);

    if (!empty($description)) {
        $update_sql = "UPDATE complaint_support SET Description = ? WHERE Complaint_ID = ? AND User_ID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sii", $description, $complaint_id, $user_id);

        if ($update_stmt->execute()) {
            header("Location: complaint_dashboard.php");
            exit();
        } else {
            $error = "Error updating complaint.";
        }
    } else {
        $error = "Description cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Complaint</title>
    <link rel="stylesheet" href="complaint_styles.css">
    <link rel="stylesheet" href="cook_styles.css">
    <link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
</head>
<body>
<header class="header">
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a href="../logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="complaint-card no-hover form-narrow">
        <h2>✏️ Edit Complaint</h2>
        <?php if (!empty($error)): ?>
            <p style="color:red"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="description">Description:</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($complaint['Description']) ?></textarea>

            <div class="card-actions">
                <button type="submit" class="btn small">Update</button>
                <a href="complaint_dashboard.php" class="btn small danger">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
