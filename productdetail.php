<?php
require 'db.php';

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($product_id <= 0) die("Invalid product ID.");

// Fetch product details
$sql = "SELECT * FROM products WHERE product_id = $product_id LIMIT 1";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) die("Product not found.");

$product = $result->fetch_assoc();
// Fetch product sizes from the new table
$sizes_query = "SELECT size, stock FROM product_sizes WHERE product_id = $product_id";
$sizes_result = $conn->query($sizes_query);

$sizes = [];
if ($sizes_result && $sizes_result->num_rows > 0) {
    while ($row = $sizes_result->fetch_assoc()) {
        $sizes[] = [
            'size' => $row['size'],
            'stock' => (int)$row['stock']
        ];
    }
}


// Prepare variables
$name = htmlspecialchars($product['name']);
$brand = htmlspecialchars($product['brand'] ?? '');
$description = htmlspecialchars($product['description']);
$price = number_format($product['price'], 2);
$image_main = htmlspecialchars($product['image']);
$sizes = explode(',', $product['size']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $name ?> — Ozyde</title>
  <link rel="stylesheet" href="style.css"> <!-- keep your main stylesheet -->
  <style>
    <style>
        /* Ozyde Boutique consistent styling */
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
            --airbnb-light: #F7F7F7;
            --airbnb-dark: #484848;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: "Helvetica Neue", Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        a {
            color: inherit;
            text-decoration: none;
        }
        
        .container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }
        
        /* Header Styles */
        .nav-wrap {
            background: #0b0b0b;
            color: #fff;
            position: sticky;
            top: 0;
            width: 100%;
            z-index: 120;
            box-shadow: 0 6px 20px rgba(2, 2, 2, 0.12);
        }
        
        .nav {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            gap: 18px;
            justify-content: space-between;
        }
        
        .logo {
            display: flex;
            gap: 12px;
            align-items: center;
            font-weight: 800;
            letter-spacing: 1px;
            font-size: 20px;
            cursor: pointer;
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
            font-size: 16px;
        }
        
        nav ul {
            margin: 0;
            padding: 0;
            display: flex;
            gap: 18px;
            list-style: none;
            align-items: center;
        }
        
        nav a {
            font-size: 14px;
            color: #fff;
            display: block;
            padding: 8px 6px;
            transition: color 0.2s ease;
        }
        
        nav a:hover {
            color: #ddd;
        }
        
        .icons {
            display: flex;
            gap: 14px;
            align-items: center;
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
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .icon-only:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px 0;
        }
        
        /* Breadcrumb Navigation */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            font-size: 14px;
            color: var(--muted);
        }
        
        .breadcrumb a {
            color: var(--accent);
            transition: color 0.2s ease;
        }
        
        .breadcrumb a:hover {
            color: var(--dark-gray);
        }
        
        .separator {
            color: var(--medium-gray);
        }
        
        .breadcrumb .current {
            color: var(--muted);
        }
        
        /* Product Section */
        .product-section {
            margin-bottom: 50px;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        /* Image Section */
        .image-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .main-image-container {
            position: relative;
            background: var(--airbnb-light);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .main-image {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .availability-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .availability-badge.available {
            background: #d4edda;
            color: #155724;
        }
        
        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        
        .thumbnail {
            width: 100%;
            aspect-ratio: 3/4;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        
        .thumbnail:hover, .thumbnail.active {
            opacity: 1;
            border-color: var(--accent);
        }
        
        /* Product Information */
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .product-header {
            border-bottom: 1px solid #e6e6e6;
            padding-bottom: 20px;
        }
        
        .product-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--accent);
            margin-bottom: 5px;
        }
        
        .product-designer {
            font-size: 16px;
            color: var(--muted);
            margin-bottom: 15px;
        }
        
        .product-price {
            display: flex;
            align-items: baseline;
            gap: 5px;
        }
        
        .price {
            font-size: 24px;
            font-weight: 700;
            color: var(--accent);
        }
        
        .period {
            font-size: 14px;
            color: var(--muted);
        }
        
        /* Size Section */
        .size-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--accent);
        }
        
        .size-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .size-btn {
            padding: 10px 5px;
            border: 1px solid #e6e6e6;
            background: white;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .size-btn.available:hover {
             background: #02000080;
        }
        
        .size-btn.available.selected {
            background: #02000080;
            color: #0b0b0b;
            border-color: #02000080;
        }
        
        .size-btn.unavailable {
            background: var(--airbnb-light);
            color: var(--muted);
            cursor: not-allowed;
        }
        
        .size-guide-link {
            font-size: 14px;
            color: var(--accent);
            text-decoration: underline;
        }
        
        /* Product Details */
        .product-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .detail-item h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--accent);
        }
        
        .detail-item p {
            font-size: 14px;
            color: var(--muted);
        }
        
        /* Rental Information */
        .rental-info h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--accent);
        }
        
        .rental-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .rental-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        
        .rental-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--airbnb-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--accent);
            flex-shrink: 0;
        }
        
        .rental-content h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--accent);
        }
        
        .rental-content p {
            font-size: 13px;
            color: var(--muted);
        }
        
        /* Booking Section */
        .booking-section {
            background: var(--airbnb-light);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .book-now-btn {
            background: var(--airbnb-pink);
            color: black;
            border: yes;
            padding: 15px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .book-now-btn:hover {
            background: #ea3a40ff;
        }
        
        .booking-note {
            font-size: 14px;
            color: var(--muted);
        }
        
        /* Section Titles */
        .section-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--accent);
            padding-bottom: 10px;
            border-bottom: 1px solid #e6e6e6;
        }
        
        /* Care Section */
        .care-section {
            margin-bottom: 50px;
        }
        
        .care-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .care-item {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }
        
        .care-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background: var(--airbnb-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--accent);
            flex-shrink: 0;
        }
        
        .care-content h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--accent);
        }
        
        .care-content p {
            font-size: 14px;
            color: var(--muted);
        }
        
        /* Description Section */
        .description-section {
            margin-bottom: 50px;
        }
        
        .description-content {
            line-height: 1.6;
        }
        
        .description-content p {
            margin-bottom: 15px;
            font-size: 15px;
            color: var(--text);
        }
        
        /* Recommendations Section */
        .recommendations-section {
            margin-bottom: 50px;
        }
        
        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .recommendation-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e6e6e6;
            transition: transform 0.2s ease;
        }
        
        .recommendation-card:hover {
            transform: translateY(-5px);
        }
        
        .recommendation-image {
            width: 100%;
            height: 300px;
            background: var(--airbnb-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
        }
        
        .recommendation-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .recommendation-content {
            padding: 15px;
        }
        
        .recommendation-content h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--accent);
        }
        
        .recommendation-content p {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 10px;
        }
        
        .recommendation-price {
            font-size: 16px;
            font-weight: 600;
            color: var(--accent);
        }
        
        /* Footer Styles */
        footer {
            border-top: 1px solid #eee;
            padding: 36px 0;
            color: var(--muted);
            background: #fafafa;
            width: 100%;
            margin-top: auto;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 32px;
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-grid h4 {
            margin: 0 0 16px 0;
            color: var(--accent);
            font-weight: 600;
        }
        
        .footer-grid ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-grid li {
            margin-bottom: 8px;
        }
        
        .footer-grid a {
            color: var(--muted);
            transition: color 0.2s ease;
        }
        
        .footer-grid a:hover {
            color: var(--accent);
        }
        
        .socials {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        
        .socials a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background: #f5f5f5;
            transition: all 0.2s ease;
        }
        
        .socials a:hover {
            background: var(--accent);
        }
        
        .socials a:hover svg path,
        .socials a:hover svg circle {
            stroke: #fff;
            fill: #fff;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                order: 2;
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .icons {
                order: 3;
                width: 100%;
                justify-content: center;
            }
            
            .product-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .product-details, .rental-grid {
                grid-template-columns: 1fr;
            }
            
            .size-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .care-grid, .recommendations-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .thumbnail-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .product-title {
                font-size: 24px;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
      <nav aria-label="Main navigation">
        <ul id="main-nav">
          <li><a href="about.html">About</a></li>
          <li><a href="blog.html">Blog</a></li>
          <li><a href="contact.html">Contact Us</a></li>
          <li><a href="catalog.html">Browse</a></li>
        </ul>
      </nav>
      <div class="icons" role="group" aria-label="User actions">
        <a href="finalhomepage.html" class="icon-only" title="Dashboard" aria-label="Dashboard">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="white" stroke-width="1.2" fill="none"/>
            <polyline points="9 22 9 12 15 12 15 22" stroke="white" stroke-width="1.2" fill="none"/>
          </svg>
        </a>
        <a href="login.html" class="icon-only" title="Login" aria-label="Login">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" stroke="white" stroke-width="1.2" fill="none" stroke-linecap="round"/>
            <polyline points="10 17 15 12 10 7" stroke="white" stroke-width="1.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            <line x1="15" y1="12" x2="3" y2="12" stroke="white" stroke-width="1.2" fill="none" stroke-linecap="round"/>
          </svg>
        </a>
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

  <main class="main-content">
    <div class="container">
      <!-- Breadcrumb Navigation -->
      <nav class="breadcrumb">
        <a href="catalog.html">Home</a>
        <span class="separator">/</span>
        <span class="current"><?= $name ?></span>
      </nav>

      <!-- Product Section -->
      <section class="product-section">
        <div class="product-grid">

          <!-- Image Section -->
          <div class="image-section">
            <div class="main-image-container">
              <img src="<?= $image_main ?>" alt="<?= $name ?>" class="main-image" id="mainImage">
              
            </div>
          </div>

          <!-- Product Info -->
          <div class="product-info">
            <div class="product-header">
              <h1 class="product-title"><?= $name ?></h1>
              <?php if ($brand): ?>
                <p class="product-designer">By <?= $brand ?></p>
              <?php endif; ?>
              <div class="product-price">
                <span class="price">R<?= $price ?></span>
                <span class="period">/ 3-day rental</span>
              </div>
            </div>

            <!-- Size Selection -->
            <div class="size-section">
              <h3>Size Selection</h3>
              <div class="size-grid">
                <?php foreach ($sizes as $size): ?>
                  <button class="size-btn available" data-size="<?= trim($size) ?>"><?= trim($size) ?></button>
                <?php endforeach; ?>
              </div>
              <a href="#" class="size-guide-link">Size Guide</a>
            </div>
          


             <!-- Rental Information -->
                        <div class="rental-info">
                            <h3>Rental Details</h3>
                            <div class="rental-grid">
                                <div class="rental-item">
                                    <div class="rental-icon">D</div>
                                    <div class="rental-content">
                                        <h4>Delivery</h4>
                                        <p>2-3 business days</p>
                                    </div>
                                </div>
                                <div class="rental-item">
                                    <div class="rental-icon">R</div>
                                    <div class="rental-content">
                                        <h4>Easy Returns</h4>
                                        <p>Pre-paid return label</p>
                                    </div>
                                </div>
                                <div class="rental-item">
                                    <div class="rental-icon">C</div>
                                    <div class="rental-content">
                                        <h4>Professional Cleaning</h4>
                                        <p>Included in rental price</p>
                                    </div>
                                </div>
                                <div class="rental-item">
                                    <div class="rental-icon">I</div>
                                    <div class="rental-content">
                                        <h4>Damage Protection</h4>
                                        <p>Minor wear covered</p>
                                    </div>
                                </div>
                            </div>
                        </div>

            <!-- Book Now Button -->
            <div class="booking-section">
              <button class="book-now-btn" id="bookNowBtn">Book Now - R<?= $price ?></button>
              <p class="booking-note">Select your rental dates in the next step</p>
            </div>

            <!-- Product Description -->
            <div class="description-section">
              <h2 class="section-title">Description</h2>
              <div class="description-content">
                <p><?= nl2br($description) ?></p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>

  <!-- Care Instructions -->
            <section class="care-section">
                <h2 class="section-title">Care Instructions</h2>
                <div class="care-grid">
                    <div class="care-item">
                        <div class="care-icon">DC</div>
                        <div class="care-content">
                            <h4>Dry Clean Only</h4>
                            <p>Professional cleaning required to maintain fabric quality</p>
                        </div>
                    </div>
                    <div class="care-item">
                        <div class="care-icon">H</div>
                        <div class="care-content">
                            <h4>Hang Carefully</h4>
                            <p>Use padded hangers to prevent shoulder marks</p>
                        </div>
                    </div>
                    <div class="care-item">
                        <div class="care-icon">S</div>
                        <div class="care-content">
                            <h4>Steam if Wrinkled</h4>
                            <p>Use low heat steam to remove wrinkles gently</p>
                        </div>
                    </div>
                    <div class="care-item">
                        <div class="care-icon">PS</div>
                        <div class="care-content">
                            <h4>Proper Storage</h4>
                            <p>Keep in garment bag away from direct sunlight</p>
                        </div>
                    </div>
                </div>
            </section>

  <!-- Footer (keep your original footer here) -->
  <footer>
    
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
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M17 3v6.2c0 2.5 2 4.5 4.5 4.5V12c-1.8 0-3.2-1.4-3.5-3H17zM9 8.5a5.5 5.5 0 1 0 5.5 5.5V8.5H9z" fill="#333"/>
                        </svg>
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
    
  </footer>

  <script>
    // Handle size selection
    const sizeButtons = document.querySelectorAll('.size-btn');
    let selectedSize = null;

    sizeButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        sizeButtons.forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        selectedSize = btn.dataset.size;
      });
    });

    // Booking button
    document.getElementById('bookNowBtn').addEventListener('click', () => {
      if (!selectedSize) {
        alert('Please select a size before booking.');
        return;
      }
      const productId = <?= $product_id ?>;
      window.location.href = `booking.html?product_id=${productId}&size=${encodeURIComponent(selectedSize)}`;
    });
  </script>
</body>
</html>
