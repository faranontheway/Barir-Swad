<?php
session_start();
require 'dbconnect.php';

// check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);

    if (!empty($description)) {
        $sql = "INSERT INTO complaint_support (User_ID, Description, Status, Submitted_Date) 
                VALUES (?, ?, 'Open', CURDATE())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $description);

        if ($stmt->execute()) {
            header("Location: complaint_dashboard.php");
            exit();
        } else {
            $error = "Error submitting complaint.";
        }
    } else {
        $error = "Description cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Complaint</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
</head>
<body>
<header class="header">
    <div class="nav">
        <div class="logo">🥘Barir Swad</div>
        <nav class="nav-links">
            <a href="complaint_dashboard.php" class="btn active">Complaints</a>
            <a href="logout.php" class="btn logout">Logout</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="complaint-card no-hover form-narrow">
        <h2>➕ Add Complaint</h2>
        <?php if (!empty($error)): ?>
            <p style="color:red"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="description">Description:</label>
            <textarea name="description" id="description" required></textarea>

            <div class="card-actions">
                <button type="submit" class="btn small">Submit</button>
                <a href="complaint_dashboard.php" class="btn small danger">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
