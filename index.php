<?php
require 'dbconnect.php';

// fetch meals
$sql = "SELECT Meal_ID, Name, Description, Proportion, Pricing, Cuisine FROM meal";
$result = $conn->query($sql);

if (!$result) {
    die("❌ Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Barir Swad - Home</title>
  <link rel="stylesheet" href="index_styles.css">
</head>
<body>

  <!-- 🔹 Header / Navbar -->
  <header class="navbar">
    <div class="logo">
      <img src="welcomeart.png" alt="Barir Swad Logo">
      <h2>Barir Swad</h2>
    </div>
    <nav>
      <a href="login.php">Sign up</a>
      <a href="catering.php">Catering Services</a>
    </nav>
  </header>

  <!-- 🔹 Hero Section -->
  <section class="hero">
    <img src="welcomeart.png" alt="Welcome Art" class="hero-art">
    <h1>Welcome to Barir Swad</h1>
    <h2>Authentic homemade flavors, served with love </h2>
    <p><a href="#menu" class="btn">Browse Menu</a></p>
  </section>

  <!-- 🔹 Menu Section -->
  <section id="menu" class="menu-section">
    <h2>Our Meals</h2>
    <div class="meal-grid">
      <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
         <div class="meal-card">
           <img src="<?= strtolower(str_replace(' ', '-', $row['Name'])) ?>.jpg" 
                alt="<?= htmlspecialchars($row['Name']) ?>" 
                class="meal-photo">
            <h3><?= htmlspecialchars($row['Name']) ?></h3>
            <p><strong>Description:</strong> <?= htmlspecialchars($row['Description']) ?></p>
            <p><strong>Portion:</strong> <?= htmlspecialchars($row['Proportion']) ?></p>
            <p><strong>Cuisine:</strong> <?= htmlspecialchars($row['Cuisine']) ?></p>
            <p class="price">৳ <?= number_format($row['Pricing'], 2) ?></p>
            <a href="add_to_cart.php?meal_id=<?= $row['Meal_ID'] ?>" class="btn">Add to Cart</a>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No meals available.</p>
      <?php endif; ?>
    </div>
  </section>

</body>
</html>