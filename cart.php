<?php
session_start();
include 'db.php'; // your database connection

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch cart items for user
$sql = "SELECT 
            c.cart_id, 
            c.product_id, 
            p.name AS product_name, 
            p.image, 
            p.price, 
            c.size, 
            c.start_date, 
            c.end_date, 
            c.quantity
        FROM cart c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.user_id = $user_id";

$result = $conn->query($sql);
$cart_items = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // calculate days rented
        $start = new DateTime($row['start_date']);
        $end = new DateTime($row['end_date']);
        $days = $start->diff($end)->days ?: 1;

        $cart_items[] = [
            'cart_id' => $row['cart_id'],
            'product_id' => $row['product_id'],
            'name' => $row['product_name'],
            'image' => $row['image'],
            'price' => (float)$row['price'],
            'quantity' => (int)$row['quantity'],
            'days' => $days,
        ];
    }
}
$conn->close();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Cart — OZYDE</title>
    <style>
         :root {
            --bg: #fff;
            --nav-bg: #0b0b0b;
            --muted: #9a9a9a;
            --accent: #111;
            --max-width: 1200px;
        }
        
        * {
            box-sizing: border-box
        }
        
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            color: #111;
            background: var(--bg)
        }
        
        .nav-wrap {
            background: var(--nav-bg);
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 120;
            box-shadow: 0 6px 20px rgba(2, 2, 2, 0.12)
        }
        
        .nav {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            gap: 18px;
            justify-content: space-between
        }
        
        .logo {
            display: flex;
            gap: 12px;
            align-items: center;
            font-weight: 800;
            letter-spacing: 1px;
            font-size: 20px
        }
        
        .logo-badge {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #fff2, #fff6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #111;
            font-weight: 900;
            font-size: 16px
        }
        
        .search {
            flex: 1;
            max-width: 640px;
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0 12px
        }
        
        .search input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 999px 0 0 999px;
            border: 0;
            outline: 0;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.06);
            color: #fff
        }
        
        .search button {
            padding: 10px 12px;
            border-radius: 0 999px 999px 0;
            border: 0;
            background: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center
        }
        
        .icons {
            display: flex;
            gap: 14px;
            align-items: center
        }
        
        .icon-only {
            display: inline-flex;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 0;
            color: #fff;
            cursor: pointer
        }
        
        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #222, #111);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700
        }
        
        .profile-area {
            position: relative
        }
        
        .profile-btn {
            display: flex;
            gap: 10px;
            align-items: center;
            background: transparent;
            border: 0;
            color: #fff;
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 8px
        }
        
        .dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            background: #111;
            border-radius: 10px;
            padding: 8px 6px;
            min-width: 180px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: none
        }
        
        .dropdown a {
            display: block;
            color: #fff;
            padding: 10px 12px;
            text-decoration: none;
            font-size: 14px;
            border-radius: 8px
        }
        
        .dropdown a:hover {
            background: #1a1a1a
        }
        
        .mobile-toggle {
            display: none;
            background: transparent;
            border: 0;
            color: #fff
        }
        
        .mobile-menu {
            display: none;
            padding: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.03)
        }
        
        @media (max-width:880px) {
            .search {
                display: none
            }
            .mobile-toggle {
                display: inline-flex
            }
        }
        
        main {
            max-width: var(--max-width);
            margin: 28px auto;
            padding: 0 18px 60px
        }
        
        .topline {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px
        }
        
        .back-btn {
            background: #fff;
            border: 0;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700
        }
        
        h1 {
            margin: 0
        }
        
        .muted {
            color: var(--muted)
        }
        
        .layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 18px;
            margin-top: 18px
        }
        
        @media (max-width:980px) {
            .layout {
                grid-template-columns: 1fr
            }
        }
        
        .cart-item {
            background: #fff;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #eee;
            display: flex;
            gap: 12px;
            align-items: center
        }
        
        .thumb {
            width: 110px;
            height: 110px;
            border-radius: 8px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa
        }
        
        .title {
            font-weight: 700
        }
        
        .meta {
            font-size: 13px;
            color: var(--muted)
        }
        
        .qty {
            display: flex;
            gap: 6px;
            align-items: center
        }
        
        .qty button {
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid #eee;
            background: #fff;
            cursor: pointer
      
        }

          .qty button:disabled {
       opacity: 0.4;
       cursor: not-allowed;
}

        
        .summary {
            background: #fff;
            padding: 18px;
            border-radius: 12px;
            border: 1px solid #eee
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: 800;
            margin-top: 12px
        }
        
        .btn {
            padding: 10px 12px;
            border-radius: 8px;
            border: 0;
            cursor: pointer
        }
        
        .btn.primary {
            background: var(--accent);
            color: #fff
        }
        
        .btn.ghost {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.06)
        }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <header class="nav-wrap" role="banner">
        <div class="nav" role="navigation" aria-label="Main navigation">
            <div style="display:flex;align-items:center;gap:12px">
                <div class="logo" id="brandLink" style="cursor:pointer">
                    <div class="logo-badge" aria-hidden="true">OZ</div>
                    <div>OZYDE</div>
                </div>
            </div>

            <div class="search" role="search" aria-label="Site search">
                <input id="searchInput" type="search" placeholder="Search dresses, designers, collection..." aria-label="Search">
                <button id="searchBtn" aria-label="Search">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none"><path d="M21 21l-4.35-4.35" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="11" cy="11" r="6" stroke="#111" stroke-width="2"/></svg>
        </button>
            </div>

            <div class="icons" role="group" aria-label="User actions">
                <button class="icon-only" title="Wishlist" aria-label="Wishlist" id="topWishlistBtn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20.8 7.2a5 5 0 0 0-7.07 0L12 8.94l-1.73-1.72a5 5 0 1 0-7.07 7.07L12 21.5l8.8-8.8a5 5 0 0 0 0-7.5z" stroke="white" stroke-width="1.2" fill="none"/></svg>
        </button>

                <button class="icon-only" title="Cart" aria-label="Cart" id="topCartBtn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 3h2l1 7h13" stroke="white" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.5" fill="white"/><circle cx="18" cy="20" r="1.5" fill="white"/></svg>
        </button>

                <div class="profile-area" id="profileArea">
                    <button class="profile-btn" id="profileBtn" aria-haspopup="true" aria-expanded="false" aria-label="Open account menu">
            <div class="profile-avatar" aria-hidden="true">A</div>
            <div style="text-align:left">
              <div style="font-weight:700; font-size:14px">A. Nomvula</div>
              <div style="font-size:12px; color:#bdbdbd;">Member</div>
            </div>
          </button>

                    <div class="dropdown" id="profileDropdown" role="menu" aria-hidden="true">
                        <a href="customerdashboard.html" role="menuitem">My Account</a>
                        <a href="orders.html" role="menuitem">My Orders</a>
                        <a href="wishlist.html" role="menuitem">Wishlist</a>
                        <a href="#" role="menuitem" id="signOutLink">Sign out</a>
                    </div>
                </div>

                <button class="mobile-toggle" id="mobileToggle" aria-expanded="false" aria-label="Open menu">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M3 12h18M3 17h18" stroke="white" stroke-width="1.6" stroke-linecap="round"/></svg>
        </button>
            </div>
        </div>

        <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
            <div style="margin-bottom:10px">
                <input id="mobileSearch" type="search" placeholder="Search dresses..." style="width:100%; padding:10px 12px; border-radius:8px; border:0; background:rgba(255,255,255,0.04); color:#fff">
            </div>
            <div style="display:flex;gap:10px">
                <button class="icon-only" aria-label="Wishlist (mobile)" id="mWishlist"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20.8 7.2a5 5 0 0 0-7.07 0L12 8.94l-1.73-1.72a5 5 0 1 0-7.07 7.07L12 21.5l8.8-8.8a5 5 0 0 0 0-7.5z" stroke="white" stroke-width="1.2" fill="none"/></svg></button>
                <button class="icon-only" aria-label="Cart (mobile)" id="mCart"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 3h2l1 7h13" stroke="white" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.5" fill="white"/><circle cx="18" cy="20" r="1.5" fill="white"/></svg></button>
                <button class="profile-btn" style="background:transparent;border:0;color:#fff" id="mAccountBtn">Account</button>
            </div>
        </div>
    </header>


