<?php
session_start();
require 'dbconnect.php';

// Check if cook is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Cook') {
    header("Location: login.php");
    exit();
}

$cook_id = $_SESSION['user_id'];
$cook_name = $_SESSION['user_name'];
$message = '';
$error = '';

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $exp_years = intval($_POST['exp_years']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $phones = isset($_POST['phone']) ? array_filter(array_map('trim', (array)$_POST['phone'])) : []; // Handle multiple phones

    // Validate and format phone numbers
    $valid_phones = [];
    foreach ($phones as $phone) {
        if (empty($phone)) continue;
        // Ensure +880 prefix and exactly 14 characters
        if (preg_match('/^\+880[0-9]{10}$/', $phone) && strlen($phone) === 14) {
            $valid_phones[] = $phone;
        } else {
            $error = "Invalid phone number format. Must start with +880 and be exactly 14 characters (e.g., +8801712345678).";
            break;
        }
    }

    if (empty($valid_phones)) {
        $error = "At least one valid phone number is required.";
    } elseif (count($valid_phones) > 5) { // Limit to 5 numbers
        $error = "You can add a maximum of 5 phone numbers.";
    } else {
        // Validate current password
        $check_sql = "SELECT Password FROM user WHERE U_ID = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $cook_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $user_data = $result->fetch_assoc();

        if ($current_password !== $user_data['Password']) {
            $error = "Current password is incorrect.";
        } else {
            // Check if email is unique (excluding current user)
            $email_check_sql = "SELECT U_ID FROM user WHERE Email = ? AND U_ID != ?";
            $email_check_stmt = $conn->prepare($email_check_sql);
            $email_check_stmt->bind_param("si", $email, $cook_id);
            $email_check_stmt->execute();
            $email_result = $email_check_stmt->get_result();

            if ($email_result->num_rows > 0) {
                $error = "Email already exists. Please choose a different email.";
            } else {
                $conn->begin_transaction();
                try {
                    // Update user information
                    $password_to_use = !empty($new_password) ? $new_password : $current_password;
                    
                    $update_sql = "UPDATE user SET Name = ?, Email = ?, Address = ?, Exp_Years = ?, Password = ? WHERE U_ID = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("sssisi", $name, $email, $address, $exp_years, $password_to_use, $cook_id);
                    $update_stmt->execute();

                    // Update phone numbers
                    if (!empty($valid_phones)) {
                        // Remove existing phones
                        $delete_phone_sql = "DELETE FROM user_phone_no WHERE User_ID = ?";
                        $delete_phone_stmt = $conn->prepare($delete_phone_sql);
                        $delete_phone_stmt->bind_param("i", $cook_id);
                        $delete_phone_stmt->execute();

                        // Insert new phones
                        $phone_sql = "INSERT INTO user_phone_no (User_ID, Phone_No) VALUES (?, ?)";
                        $phone_stmt = $conn->prepare($phone_sql);
                        foreach ($valid_phones as $phone) {
                            $phone_stmt->bind_param("is", $cook_id, $phone);
                            $phone_stmt->execute();
                        }
                    }

                    $conn->commit();
                    
                    // Update session
                    $_SESSION['user_name'] = $name;
                    
                    $message = "Profile updated successfully!";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error updating profile: " . $e->getMessage();
                }
            }
        }
    }
}

