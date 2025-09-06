<?php
session_start();
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    echo "<h2 style='text-align:center; color:#BA5448;'>🛒 Your cart is empty!</h2>";
    echo "<p style='text-align:center;'><a href='meal.php' style='color:#D6877F; font-weight:bold;'>🍴 Back to Menu</a></p>";
    exit;
}

// Update quantities
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['qty'] as $meal_id => $qty) {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$meal_id]); // remove item
        } else {
            $_SESSION['cart'][$meal_id]['qty'] = $qty;
        }
    }
    header("Location: view_cart.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Cart</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #FFF8EE; /* lily */
      margin: 30px;
      color: #953029; /* hibiscus */
    }

    .cart-container {
      width: 80%;
      margin: auto;
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      border: 2px solid #E2A48A; /* desert rose */
      box-shadow: 0 4px 12px rgba(149, 48, 41, 0.15);
    }

    h1 {
      text-align: center;
      color: #953029; /* hibiscus */
      margin-bottom: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 14px;
      border-bottom: 1px solid #E2A48A; /* desert rose */
      text-align: center;
    }

    th {
      background: #bb3f31ff; /* rosewood */
      color: #FFF8EE; /* lily */
      font-size: 1.1em;
    }

    tr:nth-child(even) {
      background: #FFF8EE;
    }

    tr:hover {
      background: #E2A48A;
      color: #fff;
    }

    input[type="number"] {
      width: 60px;
      padding: 5px;
      border: 1px solid #BA5448; /* dahlia */
      border-radius: 5px;
      text-align: center;
    }

    .btn {
      background: #D6877F; /* rosewood */
      color: #FFF8EE;
      padding: 10px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
      border: none;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .btn:hover {
      background: #BA5448; /* dahlia */
    }

    .checkout-btn, .back-btn {
      margin: 15px 10px 0;
      display: inline-block;
      padding: 12px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s ease;
    }

    .checkout-btn {
      background: #D6877F; /* rosewood */
      color: #FFF8EE;
    }

    .checkout-btn:hover {
      background: #BA5448; /* dahlia */
    }

    .back-btn {
      background: #953029; /* hibiscus */
      color: #FFF8EE;
    }

    .back-btn:hover {
      background: #BA5448; /* dahlia */
    }

    h3 {
      text-align: right;
      margin-top: 15px;
      color: #953029; /* hibiscus */
    }
  </style>
</head>
<body>
  <div class="cart-container">
    <h1>🛒 My Cart</h1>
    <form method="post" action="view_cart.php">
      <table>
        <tr>
          <th>Meal</th>
          <th>Price</th>
          <th>Quantity</th>
          <th>Total</th>
        </tr>
        <?php 
        $grand_total = 0;
        foreach ($_SESSION['cart'] as $meal_id => $item): 
          $total = $item['price'] * $item['qty'];
          $grand_total += $total;
        ?>
        <tr>
          <td><?= htmlspecialchars($item['name']) ?></td>
          <td>৳ <?= number_format($item['price'],2) ?></td>
          <td>
            <input type="number" name="qty[<?= $meal_id ?>]" value="<?= $item['qty'] ?>" min="0">
          </td>
          <td>৳ <?= number_format($total,2) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <h3>Grand Total: ৳ <?= number_format($grand_total,2) ?></h3>
      <button type="submit" class="btn">Update Cart</button>
    </form>
    <div style="text-align:center;">
      <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
      <a href="meal.php" class="back-btn">🍴 Back to Menu</a>
    </div>
  </div>
</body>
</html>