<!-- only change this part: instead of the JS fake fetch, we'll use PHP output -->

<main>
    <div class="topline">
        <button id="backBtn" class="back-btn" aria-label="Go back">← Back</button>
        <div>
            <h1>Cart</h1>
            <div class="muted">Review your items, remove from cart or proceed to checkout.</div>
        </div>
    </div>

    <div class="layout" style="margin-top:18px">
        <div id="itemsCol">
            <?php if (empty($cart_items)): ?>
                <div style="background:#fff;padding:30px;border-radius:12px;text-align:center">
                    Your cart is empty
                </div>
            <?php else: ?>
                <?php foreach ($cart_items as $it): 
                    $subtotal = $it['price'] * $it['quantity'] * $it['days']; ?>
                    <div class="cart-item">
                        <div class="thumb">
                            <img src="<?= htmlspecialchars($it['image']) ?>" 
                                 alt="<?= htmlspecialchars($it['name']) ?>" 
                                 style="width:100%;height:100%;object-fit:cover;border-radius:8px">
                        </div>
                        <div style="flex:1">
                            <div class="title"><?= htmlspecialchars($it['name']) ?></div>
                            <div class="meta"><?= $it['days'] ?> days • R<?= number_format($it['price'],2) ?></div>
                            <div style="margin-top:10px;display:flex;align-items:center;gap:12px">
                                
</div>

                                </div>
                                <form method="post" action="remove_cart.php" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
                                    <button class="btn ghost" type="submit">Remove</button>
                                </form>
                            </div>
                        </div>
                        <div style="font-weight:800">R<?= number_format($subtotal,2) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <aside class="summary">
            <?php
            $subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'] * $i['days'], $cart_items));
            $deposit = $subtotal * 0.20;
            $delivery = $cart_items ? 150 : 0;
            $total = $subtotal + $deposit + $delivery;
            ?>
            <div style="font-weight:800">Order summary</div>
            <div style="margin-top:12px">
                <div style="display:flex;justify-content:space-between">
                    <div>Subtotal</div>
                    <div>R<?= number_format($subtotal,2) ?></div>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:8px">
                    <div>Deposit (20%)</div>
                    <div>R<?= number_format($deposit,2) ?></div>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:8px">
                    <div>Delivery</div>
                    <div>R<?= number_format($delivery,2) ?></div>
                </div>
                <div class="total-row">
                    <div>Total</div>
                    <div>R<?= number_format($total,2) ?></div>
                </div>
            </div>
            <div style="margin-top:16px;display:flex;gap:8px">
                <button class="btn ghost" id="continueBtn" onclick="window.location.href='catalog.php'">Continue shopping</button>
                <?php if (!empty($cart_items)): ?>
                <button class="btn primary" id="checkoutBtn" onclick="window.location.href='checkout.php'">Checkout</button>
                <?php endif; ?>
            </div>
            <div class="muted" style="margin-top:12px;font-size:13px">Deposit refunded after inspection if items returned on time and undamaged.</div>
        </aside>
    </div>
</main>

</body>
</html>