// Get current user data
$user_sql = "SELECT u.*, up.Phone_No FROM user u LEFT JOIN user_phone_no up ON u.U_ID = up.User_ID WHERE u.U_ID = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $cook_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_all(MYSQLI_ASSOC); // Fetch all for multiple phones
$main_data = $user_data[0] ?? ['Name' => '', 'Exp_Years' => 0, 'Email' => '', 'Address' => '']; // Default if no data
$phones = array_column($user_data, 'Phone_No') ?: []; // Extract phones, default to empty array
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Barir Swad Cook</title>
    <link rel="stylesheet" href="cook_styles.css">
    <link rel="stylesheet" href="cook_profile_styles.css">
    <link href="https://fonts.googleapis.com/css2?family=DynaPuff:wght@400..700&family=Permanent+Marker&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="nav">
            <div class="logo">🥘Barir Swad</div>
            <nav class="nav-links">
                <a href="cook_dashboard.php" class="btn">Dashboard</a>
                <a href="cook_reviews.php" class="btn">My Reviews</a>
                <a href="logout.php" class="btn logout">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <div class="cook-avatar">
                <?= strtoupper(substr($main_data['Name'], 0, 1)) ?>
            </div>
            <h1><?= htmlspecialchars($cook_name) ?></h1>
            <div class="experience-badge">
                <?= $main_data['Exp_Years'] ?> Years Experience
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Edit Profile</h3>
            <form method="POST" onsubmit="return validateForm()">
                <div class="form-section">
                    <div class="section-title">Professional Information</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($main_data['Name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="exp_years">Years of Experience</label>
                            <input type="number" id="exp_years" name="exp_years" value="<?= $main_data['Exp_Years'] ?>" min="0" max="50" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($main_data['Email']) ?>" required>
                        </div>
                        <div class="form-group phone-group">
                            <label for="phone">Phone Number</label>
                            <div class="phone-inputs">
                                <?php foreach ($phones as $index => $phone): ?>
                                    <div class="phone-input-wrapper">
                                        <input type="tel" name="phone[]" value="<?= htmlspecialchars($phone) ?>" placeholder="+8801XXXXXXXXX" class="phone-input" required maxlength="14" pattern="^\+880[0-9]{10}$">
                                        <?php if ($index > 0 || (empty($phones) && $index > 0)): // Show remove link for all but the first input ?>
                                            <a href="#" class="remove-link" onclick="removePhoneField(this); return false;">Remove</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($phones)): ?>
                                    <div class="phone-input-wrapper">
                                        <input type="tel" name="phone[]" placeholder="+8801XXXXXXXXX" class="phone-input" required maxlength="14" pattern="^\+880[0-9]{10}$">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" onclick="addPhoneField()" class="btn-add">Add Phone</button>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" required><?= htmlspecialchars($main_data['Address']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">Security Settings</div>
                    <div class="password-section">
                        <div class="password-note">
                            Enter your current password to save changes. Leave new password blank to keep current password.
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password (Optional)</label>
                                <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="update_profile" class="submit-btn">
                    Update Profile
                </button>
            </form>
        </div>
    </div>

    <script>
        function addPhoneField() {
            const phoneContainer = document.querySelector('.phone-inputs');
            const phoneInputs = phoneContainer.getElementsByClassName('phone-input');
            if (phoneInputs.length >= 5) { // Limit to 5 phones
                alert("You can add a maximum of 5 phone numbers.");
                return;
            }
            const wrapper = document.createElement('div');
            wrapper.className = 'phone-input-wrapper';
            const newInput = document.createElement('input');
            newInput.type = 'tel';
            newInput.name = 'phone[]';
            newInput.placeholder = '+8801XXXXXXXXX';
            newInput.className = 'phone-input';
            newInput.required = true;
            newInput.maxlength = '14';
            newInput.pattern = '^\\+880[0-9]{10}$';
            const removeLink = document.createElement('a');
            removeLink.href = '#';
            removeLink.className = 'remove-link';
            removeLink.textContent = 'Remove';
            removeLink.onclick = function() { removePhoneField(this); return false; };
            wrapper.appendChild(newInput);
            wrapper.appendChild(removeLink);
            phoneContainer.appendChild(wrapper);
        }

        function removePhoneField(link) {
            const wrapper = link.parentElement;
            wrapper.remove();
            // Ensure at least one input remains (optional, remove if not needed)
            const phoneInputs = document.querySelectorAll('.phone-input');
            if (phoneInputs.length === 0) {
                addPhoneField(); // Add back one input if all are removed
            }
        }

        function validateForm() {
            const phoneInputs = document.querySelectorAll('.phone-input');
            for (let input of phoneInputs) {
                if (!input.value.match(/^\+880[0-9]{10}$/) || input.value.length !== 14) {
                    alert("Each phone number must start with +880 and be exactly 14 characters long (e.g., +8801712345678).");
                    input.focus();
                    return false;
                }
            }
            return true;
        }
    </script>
</body>
</html>