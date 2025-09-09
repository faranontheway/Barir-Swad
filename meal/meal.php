<?php
require 'dbconnect.php';

$sql = "SELECT Meal_ID, Name, Description, Proportion, Pricing, Cuisine, Status FROM meal";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Meal Menu</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container">
    <h1>🍴Meal Menu</h1>

    <?php if ($result->num_rows > 0): ?>
      <div class="meal-grid">
        <?php while($row = $result->fetch_assoc()): ?>
          <div class="meal-card">
            <img src="<?= strtolower(str_replace(' ', '-', $row['Name'])) ?>.jpg" 
                 alt="<?= htmlspecialchars($row['Name']) ?>" 
                 class="meal-photo">
            <h2><?= htmlspecialchars($row['Name']) ?></h2>
            <p><strong>Description:</strong> <?= htmlspecialchars($row['Description']) ?></p>
            <p><strong>Portion:</strong> <?= htmlspecialchars($row['Proportion']) ?></p>
            <p><strong>Cuisine:</strong> <?= htmlspecialchars($row['Cuisine']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($row['Status']) ?></p>
            <p class="price">৳<?= number_format($row['Pricing'], 2) ?></p>
            <a href="add_to_cart.php?meal_id=<?= $row['Meal_ID'] ?>" class="btn">Add to Cart</a>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p>No meals available.</p>
    <?php endif; ?>
  </div>
</body>
</html>