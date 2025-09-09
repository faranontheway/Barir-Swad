<?php
session_start();
require 'dbconnect.php';

$message = '';
$success = false;

// Handle catering booking submission
if ($_POST && isset($_POST['book_catering'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $message = "Please login to book catering services.";
    } else {
        $customer_id = $_SESSION['user_id'];
        $event_name = $_POST['event_name'];
        $event_date = $_POST['event_date'];
        $event_time = $_POST['event_time'];
        $event_location = $_POST['event_location'];
        $number_of_people = $_POST['number_of_people'];
        $contact_person = $_POST['contact_person'];
        $contact_phone = $_POST['contact_phone'];
        $special_requirements = $_POST['special_requirements'] ?? '';
        
        // Get selected meals
        $selected_meals = [];
        if (isset($_POST['meals']) && is_array($_POST['meals'])) {
            foreach ($_POST['meals'] as $meal_id) {
                if (!empty($meal_id) && isset($_POST['meal_quantity_' . $meal_id]) && $_POST['meal_quantity_' . $meal_id] > 0) {
                    $selected_meals[$meal_id] = (int)$_POST['meal_quantity_' . $meal_id];
                }
            }
        }
        
        // Validate date is not in the past
        $today = date('Y-m-d');
        if ($event_date < $today) {
            $message = "Event date cannot be in the past.";
        } elseif (empty($selected_meals)) {
            $message = "Please select at least one meal for your catering service.";
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert catering booking
                $sql = "INSERT INTO catering_services (Customer_ID, Event_Name, Event_Date, Event_Time, Event_Location, Number_of_People, Contact_Person, Contact_Phone, Special_Requirements) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssisss", $customer_id, $event_name, $event_date, $event_time, $event_location, $number_of_people, $contact_person, $contact_phone, $special_requirements);
                
                if ($stmt->execute()) {
                    $catering_id = $stmt->insert_id;
                    
                    // Insert selected meals
                    $meal_sql = "INSERT INTO catering_has_meals (Catering_ID, Meal_ID, Quantity_Per_Person, Total_Quantity, Unit_Price, Total_Price) VALUES (?, ?, ?, ?, ?, ?)";
                    $meal_stmt = $conn->prepare($meal_sql);
                    
                    foreach ($selected_meals as $meal_id => $quantity_per_person) {
                        // Get meal price
                        $price_sql = "SELECT Pricing FROM meal WHERE Meal_ID = ?";
                        $price_stmt = $conn->prepare($price_sql);
                        $price_stmt->bind_param("i", $meal_id);
                        $price_stmt->execute();
                        $price_result = $price_stmt->get_result();
                        $meal_price = $price_result->fetch_assoc()['Pricing'];
                        
                        $total_quantity = $quantity_per_person * $number_of_people;
                        $total_price = $meal_price * $total_quantity;
                        
                        $meal_stmt->bind_param("iididi", $catering_id, $meal_id, $quantity_per_person, $total_quantity, $meal_price, $total_price);
                        $meal_stmt->execute();
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $message = "Catering booking submitted successfully! Your booking ID is #$catering_id. We will contact you within 24 hours.";
                    $success = true;
                } else {
                    throw new Exception("Error submitting booking");
                }
            } catch (Exception $e) {
                // Rollback transaction
                $conn->rollback();
                $message = "Error submitting booking. Please try again.";
            }
        }
    }
}

// Get all available meals
$meals_sql = "SELECT * FROM meal WHERE Status != 'Unavailable' ORDER BY Cuisine, Name";
$meals_result = $conn->query($meals_sql);

