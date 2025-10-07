<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch cart items
$stmt = $conn->prepare("
    SELECT c.cart_id, c.product_id, c.quantity, p.price, p.name 
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    echo "Your cart is empty.";
    exit;
}

// Calculate total amount
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_method = $_POST['delivery_method'] ?? 'collection';
    $payment_method = $_POST['payment_method'] ?? 'pay_in_store';

    // 1️⃣ Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, delivery_method, order_status, payment_status) 
        VALUES (?, ?, ?, 'pending', 'pending')
    ");
    $stmt->bind_param("ids", $user_id, $total_amount, $delivery_method);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // 2️⃣ Add items to order_items
    foreach ($cart_items as $item) {
        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();

        // Optional: decrease inventory stock
        $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
        $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt->execute();
    }

    // 3️⃣ Handle payment record
    $stmt = $conn->prepare("
        INSERT INTO payments (order_id, method, amount, status) 
        VALUES (?, ?, ?, ?)
    ");
    $payment_status = ($payment_method === 'pay_in_store') ? 'pending' : 'pending';
    $stmt->bind_param("isds", $order_id, $payment_method, $total_amount, $payment_status);
    $stmt->execute();

    // 4️⃣ Handle delivery (optional, can be updated later)
    $delivery_address = $_POST['delivery_address'] ?? null;
    if ($delivery_method === 'delivery' && $delivery_address) {
        $stmt = $conn->prepare("
            INSERT INTO delivery (order_id, courier, delivery_address, delivery_status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $courier = 'To be assigned';
        $stmt->bind_param("iss", $order_id, $courier, $delivery_address);
        $stmt->execute();
    }

    // 5️⃣ Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Redirect to success page
    header("Location: success.php?order_id=$order_id");
    exit;
}
?>
