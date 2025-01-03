<?php
session_start();
$cart = $_SESSION['cart'] ?? [];
$total = 0;

// Handle form submission for updating quantity or removing items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove'])) {
        // Remove item from cart
        $indexToRemove = $_POST['remove'];
        if (isset($cart[$indexToRemove])) {
            unset($cart[$indexToRemove]);
        }
    } elseif (isset($_POST['action'])) {
        $index = $_POST['index'];
        if (isset($cart[$index])) {
            if ($_POST['action'] === 'increase') {
                $cart[$index]['quantity']++;
            } elseif ($_POST['action'] === 'decrease') {
                $cart[$index]['quantity'] = max(1, $cart[$index]['quantity'] - 1); // Ensure quantity is at least 1
            }
        }
    }
    $_SESSION['cart'] = $cart; // Save updated cart in session
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <link rel="stylesheet" href="cart.css">
   
</head>
<body>
<header class="header">
    <div class="container">
        <h1 class="logo">VirtualTailor</h1>
        <nav class="navbar">
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="index.html">Services</a></li>
                <li><a href="index.html">Products</a></li>
                <li><a href="index.html">About</a></li>
                <li><a href="index.html">Contact</a></li>
                <li><a href="Cart.php">Cart</a></li>
            </ul>
        </nav>
    </div>
</header>
<h1><center>Your Shopping Cart</center></h1>
<?php if (empty($cart)): ?>
    <p>Your cart is empty. <a href="index.html">Go back to products</a></p>
<?php else: ?>
    <form method="POST">
        <table border="1" style="width: 100%; margin-top: 20px;">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart as $index => $product): ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" width="100"></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td>₹<?= number_format($product['price'], 2) ?></td>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <!-- Decrease Button -->
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="index" value="<?= $index ?>">
                                    <button type="submit" name="action" value="decrease" <?= $product['quantity'] <= 1 ? 'disabled' : '' ?> style="padding: 5px;">-</button>
                                </form>
                                <!-- Quantity Display -->
                                <input type="number" value="<?= $product['quantity'] ?>" readonly style="width: 50px; text-align: center; margin: 0 5px;">
                                <!-- Increase Button -->
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="index" value="<?= $index ?>">
                                    <button type="submit" name="action" value="increase" style="padding: 5px;">+</button>
                                </form>
                            </div>
                        </td>
                        <td>₹<?= number_format($product['price'] * $product['quantity'], 2) ?></td>
                        <td>
                            <form method="POST" style="margin: 0;">
                                <button type="submit" name="remove" value="<?= $index ?>" style="color: red;">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php $total += $product['price'] * $product['quantity']; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: bold;">Total:</td>
                    <td style="text-align: right;">₹<?= number_format($total, 2) ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: right;">
                        <a href="payment.html" style="padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; font-size: 16px;">
                            Proceed to Checkout
                        </a>
                    </td>
                </tr>
            </tfoot>
        </table>
    </form>
<?php endif; ?>
</body>
</html>
