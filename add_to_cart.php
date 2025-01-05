<?php
session_start();

// Ensure the cart is initialized
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get product data from the GET request
if (isset($_GET['product_name'], $_GET['product_price'], $_GET['product_image'])) {
    $productName = $_GET['product_name'];
    $productPrice = (float)$_GET['product_price'];
    $productImage = $_GET['product_image'];

    // Check if the product already exists in the cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['name'] === $productName) {
            $item['quantity']++;
            $found = true;
            break;
        }
    }

    // If the product is not in the cart, add it
    if (!$found) {
        $_SESSION['cart'][] = [
            'name' => $productName,
            'price' => $productPrice,
            'image' => $productImage,
            'quantity' => 1,
        ];
    }

    // Respond with a success message
    echo json_encode(["status" => "success", "message" => "Product added to cart"]);
} else {
    // If product data is invalid
    echo json_encode(["status" => "error", "message" => "Invalid product data"]);
}
?>
