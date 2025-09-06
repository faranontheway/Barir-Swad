<?php
session_start();
require 'dbconnect.php';

// Only allow logged-in admins
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if user exists
    $stmt = $conn->prepare("SELECT U_ID, Type FROM user WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if ($user['Type'] === 'Admin') {
            $message = "This user is already an admin.";
        } else {
            // Update role to Admin
            $update = $conn->prepare("UPDATE user SET Type = 'Admin' WHERE U_ID = ?");
            $update->bind_param("i", $user['U_ID']);
            if ($update->execute()) {
                $message = "User promoted to Admin successfully!";
            } else {
                $message = "Error promoting user.";
            }
        }
    } else {
        $message = "No user found with this email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Create Admin</h2>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="email">User Email:</label>
        <input type="email" name="email" required>
        <button type="submit">Promote to Admin</button>
    </form>

    <p><a href="admin_dash.php">Back to Dashboard</a></p>
</body>
</html>