// Get sample catering packages (from meals table)
$packages_sql = "SELECT * FROM meal WHERE Cuisine IN ('Bengali', 'Indian', 'Chinese') ORDER BY Pricing LIMIT 6";
$packages_result = $conn->query($packages_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catering Services - Barir Swad</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation -->
    <header class="navbar">
        <div class="container">
            <div class="logo">
                <img src="welcomeart.png" alt="Barir Swad Logo">
                <h2>Barir Swad</h2>
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="meal.php">Menu</a>
                <a href="catering.php">Catering</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_type'] == 'Customer'): ?>
                        <a href="customer_dashboard.php">Dashboard</a>
                    <?php elseif ($_SESSION['user_type'] == 'Cook'): ?>
                        <a href="cook_dashboard.php">Dashboard</a>
                    <?php endif; ?>
                    <a href="admin_logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn">Login / Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="catering-hero">
        <div>
            <h1>Catering Services</h1>
            <p>Let us make your special events memorable with authentic homemade flavors</p>
        </div>
    </section>

    <div class="services-section">
        <!-- Pricing Information -->
        <div class="pricing-info">
            <h3>Our Catering Services Include</h3>
            <div class="pricing-features">
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    <span>Professional food preparation</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    <span>Fresh, homemade quality</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    <span>Customizable menu options</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    <span>Professional serving setup</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    <span>Advance booking available</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    <span>Flexible payment options</span>
                </div>
            </div>
        </div>

        <!-- Service Types -->
        <h2 style="text-align: center; margin-bottom: 30px; color: #333;">Our Catering Services</h2>
        <div class="services-grid">
            <div class="service-card">
                <h3>Wedding Catering</h3>
                <ul>
                    <li>Traditional Bengali wedding menu</li>
                    <li>Buffet style serving</li>
                    <li>Decorative food presentation</li>
                    <li>Minimum 50 guests</li>
                    <li>Starting from ৳250 per person</li>
                </ul>
            </div>
            
            <div class="service-card">
                <h3>Corporate Events</h3>
                <ul>
                    <li>Professional lunch meetings</li>
                    <li>Office parties and celebrations</li>
                    <li>Continental and Bengali options</li>
                    <li>Minimum 20 guests</li>
                    <li>Starting from ৳200 per person</li>
                </ul>
            </div>
            
            <div class="service-card">
                <h3>Private Parties</h3>
                <ul>
                    <li>Birthday parties and family gatherings</li>
                    <li>Anniversary celebrations</li>
                    <li>Customizable menu</li>
                    <li>Minimum 15 guests</li>
                    <li>Starting from ৳180 per person</li>
                </ul>
            </div>
        </div>

        <!-- Sample Menu Packages -->
        <h2 style="text-align: center; margin-bottom: 30px; color: #333;">Sample Menu Items</h2>
        <div class="packages-section">
            <div class="packages-grid">
                <?php if ($packages_result->num_rows > 0): ?>
                    <?php while($package = $packages_result->fetch_assoc()): ?>
                        <div class="package-card">
                            <div class="cuisine-tag"><?= htmlspecialchars($package['Cuisine']) ?></div>
                            <h4><?= htmlspecialchars($package['Name']) ?></h4>
                            <p><?= htmlspecialchars($package['Description']) ?></p>
                            <div class="package-price">৳<?= number_format($package['Pricing'], 0) ?></div>
                            <small>per person</small>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Booking Form -->
        <div class="booking-section">
            <h2 style="text-align: center; margin-bottom: 30px; color: #333;">Book Your Catering Service</h2>
            
            <?php if ($message): ?>
                <div class="message <?= $success ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="message error">
                    Please <a href="login.php" style="color: #ff6b35;">login</a> to book catering services.
                </div>
            <?php else: ?>
                <form method="POST" class="booking-form" id="cateringForm">
                    <div class="form-group">
                        <label for="event_name">Event Name *</label>
                        <input type="text" id="event_name" name="event_name" required 
                               placeholder="e.g., Wedding Reception, Birthday Party">
                    </div>
                    
                    <div class="form-group">
                        <label for="number_of_people">Number of People *</label>
                        <input type="number" id="number_of_people" name="number_of_people" 
                               min="15" max="500" required placeholder="Minimum 15 guests">
                    </div>
                    
                    <!-- Meal Selection Section -->
                    <div class="meal-selection">
                        <h3>Select Meals for Your Event *</h3>
                        <p>Choose meals and specify how many portions per person</p>
                        
                        <div class="meal-grid">
                            <?php if ($meals_result->num_rows > 0): ?>
                                <?php $meals_result->data_seek(0); // Reset result pointer ?>
                                <?php while($meal = $meals_result->fetch_assoc()): ?>
                                    <div class="meal-item" data-meal-id="<?= $meal['Meal_ID'] ?>" data-meal-price="<?= $meal['Pricing'] ?>">
                                        <div class="meal-info">
                                            <h4><?= htmlspecialchars($meal['Name']) ?></h4>
                                            <p><?= htmlspecialchars(substr($meal['Description'], 0, 50)) ?>...</p>
                                            <span class="meal-price">৳<?= number_format($meal['Pricing'], 0) ?></span>
                                            <small> per person</small>
                                        </div>
                                        <div class="meal-controls">
                                            <input type="checkbox" name="meals[]" value="<?= $meal['Meal_ID'] ?>" class="meal-checkbox">
                                            <input type="number" name="meal_quantity_<?= $meal['Meal_ID'] ?>" 
                                                   min="1" max="5" value="1" step="1" class="quantity-input" 
                                                   placeholder="Qty" disabled>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="cost-summary" id="costSummary" style="display: none;">
                            <h4>Cost Estimate</h4>
                            <div class="cost-breakdown" id="costBreakdown"></div>
                            <div style="font-size: 18px; font-weight: bold; margin-top: 10px;" id="totalCost"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_date">Event Date *</label>
                        <input type="date" id="event_date" name="event_date" required 
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="event_time">Event Time *</label>
                        <input type="time" id="event_time" name="event_time" required>
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label for="event_location">Event Location *</label>
                        <textarea id="event_location" name="event_location" required 
                                  placeholder="Full address of the event venue"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_person">Contact Person *</label>
                        <input type="text" id="contact_person" name="contact_person" required 
                               placeholder="Primary contact for event">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone *</label>
                        <input type="tel" id="contact_phone" name="contact_phone" required 
                               placeholder="e.g., 01XXXXXXXXX">
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label for="special_requirements">Special Requirements</label>
                        <textarea id="special_requirements" name="special_requirements" 
                                  placeholder="Any dietary restrictions, special requests, or additional information"></textarea>
                    </div>
                    
                    <div class="form-group form-group-full">
                        <button type="submit" name="book_catering" class="btn-catering">
                            Submit Catering Request
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mealCheckboxes = document.querySelectorAll('.meal-checkbox');
            const quantityInputs = document.querySelectorAll('.quantity-input');
            const peopleInput = document.getElementById('number_of_people');
            const costSummary = document.getElementById('costSummary');
            const costBreakdown = document.getElementById('costBreakdown');
            const totalCostDiv = document.getElementById('totalCost');

            // Handle meal selection
            mealCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const mealItem = this.closest('.meal-item');
                    const quantityInput = mealItem.querySelector('.quantity-input');
                    
                    if (this.checked) {
                        mealItem.classList.add('selected');
                        quantityInput.disabled = false;
                    } else {
                        mealItem.classList.remove('selected');
                        quantityInput.disabled = true;
                        quantityInput.value = 1;
                    }
                    
                    updateCostEstimate();
                });
            });

            // Handle quantity changes
            quantityInputs.forEach(input => {
                input.addEventListener('input', updateCostEstimate);
            });

            // Handle people count changes
            peopleInput.addEventListener('input', updateCostEstimate);

            function updateCostEstimate() {
                const people = parseInt(peopleInput.value) || 0;
                if (people < 15) {
                    costSummary.style.display = 'none';
                    return;
                }

                let totalCost = 0;
                let breakdown = '';
                let hasSelectedMeals = false;

                mealCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        hasSelectedMeals = true;
                        const mealItem = checkbox.closest('.meal-item');
                        const mealPrice = parseFloat(mealItem.dataset.mealPrice);
                        const mealName = mealItem.querySelector('h4').textContent;
                        const quantityInput = mealItem.querySelector('.quantity-input');
                        const quantity = parseInt(quantityInput.value) || 1;
                        
                        const itemTotal = mealPrice * quantity * people;
                        totalCost += itemTotal;
                        
                        breakdown += `<div>${mealName} x${quantity} per person: ৳${itemTotal.toLocaleString()}</div>`;
                    }
                });

                if (hasSelectedMeals && people >= 15) {
                    costBreakdown.innerHTML = breakdown;
                    totalCostDiv.innerHTML = `Total Estimated Cost: ৳${totalCost.toLocaleString()}`;
                    costSummary.style.display = 'block';
                } else {
                    costSummary.style.display = 'none';
                }
            }

            // Set minimum date to tomorrow
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const dateInput = document.getElementById('event_date');
            dateInput.min = tomorrow.toISOString().split('T')[0];
        });
    </script>
</body>
</html>