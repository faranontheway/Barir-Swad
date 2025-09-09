<?php
session_start();
require_once "dbconnect.php";

if (!isset($_SESSION['User_ID'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['User_ID'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['Complaint_Description']);

    if (empty($description)) {
        $_SESSION['message'] = "Description cannot be empty.";
        $_SESSION['msg_type'] = "error";
    } else {
        $status = "Pending";
        $message = null;

        $sql = "INSERT INTO complaint_support (User_ID, Complaint_Description, Complaint_Status, Complaint_Message) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $user_id, $description, $status, $message);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Complaint submitted successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: complaint_dashboard.php");
            exit();
        } else {
            $_SESSION['message'] = "Error submitting complaint.";
            $_SESSION['msg_type'] = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Complaint</title>
    <link rel="stylesheet" href="cook_styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Submit a Complaint</h2>

        <!-- Session Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="msg <?= $_SESSION['msg_type'] ?>">
                <?= $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['msg_type']); ?>
        <?php endif; ?>

        <form method="post">
            <label for="Complaint_Description">Description</label>
            <textarea id="Complaint_Description" name="Complaint_Description" required></textarea>

            <button type="submit" class="btn-action">Submit</button>
            <a href="complaint_dashboard.php" class="btn-action">Cancel</a>
        </form>
    </div>
</body>
</html>
