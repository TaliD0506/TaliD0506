<?php
session_start();
// ----- CONFIG - adjust these for your environment -----
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ozyde';
// -----------------------------------------------------

// Connect with mysqli
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "Database connection failed: (" . $mysqli->connect_errno . ") " . htmlspecialchars($mysqli->connect_error);
    exit;
}
$mysqli->set_charset('utf8mb4');

// Check logged in
$logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Fetch products
$products = [];
$query = "SELECT product_id, name, price, image, size, stock, is_rental FROM products ORDER BY created_at DESC";
if ($stmt = $mysqli->prepare($query)) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
} else {
    // fallback: try direct query
    if ($res = $mysqli->query($query)) {
        while ($row = $res->fetch_assoc()) $products[] = $row;
        $res->free();
    }
}

// Helper to safe output
function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dress Catalog — OZYDE</title>
    <style>
        /* (CSS kept exactly as in your layout) */
         :root {
            --bg: #fff;
            --text: #222;
            --muted: #7a7a7a;
            --accent: #111;
            --max-width: 1200px;
            --chip-bg: #f3f3f3;
            --chip-border: #e6e6e6;
            --primary: #111;
            --success: #2fa46b;
            --warning: #f59e0b;
            --danger: #ef4444;
            --airbnb-pink: #FF5A5F;
        }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Helvetica Neue", Arial, sans-serif; color:var(--text); background:var(--bg); -webkit-font-smoothing:antialiased; }
        a { color:inherit; text-decoration:none; }
        .container { max-width:var(--max-width); margin:0 auto; padding:0 20px; }
        .nav-wrap { background:#0b0b0b; color:#fff; position:sticky; top:0; z-index:120; box-shadow:0 6px 20px rgba(2,2,2,0.12); }
        .nav { max-width:var(--max-width); margin:0 auto; padding:10px 18px; display:flex; align-items:center; gap:18px; justify-content:space-between; }
        .logo { display:flex; gap:12px; align-items:center; font-weight:800; letter-spacing:1px; font-size:20px; cursor:pointer; }
        .logo-badge { width:40px; height:40px; border-radius:8px; background:linear-gradient(135deg,#fff2,#fff6); display:flex; align-items:center; justify-content:center; color:#111; font-weight:900; font-size:16px; }
        nav ul { margin:0; padding:0; display:flex; gap:18px; list-style:none; align-items:center; }
        nav a { font-size:14px; color:#fff; display:block; padding:8px 6px; transition:color 0.2s ease; }
        nav a:hover { color:#ddd; }
        .btn-signup { background:var(--accent); color:#fff; padding:8px 14px; border-radius:6px; font-weight:600; font-size:14px; transition:background 0.2s ease; }
        .btn-signup:hover { background:#333; }
        .icons { display:flex; gap:14px; align-items:center; }
        .icon-only { display:inline-flex; width:40px; height:40px; border-radius:8px; align-items:center; justify-content:center; background:transparent; border:0; color:#fff; cursor:pointer; transition:background 0.2s ease; }
        .icon-only:hover { background:rgba(255,255,255,0.1); }

        .search { flex:1; max-width:400px; display:flex; align-items:center; gap:6px; margin:0 12px; }
        .search input { width:100%; padding:10px 12px; border-radius:999px 0 0 999px; border:0; outline:0; font-size:14px; background:rgba(255,255,255,0.06); color:#fff; }
        .search input::placeholder { color:#aaa; }
        .search button { padding:10px 12px; border-radius:0 999px 999px 0; border:0; background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; }

        .hero { background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%); padding:60px 0; text-align:center; margin-bottom:40px; }
        .hero-content h1 { margin:0 0 16px 0; font-size:36px; font-weight:800; color:var(--accent); }
        .hero-content p { margin:0; color:var(--muted); font-size:18px; }

        .user-status { background:#f8f9fa; padding:12px 0; text-align:center; border-bottom:1px solid #e9ecef; margin-bottom:20px; }
        .user-status.logged-out { background:#fff3cd; color:#856404; }
        .user-status a { color:var(--accent); text-decoration:underline; font-weight:600; margin-left:5px; }

        .filters-section { margin-bottom:40px; }
        .filters { display:flex; gap:20px; flex-wrap:wrap; align-items:end; }
        .filter-group { display:flex; flex-direction:column; gap:8px; }
        .filter-group label { font-size:14px; font-weight:600; color:var(--muted); }
        .filter-group select { padding:10px 12px; border:1px solid #e6e6e6; border-radius:6px; font-size:14px; min-width:150px; background:#fff; cursor:pointer; }
        .filter-btn { padding:10px 20px; border:0; border-radius:6px; background:var(--accent); color:#fff; font-weight:600; cursor:pointer; transition:background 0.2s ease; }
        .filter-btn:hover { background:#333; }

        .products-section { margin-bottom:60px; }
        .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; gap:12px; }
        .section-header h2 { margin:0; font-size:24px; font-weight:700; color:var(--accent); }
        .sort-options { display:flex; align-items:center; gap:10px; }
        .sort-options label { font-size:14px; color:var(--muted); }
        .sort-options select { padding:8px 12px; border:1px solid #e6e6e6; border-radius:6px; font-size:14px; background:#fff; cursor:pointer; }

        .view-toggle { display:flex; gap:12px; align-items:center; margin-left:12px; }
        .view-toggle .toggle { background:transparent; border:0; font-weight:700; padding:6px 8px; cursor:pointer; color:var(--muted); position:relative; font-size:14px; }
        .view-toggle .toggle.active { color:var(--accent); }
        .view-toggle .toggle.active::after { content:''; position:absolute; left:6px; right:6px; height:3px; background:var(--accent); bottom:-6px; border-radius:3px; }

        .products-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:30px; margin-bottom:40px; }

        .product-card { background:#fff; border:1px solid #f0f0f0; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.05); transition:transform .3s ease, box-shadow .3s ease; position:relative; }
        .product-card:hover { transform:translateY(-5px); box-shadow:0 10px 30px rgba(0,0,0,0.1); }
        .product-image { position:relative; height:300px; overflow:hidden; }
        .product-image img { width:100%; height:100%; object-fit:cover; transition:transform .3s ease; }
        .product-card:hover .product-image img { transform:scale(1.05); }
        .wishlist-btn { position:absolute; top:12px; left:12px; width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,0.9); border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:10; }
        .product-overlay { position:absolute; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); display:flex; flex-direction:column; justify-content:center; align-items:center; gap:12px; opacity:0; transition:opacity .3s ease; }
        .product-card:hover .product-overlay { opacity:1; }
        .quick-view-btn, .rent-btn { padding:10px 20px; border:0; border-radius:6px; font-weight:600; cursor:pointer; transition:all .2s ease; }
        .quick-view-btn { background:#fff; color:var(--accent); }
        .rent-btn { background:var(--accent); color:#fff; }
        .
        .product-info { padding:20px; }
        .product-title { margin:0 0 8px 0; font-size:18px; font-weight:600; color:var(--accent); }
        .product-designer { margin:0 0 12px 0; color:var(--muted); font-size:14px; }
        .product-details { display:flex; justify-content:space-between; margin-bottom:12px; font-size:13px; color:var(--muted); }
        .product-price { font-size:20px; font-weight:700; color:var(--accent); }

        .load-more-section { text-align:center; }
        .load-more-btn { padding:12px 30px; border:1px solid #e6e6e6; border-radius:6px; background:#fff; color:var(--accent); font-weight:600; cursor:pointer; }
        .modal-overlay { position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); display:flex; justify-content:center; align-items:center; z-index:1000; opacity:0; visibility:hidden; transition:all .3s ease; }
        .modal-overlay.active { opacity:1; visibility:visible; }
        .modal-content { background:white; border-radius:12px; padding:30px; max-width:400px; width:90%; transform:scale(0.9); transition:transform .3s ease; }
        .modal-overlay.active .modal-content { transform:scale(1); }
        .modal-header { margin-bottom:20px; text-align:center; }
        .modal-header h3 { margin:0 0 10px 0; font-size:24px; font-weight:700; color:var(--accent); }
        .modal-actions { display:flex; flex-direction:column; gap:15px; margin-top:25px; }
        .modal-btn { padding:12px; border-radius:6px; font-weight:600; cursor:pointer; border:none; font-size:16px; }
        .modal-btn.primary { background:var(--accent); color:white; }
        .modal-btn.secondary { background:#f5f5f5; color:var(--accent); }
        footer { border-top:1px solid #eee; padding:36px 0; margin-top:28px; color:var(--muted); background:#fafafa; }
        .footer-grid { display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:32px; }
        @media (max-width:880px) {
            .search { order:3; max-width:100%; margin:15px 0 0 0; }
            .filters { flex-direction:column; align-items:stretch; }
            .filter-group { width:100%; }
            .section-header { flex-direction:column; align-items:flex-start; gap:15px; }
            .products-grid { grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:20px; }
            .footer-grid { grid-template-columns:1fr 1fr; gap:24px; }
        }
        @media (max-width:640px) {
            .nav { flex-wrap:wrap; }
            nav ul { order:2; width:100%; justify-content:center; margin-top:15px; }
            .products-grid { grid-template-columns:1fr; }
            .footer-grid { grid-template-columns:1fr; }
        }
    </style>
</head>

<body>
    <!-- ===== Navigation Bar ===== -->
    <header class="nav-wrap" role="banner">
        <div class="nav" role="navigation" aria-label="Main navigation">
            <div class="logo" id="brandLink">
                <div class="logo-badge" aria-hidden="true">✦</div>
                <div>Ozyde</div>
            </div>

            <!-- Search Bar -->
            <div class="search" role="search" aria-label="Site search">
                <input id="searchInput" type="search" placeholder="Search dresses, designers, collection..." aria-label="Search">
                <button id="searchBtn" aria-label="Search">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none">
                        <path d="M21 21l-4.35-4.35" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="11" cy="11" r="6" stroke="#111" stroke-width="2"/>
                    </svg>
                </button>
            </div>

            <nav aria-label="Main navigation">
                <ul id="main-nav">
                    <li><a href="about.html">About</a></li>
                    <li><a href="blog.html">Blog</a></li>
                    <li><a href="contact.html">Contact Us</a></li>
                    <li><a href="custommade.html">Custom Made</a></li>
                    <li><a href="catalog.php" class="active">Browse</a></li>
                </ul>
            </nav>

            <div class="icons" role="group" aria-label="User actions">
                <a href="finalhomepage.html" class="icon-only" title="Dashboard" aria-label="Dashboard">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="white" stroke-width="1.2" fill="none"/>
                        <polyline points="9 22 9 12 15 12 15 22" stroke="white" stroke-width="1.2" fill="none"/>
                    </svg>
                </a>

                <?php if ($logged_in): ?>
                    <a href="customerdashboard.php" class="icon-only" title="Account">Account</a>
                <?php else: ?>
                    <a href="login_register.php" class="icon-only" title="Login/Register" aria-label="Login/Register">Login</a>
                <?php endif; ?>

                <a href="help.html" class="icon-only" title="Help" aria-label="Help">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.2" fill="none"/>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="white" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                        <line x1="12" y1="17" x2="12" y2="17" stroke="white" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- User Status Banner -->
    <div class="user-status <?php echo $logged_in ? '' : 'logged-out'; ?>" id="userStatus">
        <div class="container">
            <?php if ($logged_in): ?>
                Welcome back — you are signed in. <a href="customerdashboard.php">Go to your dashboard</a>.
            <?php else: ?>
                You are browsing as a guest. <a href="login_register.php" id="loginLink">Sign in or register</a> to access wishlist and quick checkout.
            <?php endif; ?>
        </div>
    </div>

    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Designer Dress Rentals</h1>
                    <p>Luxury dresses for every special occasion</p>
                </div>
            </div>
        </section>

        <!-- Filters Section (static controls, no server filtering yet) -->
        <section class="filters-section">
            <div class="container">
                <div class="filters">
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category">
                            <option value="">All Categories</option>
                            <option value="evening">Evening Gowns</option>
                            <option value="cocktail">Cocktail Dresses</option>
                            <option value="formal">Formal Wear</option>
                            <option value="wedding">Wedding Guest</option>
                            <option value="prom">Prom Dresses</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="size">Size</label>
                        <select id="size">
                            <option value="">All Sizes</option>
                            <option value="xs">XS</option>
                            <option value="s">S</option>
                            <option value="m">M</option>
                            <option value="l">L</option>
                            <option value="xl">XL</option>
                            <option value="xxl">XXL</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="color">Color</label>
                        <select id="color">
                            <option value="">All Colors</option>
                            <option value="black">Black</option>
                            <option value="red">Red</option>
                            <option value="blue">Blue</option>
                            <option value="green">Green</option>
                            <option value="pink">Pink</option>
                            <option value="white">White</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="price">Price Range</label>
                        <select id="price">
                            <option value="">All Prices</option>
                            <option value="0-50">R0 - R50</option>
                            <option value="50-100">R50 - R100</option>
                            <option value="100-200">R100 - R200</option>
                            <option value="200+">R200+</option>
                        </select>
                    </div>
                    <button class="filter-btn">Apply Filters</button>
                </div>
            </div>
        </section>

        <!-- Products Grid -->
        <section class="products-section">
            <div class="container">
                <div class="section-header">
                    <h2>Available Dresses</h2>

                    <div style="display:flex; align-items:center; gap:12px;">
                        <div class="sort-options">
                            <label for="sort">Sort by:</label>
                            <select id="sort">
                                <option value="newest">Newest</option>
                                <option value="price-low">Price: Low to High</option>
                                <option value="price-high">Price: High to Low</option>
                                <option value="popular">Most Popular</option>
                            </select>
                        </div>

                        <div class="view-toggle" role="tablist" aria-label="View toggle">
                            <button class="toggle active" data-view="shop" role="tab" aria-selected="true">Shop</button>
                            <button class="toggle" data-view="for-you" role="tab" aria-selected="false">For you</button>
                        </div>
                    </div>
                </div>

                <div class="products-grid">
                    <?php if (empty($products)): ?>
                        <div style="grid-column:1/-1; background:#fff;border:1px solid #f1f1f1;padding:20px;border-radius:8px;text-align:center;">
                            No products found.
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $p): 
                            $pid = (int)$p['product_id'];
                            $title = esc($p['name'] ?? 'Untitled');
                            $price = is_numeric($p['price']) ? number_format((float)$p['price'], 2) : '0.00';
                            $img = !empty($p['image']) ? esc($p['image']) : 'gallery/placeholder.png';
                            // if image path is not an absolute URL, ensure it points to gallery/
                            if (!preg_match('#^https?://#i', $img) && strpos($img, '/') !== 0) {
                                // leave as-is (you stored gallery/path in DB)
                            }
                            $size = esc($p['size'] ?? '—');
                            $stock = isset($p['stock']) ? (int)$p['stock'] : 0;
                            $available_class = ($stock > 0) ? 'available' : 'rented';
                        ?>
                        <a href="productdetail.php?product_id=<?php echo $pid; ?>" class="product-link" style="text-decoration:none;">
                            <div class="product-card" data-id="<?php echo $pid; ?>">
                                <div class="product-image">
                                    <img src="<?php echo $img; ?>" alt="<?php echo $title; ?>" onerror="this.onerror=null;this.src='gallery/placeholder.png'">
                                    <button class="wishlist-btn" data-product-id="<?php echo $pid; ?>" aria-label="Add to wishlist">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                    </button>
                                    <div class="product-overlay" aria-hidden="true">
                                        <button class="quick-view-btn" data-product-id="<?php echo $pid; ?>">Quick View</button>
                                        <button class="rent-btn" data-product-id="<?php echo $pid; ?>">Rent Now</button>
                                    </div>
                                    <span class="availability-badge <?php echo $available_class; ?>"><?php echo $stock > 0 ? 'Available' : 'Rented'; ?></span>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-title"><?php echo $title; ?></h3>
                                    <p class="product-designer">By Designer</p>
                                    <div class="product-details">
                                        <span class="size">Size: <?php echo $size; ?></span>
                                        <span class="rental-period">3-day rental</span>
                                    </div>
                                    <div class="product-price">R<?php echo $price; ?></div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="load-more-section">
                    <button class="load-more-btn" id="loadMore">Load More Dresses</button>
                </div>
            </div>
        </section>
    </main>

    <!-- Login Modal -->
    <div class="modal-overlay" id="loginModal" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Sign In Required</h3>
                <p>Please sign in to access this feature</p>
            </div>
            <div class="modal-actions">
                <button class="modal-btn primary" id="goToRegister">Sign In / Register</button>
                <button class="modal-btn secondary" id="closeModal">Continue Browsing</button>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>Ozyde Boutique</h4>
                    <p>Premium dress rentals for your special occasions. Quality, style, and affordability combined.</p>
                    <div>Address:<br>5 Liebenberg Rd, Noordwyk, Midrand 1687</div>
                    <div class="socials" aria-label="Social media">
                        <a href="https://www.instagram.com/ozyde_?igsh=NWM0aTd4ZGFmeHVr" target="_blank" rel="noopener" aria-label="Instagram">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="3" width="18" height="18" rx="5" stroke="#333" stroke-width="1.2" fill="none"/>
                                <circle cx="12" cy="12" r="3.2" stroke="#333" stroke-width="1.2" fill="none"/>
                                <circle cx="17.5" cy="6.5" r="0.6" fill="#333"/>
                            </svg>
                        </a>
                        <a href="https://www.tiktok.com/@ozyde_designs?_t=ZS-8zlyfPi8HHJ&_r=1" target="_blank" rel="noopener" aria-label="TikTok">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/TikTok_logo.svg/1200px-TikTok_logo.svg.png" alt="TikTok" style="width:18px;height:18px;display:block" />
                        </a>
                        <a href="mailto:ozydedesigns@gmail.com" aria-label="Email">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="6" width="18" height="12" rx="2" stroke="#333" stroke-width="1.2" fill="none"/>
                                <path d="M4 7.5l8 6 8-6" stroke="#333" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#">How It Works</a></li>
                        <li><a href="#">Size Guide</a></li>
                        <li><a href="#">Care Instructions</a></li>
                        <li><a href="#">Returns & Policy</a></li>
                        <li><a href="#">Delivery</a></li>
                        <li><a href="#">Help Center</a></li>
                    </ul>
                </div>

                <div>
                    <h4>Company</h4>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Terms</a></li>
                        <li><a href="#">Privacy</a></li>
                    </ul>
                </div>

                <div>
                    <h4>Support</h4>
                    <ul>
                        <li><a href="contact.html">Contact</a></li>
                        <li><a href="#">Sizing Guide</a></li>
                        <li><a href="#">Cleaning</a></li>
                        <li><a href="#">Partnerships</a></li>
                        <li><a href="#">Affiliate</a></li>
                    </ul>
                </div>
            </div>

            <div style="margin-top:24px;text-align:center;padding-top:24px;border-top:1px solid #e6e6e6;color:var(--muted)">
                © 2024 Ozyde Boutique. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        // small UI wiring kept minimal:
        document.getElementById('searchBtn').addEventListener('click', function() {
            const q = document.getElementById('searchInput').value.trim();
            if (!q) { alert('Type something to search (demo)'); return; }
            // naive client-side redirect to search page
            window.location.href = 'search.html?q=' + encodeURIComponent(q);
        });

        // Login modal handling for Rent Now buttons if user is not logged in
        const loggedIn = <?php echo $logged_in ? 'true' : 'false'; ?>;
        const loginModal = document.getElementById('loginModal');
        const goToRegister = document.getElementById('goToRegister');
        const closeModal = document.getElementById('closeModal');

        document.querySelectorAll('.rent-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                // prevent following the product link when clicking rent on overlay
                e.stopPropagation();
                e.preventDefault();
                if (!loggedIn) {
                    loginModal.classList.add('active');
                    loginModal.setAttribute('aria-hidden', 'false');
                } else {
                    // if logged in, go to product detail or booking flow
                    const pid = btn.getAttribute('data-product-id');
                    window.location.href = 'productdetail.php?product_id=' + encodeURIComponent(pid);
                }
            });
        });

        goToRegister.addEventListener('click', function() {
            window.location.href = 'login_register.php?redirect=catalog.php';
        });
        closeModal.addEventListener('click', function() {
            loginModal.classList.remove('active');
            loginModal.setAttribute('aria-hidden', 'true');
        });

        // wishlist buttons placeholder
        document.querySelectorAll('.wishlist-btn').forEach(b => {
            b.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                if (!loggedIn) {
                    loginModal.classList.add('active');
                    loginModal.setAttribute('aria-hidden', 'false');
                    return;
                }
                alert('Wishlist: demo action (implement server-side).');
            });
        });

        // product card clicks already wrap with anchor to productdetail; keep default behavior
    </script>
</body>
</html>
