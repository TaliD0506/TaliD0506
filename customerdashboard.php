<?php
session_start();
require 'db.php'; // DB connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info for greeting
$userQuery = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

// Fetch purchases
$sql_orders = "
    SELECT o.order_id, o.total_amount, o.order_status, o.created_at, 
           GROUP_CONCAT(p.name SEPARATOR ', ') as products
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";
$stmt = $conn->prepare($sql_orders);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$purchases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch bookings (rentals)
$sql_bookings = "
    SELECT b.booking_id, b.start_date, b.end_date, b.status, b.created_at,
           p.name as product_name
    FROM bookings b
    JOIN products p ON b.product_id = p.product_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
";
$stmt2 = $conn->prepare($sql_bookings);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$bookings = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Customer Dashboard — OZYDE</title>
<style>
body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background:#f8f8f8; }
h1,h2 { color:#333; }
.container { max-width:1200px; margin:0 auto; }
.quick-highlights { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; }
.card { flex:1; min-width:180px; padding:18px; background:#fff; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,.1); text-align:center; cursor:pointer; transition:transform .2s; }
.card:hover { transform:translateY(-4px); }
a.card { text-decoration:none; color:inherit; }
table { width:100%; border-collapse:collapse; margin-top:20px; background:#fff; }
th, td { border:1px solid #ddd; padding:10px; text-align:left; }
th { background:#f4f4f4; }
</style>
</head>
<body>
<div class="container">
    <h1>Welcome, <?= htmlspecialchars($user['name']) ?>!</h1>

    <!-- Quick highlights -->
    <div class="quick-highlights">
        <a href="orders.php" class="card">
            <h3>My Rentals / Orders</h3>
        </a>
        <a href="orders.php" class="card">
            <h3>Orders</h3>
        </a>
        <a href="profile.php" class="card">
            <h3>My Account</h3>
        </a>
    </div>

    <!-- Purchases table -->
    <h2>Recent Purchases</h2>
    <?php if(!empty($purchases)): ?>
    <table>
        <tr>
            <th>Order ID</th>
            <th>Products</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        <?php foreach($purchases as $p): ?>
        <tr>
            <td>#<?= $p['order_id'] ?></td>
            <td><?= htmlspecialchars($p['products']) ?></td>
            <td>R<?= number_format($p['total_amount'],2) ?></td>
            <td><?= ucfirst($p['order_status']) ?></td>
            <td><?= $p['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p>You haven’t made any purchases yet.</p>
    <?php endif; ?>

    <!-- Rentals table -->
    <h2>My Rentals / Bookings</h2>
    <?php if(!empty($bookings)): ?>
    <table>
        <tr>
            <th>Booking ID</th>
            <th>Product</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        <?php foreach($bookings as $b): ?>
        <tr>
            <td>#<?= $b['booking_id'] ?></td>
            <td><?= htmlspecialchars($b['product_name']) ?></td>
            <td><?= $b['start_date'] ?></td>
            <td><?= $b['end_date'] ?></td>
            <td><?= ucfirst($b['status']) ?></td>
            <td><?= $b['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p>You haven’t made any rentals yet.</p>
    <?php endif; ?>

</div>
</body>
</html>
