<?php
session_start();
require 'db.php'; // your database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $size = $_POST['size'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Insert into cart
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, size, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $product_id, $size, $start_date, $end_date);
    $stmt->execute();
    $stmt->close();

    // Redirect to cart
    header('Location: cart.php');
    exit;
}
?>
