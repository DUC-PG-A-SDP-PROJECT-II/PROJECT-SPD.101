<?php
ob_start();
session_start();
include("admin/inc/config.php");
include("admin/inc/functions.php");
include("admin/inc/CSRF_Protect.php");
$csrf = new CSRF_Protect();
$error_message = '';
$success_message = '';
$error_message1 = '';
$success_message1 = '';

// Getting all language variables into array as global variable
$i=1;
$statement = $pdo->prepare("SELECT * FROM tbl_language");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);							
foreach ($result as $row) {
	define('LANG_VALUE_'.$i,$row['lang_value']);
	$i++;
}

$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row)
{
	$logo = $row['logo'];
	$favicon = $row['favicon'];
	$contact_email = $row['contact_email'];
	$contact_phone = $row['contact_phone'];
	$meta_title_home = $row['meta_title_home'];
    $meta_keyword_home = $row['meta_keyword_home'];
    $meta_description_home = $row['meta_description_home'];
    $before_head = $row['before_head'];
    $after_body = $row['after_body'];
}

// Checking the order table and removing the pending transaction that are 24 hours+ old. Very important
$current_date_time = date('Y-m-d H:i:s');
$statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_status=?");
$statement->execute(array('Pending'));
$result = $statement->fetchAll(PDO::FETCH_ASSOC);							
foreach ($result as $row) {
	$ts1 = strtotime($row['payment_date']);
	$ts2 = strtotime($current_date_time);     
	$diff = $ts2 - $ts1;
	$time = $diff/(3600);
	if($time>24) {

		// Return back the stock amount
		$statement1 = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
		$statement1->execute(array($row['payment_id']));
		$result1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result1 as $row1) {
			$statement2 = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id=?");
			$statement2->execute(array($row1['product_id']));
			$result2 = $statement2->fetchAll(PDO::FETCH_ASSOC);							
			foreach ($result2 as $row2) {
				$p_qty = $row2['p_qty'];
			}
			$final = $p_qty+$row1['quantity'];

			$statement = $pdo->prepare("UPDATE tbl_product SET p_qty=? WHERE p_id=?");
			$statement->execute(array($final,$row1['product_id']));
		}
		
		// Deleting data from table
		$statement1 = $pdo->prepare("DELETE FROM tbl_order WHERE payment_id=?");
		$statement1->execute(array($row['payment_id']));

		$statement1 = $pdo->prepare("DELETE FROM tbl_payment WHERE id=?");
		$statement1->execute(array($row['id']));
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

	<!-- Meta Tags -->
	<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>

	<!-- Favicon -->
	<link rel="icon" type="image/png" href="assets/uploads/<?php echo $favicon; ?>">

	<!-- Stylesheets -->
	<link rel="stylesheet" href="assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/css/font-awesome.min.css">
	<link rel="stylesheet" href="assets/css/owl.carousel.min.css">
	<link rel="stylesheet" href="assets/css/owl.theme.default.min.css">
	<link rel="stylesheet" href="assets/css/jquery.bxslider.min.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/rating.css">
	<link rel="stylesheet" href="assets/css/spacing.css">
	<link rel="stylesheet" href="assets/css/bootstrap-touch-slider.css">
	<link rel="stylesheet" href="assets/css/animate.min.css">
	<link rel="stylesheet" href="assets/css/tree-menu.css">
	<link rel="stylesheet" href="assets/css/select2.min.css">
	<link rel="stylesheet" href="assets/css/main.css">
	<link rel="stylesheet" href="assets/css/responsive.css">
	<link rel="stylesheet" href="assets/css/modern-ecommerce.css?v=<?php echo time(); ?>">

	<!-- Core Scripts -->
	<script src="assets/js/jquery-2.2.4.min.js"></script>

	<?php

	$statement = $pdo->prepare("SELECT * FROM tbl_page WHERE id=1");
	$statement->execute();
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);							
	foreach ($result as $row) {
		$about_meta_title = $row['about_meta_title'];
		$about_meta_keyword = $row['about_meta_keyword'];
		$about_meta_description = $row['about_meta_description'];
		$faq_meta_title = $row['faq_meta_title'];
		$faq_meta_keyword = $row['faq_meta_keyword'];
		$faq_meta_description = $row['faq_meta_description'];
		$blog_meta_title = $row['blog_meta_title'];
		$blog_meta_keyword = $row['blog_meta_keyword'];
		$blog_meta_description = $row['blog_meta_description'];
		$contact_meta_title = $row['contact_meta_title'];
		$contact_meta_keyword = $row['contact_meta_keyword'];
		$contact_meta_description = $row['contact_meta_description'];
		$pgallery_meta_title = $row['pgallery_meta_title'];
		$pgallery_meta_keyword = $row['pgallery_meta_keyword'];
		$pgallery_meta_description = $row['pgallery_meta_description'];
		$vgallery_meta_title = $row['vgallery_meta_title'];
		$vgallery_meta_keyword = $row['vgallery_meta_keyword'];
		$vgallery_meta_description = $row['vgallery_meta_description'];
	}

	$cur_page = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
	
	if($cur_page == 'index.php' || $cur_page == 'login.php' || $cur_page == 'registration.php' || $cur_page == 'cart.php' || $cur_page == 'checkout.php' || $cur_page == 'forget-password.php' || $cur_page == 'reset-password.php' || $cur_page == 'product-category.php' || $cur_page == 'product.php') {
		?>
		<title><?php echo $meta_title_home; ?></title>
		<meta name="keywords" content="<?php echo $meta_keyword_home; ?>">
		<meta name="description" content="<?php echo $meta_description_home; ?>">
		<?php
	}

	if($cur_page == 'about.php') {
		?>
		<title><?php echo $about_meta_title; ?></title>
		<meta name="keywords" content="<?php echo $about_meta_keyword; ?>">
		<meta name="description" content="<?php echo $about_meta_description; ?>">
		<?php
	}
	if($cur_page == 'faq.php') {
		?>
		<title><?php echo $faq_meta_title; ?></title>
		<meta name="keywords" content="<?php echo $faq_meta_keyword; ?>">
		<meta name="description" content="<?php echo $faq_meta_description; ?>">
		<?php
	}
	if($cur_page == 'contact.php') {
		?>
		<title><?php echo $contact_meta_title; ?></title>
		<meta name="keywords" content="<?php echo $contact_meta_keyword; ?>">
		<meta name="description" content="<?php echo $contact_meta_description; ?>">
		<?php
	}
	$og_photo = '';
	$og_title = '';
	$og_slug = '';
	$og_description = '';

	if($cur_page == 'product.php')
	{
		if(isset($_REQUEST['id'])) {
			$statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id=?");
			$statement->execute(array($_REQUEST['id']));
			$result = $statement->fetchAll(PDO::FETCH_ASSOC);							
			foreach ($result as $row) 
			{
			    $og_photo = $row['p_featured_photo'];
			    $og_title = $row['p_name'];
			    $og_slug = 'product.php?id='.$_REQUEST['id'];
				$og_description = substr(strip_tags($row['p_description']),0,200).'...';
			}
		}
	}

	if($cur_page == 'dashboard.php') {
		?>
		<title>Dashboard - <?php echo $meta_title_home; ?></title>
		<meta name="keywords" content="<?php echo $meta_keyword_home; ?>">
		<meta name="description" content="<?php echo $meta_description_home; ?>">
		<?php
	}
	if($cur_page == 'customer-profile.php') {
		?>
		<title>Customer Profile - <?php echo $meta_title_home; ?></title>
		<meta name="keywords" content="<?php echo $meta_keyword_home; ?>">
		<meta name="description" content="<?php echo $meta_description_home; ?>">
		<?php
	}
	if($cur_page == 'customer-profile-update.php') {
		?>
		<title>Update Profile - <?php echo $meta_title_home; ?></title>
		<meta name="keywords" content="<?php echo $meta_keyword_home; ?>">
		<meta name="description" content="<?php echo $meta_description_home; ?>">
		<?php
	}
	if($cur_page == 'customer-billing-shipping-update.php') {
		?>
		<title>Update Billing and Shipping Info - <?php echo $meta_title_home; ?></title>
		<meta name="keywords" content="<?php echo $meta_keyword_home; ?>">
		<meta name="description" content="<?php echo $meta_description_home; ?>">
		<?php
	}
	if($cur_page == 'customer-password-update.php') {
		?>
		<title>Update Password - <?php echo $meta_title_home; ?></title>
		<meta name="keywords" content="<?php echo $meta_keyword_home; ?>">
		<meta name="description" content="<?php echo $meta_description_home; ?>">
		<?php
	}
	if($cur_page == 'customer-order.php') {
		?>
		<title>Orders - <?php echo $meta_title_home; ?></title>
		<meta name="keywords" content="<?php echo $meta_keyword_home; ?>">
		<meta name="description" content="<?php echo $meta_description_home; ?>">
		<?php
	}
	?>
	
	<?php if($cur_page == 'blog-single.php' && !empty($og_title)): ?>
		<meta property="og:title" content="<?php echo $og_title; ?>">
		<meta property="og:type" content="website">
		<meta property="og:url" content="<?php echo BASE_URL.$og_slug; ?>">
		<meta property="og:description" content="<?php echo $og_description; ?>">
		<meta property="og:image" content="assets/uploads/<?php echo $og_photo; ?>">
	<?php endif; ?>

	<?php if($cur_page == 'product.php' && !empty($og_title)): ?>
		<meta property="og:title" content="<?php echo $og_title; ?>">
		<meta property="og:type" content="website">
		<meta property="og:url" content="<?php echo BASE_URL.$og_slug; ?>">
		<meta property="og:description" content="<?php echo $og_description; ?>">
		<meta property="og:image" content="assets/uploads/<?php echo $og_photo; ?>">
	<?php endif; ?>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>

	<script type="text/javascript" src="//platform-api.sharethis.com/js/sharethis.js#property=5993ef01e2587a001253a261&product=inline-share-buttons"></script>

<?php echo $before_head; ?>

</head>
<body>

<?php echo $after_body; ?>
<!--
<div id="preloader">
	<div id="status"></div>
</div>-->

<?php
// Cart & Wishlist counters calculation
$cart_item_count = 0;
$table_total_price = 0;
if (isset($_SESSION['cart_p_qty'])) {
    foreach ($_SESSION['cart_p_qty'] as $k => $qty) {
        $cart_item_count += (int)$qty;
        $price = isset($_SESSION['cart_p_current_price'][$k]) ? (float)$_SESSION['cart_p_current_price'][$k] : 0;
        $table_total_price += ($price * (int)$qty);
    }
}
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// Fetch Top Categories for the search dropdown and menu
$stmt_top_cat = $pdo->prepare("SELECT * FROM tbl_top_category ORDER BY tcat_name ASC");
$stmt_top_cat->execute();
$header_top_categories = $stmt_top_cat->fetchAll(PDO::FETCH_ASSOC);

// Fetch all Mid Categories
$stmt_mid_cat = $pdo->prepare("SELECT * FROM tbl_mid_category ORDER BY mcat_name ASC");
$stmt_mid_cat->execute();
$header_mid_categories = $stmt_mid_cat->fetchAll(PDO::FETCH_ASSOC);

// Fetch all End Categories
$stmt_end_cat = $pdo->prepare("SELECT * FROM tbl_end_category ORDER BY ecat_name ASC");
$stmt_end_cat->execute();
$header_end_categories = $stmt_end_cat->fetchAll(PDO::FETCH_ASSOC);

// Map mid categories by top category id
$mid_cats_by_top = [];
foreach ($header_mid_categories as $m) {
    $mid_cats_by_top[$m['tcat_id']][] = $m;
}

// Map end categories by mid category id
$end_cats_by_mid = [];
foreach ($header_end_categories as $e) {
    $end_cats_by_mid[$e['mcat_id']][] = $e;
}

$current_currency = get_current_currency();
$current_lang = get_current_language();
?>

<!-- Amazon-Style Modern Top Bar -->
<div class="modern-top-bar">
	<div class="container">
		<div class="row">
			<div class="col-md-7 col-sm-6 col-xs-12">
				<span class="top-item"><i class="fa fa-globe"></i> Deliver Worldwide &bull; Free Shipping Over $50</span>
				<span class="top-item hidden-xs"><i class="fa fa-phone"></i> <?php echo htmlspecialchars($contact_phone); ?></span>
				<span class="top-item hidden-xs hidden-sm"><i class="fa fa-envelope-o"></i> <?php echo htmlspecialchars($contact_email); ?></span>
			</div>
			<div class="col-md-5 col-sm-6 col-xs-12 text-right">
				<span class="top-item">
					<a href="currency-switch.php?currency=<?php echo $current_currency === 'USD' ? 'KHR' : 'USD'; ?>" title="Click to toggle currency (USD / KHR)" style="color: #febd69; font-weight: 700;">
						<i class="fa fa-exchange"></i> <?php echo $current_currency === 'USD' ? 'Riels (៛)' : 'Dollars ($)'; ?>
					</a>
				</span>
				<span class="top-item">
					<a href="customer-order.php"><i class="fa fa-truck"></i> Track Order</a>
				</span>
				<span class="top-item">
					<a href="faq.php"><i class="fa fa-question-circle"></i> Help</a>
				</span>
				<span class="top-item hidden-xs" style="margin-right: 0;">
					<?php
					$statement = $pdo->prepare("SELECT * FROM tbl_social");
					$statement->execute();
					$result = $statement->fetchAll(PDO::FETCH_ASSOC);
					foreach ($result as $row) {
						if (!empty($row['social_url'])) {
							echo '<a href="'.$row['social_url'].'" style="margin-left:8px;" target="_blank"><i class="'.$row['social_icon'].'"></i></a>';
						}
					}
					?>
				</span>
			</div>
		</div>
	</div>
</div>

<!-- Amazon-Style Main Header -->
<header class="modern-header">
	<div class="container">
		<div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
			
			<!-- Logo -->
			<div class="col-md-2 col-sm-4 col-xs-6 logo-area" style="display: flex; align-items: center; gap: 12px;">
				<a href="index.php">
					<img src="assets/uploads/<?php echo $logo; ?>" alt="Store Logo">
				</a>
				<div class="deliver-badge hidden-xs hidden-sm" onclick="window.location.href='checkout.php'">
					<i class="fa fa-map-marker"></i>
					<div>
						<span class="line-1">Deliver to</span>
						<span class="line-2">Worldwide</span>
					</div>
				</div>
			</div>

			<!-- Amazon Smart Unified Search Bar with Autocomplete -->
			<div class="col-md-5 col-sm-8 col-xs-12 search-area" style="margin: 6px 0;">
				<div class="search-form-wrapper">
					<form action="search-result.php" method="get" class="amazon-search-bar">
						<select id="modern-search-category" name="category_id" class="search-cat-select hidden-xs">
							<option value="0">All Categories</option>
							<?php foreach ($header_top_categories as $tcat): ?>
								<option value="<?php echo $tcat['tcat_id']; ?>"><?php echo htmlspecialchars($tcat['tcat_name']); ?></option>
							<?php endforeach; ?>
						</select>
						<input type="text" id="modern-search-input" name="search_text" class="search-input" placeholder="Search More-quality products, brands..." autocomplete="off">
						<button type="submit" class="search-btn" title="Search">
							<i class="fa fa-search"></i>
						</button>
					</form>
					<!-- Live Suggestions Popup -->
					<div id="search-suggestions-box" class="search-suggestions-box"></div>
				</div>
			</div>

			<!-- Right Action Items -->
			<div class="col-md-5 col-sm-12 col-xs-6" style="margin: 6px 0;">
				<div class="header-actions">

					<!-- Amazon-Style Currency & Language Switcher Dropdown (Matches User Screenshot) -->
					<div class="lang-curr-dropdown-wrapper">
						<div class="lang-curr-trigger" tabindex="0" title="Change Language & Currency">
							<span class="flag-wrapper">
								<?php echo render_flag_svg($current_lang === 'KH' || $current_currency === 'KHR' ? 'KH' : 'KH'); ?>
							</span>
							<div class="lang-curr-text">
								<span class="top-line"><?php echo $current_lang; ?>/</span>
								<span class="bottom-line">
									<?php echo $current_currency; ?> <i class="fa fa-caret-down" style="font-size: 9px; margin-left: 2px;"></i>
								</span>
							</div>
						</div>

						<div class="lang-curr-menu">
							<!-- Language Section -->
							<div class="menu-section">
								<div class="section-title">
									<i class="fa fa-language"></i> Change Language
								</div>
								<ul class="option-list">
									<li>
										<a href="currency-switch.php?lang=EN" class="option-item <?php echo $current_lang === 'EN' ? 'active' : ''; ?>">
											<span class="custom-radio <?php echo $current_lang === 'EN' ? 'checked' : ''; ?>"></span>
											<span class="flag-icon-mini"><?php echo render_flag_svg('US'); ?></span>
											<span class="opt-label">English - EN</span>
										</a>
									</li>
									<li>
										<a href="currency-switch.php?lang=KH" class="option-item <?php echo $current_lang === 'KH' ? 'active' : ''; ?>">
											<span class="custom-radio <?php echo $current_lang === 'KH' ? 'checked' : ''; ?>"></span>
											<span class="flag-icon-mini"><?php echo render_flag_svg('KH'); ?></span>
											<span class="opt-label">ភាសាខ្មែរ - KH (Khmer)</span>
										</a>
									</li>
								</ul>
							</div>

							<div class="menu-divider"></div>

							<!-- Currency Section -->
							<div class="menu-section">
								<div class="section-title">
									<i class="fa fa-money"></i> Change Currency
								</div>
								<div class="exchange-rate-badge">
									<i class="fa fa-exchange"></i> $1.00 USD = 4,100 KHR (៛)
								</div>
								<ul class="option-list">
									<li>
										<a href="currency-switch.php?currency=USD" class="option-item <?php echo $current_currency === 'USD' ? 'active' : ''; ?>">
											<span class="custom-radio <?php echo $current_currency === 'USD' ? 'checked' : ''; ?>"></span>
											<span class="symbol-tag">$</span>
											<span class="opt-label"><strong>USD</strong> - US Dollar ($)</span>
											<?php if ($current_currency === 'USD'): ?>
												<span class="active-pill">Active</span>
											<?php endif; ?>
										</a>
									</li>
									<li>
										<a href="currency-switch.php?currency=KHR" class="option-item <?php echo $current_currency === 'KHR' ? 'active' : ''; ?>">
											<span class="custom-radio <?php echo $current_currency === 'KHR' ? 'checked' : ''; ?>"></span>
											<span class="symbol-tag">៛</span>
											<span class="opt-label"><strong>KHR</strong> - Khmer Riel (៛)</span>
											<?php if ($current_currency === 'KHR'): ?>
												<span class="active-pill">Active</span>
											<?php endif; ?>
										</a>
									</li>
								</ul>
							</div>

							<div class="menu-footer-hint">
								<i class="fa fa-info-circle"></i> Exchange rate applied instantly across the store
							</div>
						</div>
					</div>

					<!-- Wishlist -->
					<a href="wishlist.php" class="nav-action-link" title="My Wishlist">
						<span class="top-line">
							<i class="fa fa-heart" style="color: #e4144d;"></i> Saved
						</span>
						<span class="bottom-line">
							Wishlist <span class="wishlist-counter-bubble"><?php echo $wishlist_count; ?></span>
						</span>
					</a>

					<!-- Account & Lists (Amazon-Style Dropdown) -->
					<div class="account-dropdown-wrapper">
						<a href="<?php echo isset($_SESSION['customer']) ? 'customer-profile.php' : 'login.php'; ?>" class="nav-action-link">
							<span class="top-line">
								Hello, <?php 
								if (isset($_SESSION['customer'])) {
									$first_name = explode(' ', trim($_SESSION['customer']['cust_name']))[0];
									echo htmlspecialchars($first_name);
								} else {
									echo 'Sign In';
								}
								?>
							</span>
							<span class="bottom-line">
								Account &amp; Lists <i class="fa fa-caret-down" style="font-size: 10px;"></i>
							</span>
						</a>

						<div class="account-dropdown-menu">
							<?php if (isset($_SESSION['customer'])): ?>
								<div class="user-greet">
									<div style="font-size: 11px; color: #777;">Signed in as</div>
									<strong style="color: #111;"><?php echo htmlspecialchars($_SESSION['customer']['cust_name']); ?></strong>
									<div style="font-size: 11px; color: #888;"><?php echo htmlspecialchars($_SESSION['customer']['cust_email']); ?></div>
								</div>
								<a href="customer-profile.php"><i class="fa fa-user-circle-o" style="margin-right: 8px; color: #e4144d;"></i> My Profile</a>
								<a href="customer-order.php"><i class="fa fa-shopping-bag" style="margin-right: 8px; color: #007185;"></i> Returns &amp; Orders</a>
								<a href="wishlist.php"><i class="fa fa-heart-o" style="margin-right: 8px; color: #e4144d;"></i> My Wishlist (<?php echo $wishlist_count; ?>)</a>
								<a href="customer-profile-update.php"><i class="fa fa-pencil" style="margin-right: 8px;"></i> Edit Account Info</a>
								<a href="customer-billing-shipping-update.php"><i class="fa fa-truck" style="margin-right: 8px;"></i> Billing &amp; Shipping</a>
								<a href="customer-password-update.php"><i class="fa fa-lock" style="margin-right: 8px;"></i> Change Password</a>
								<div class="divider"></div>
								<a href="logout.php" style="color: #c00;"><i class="fa fa-sign-out" style="margin-right: 8px;"></i> Sign Out</a>
							<?php else: ?>
								<div style="padding: 14px 18px; text-align: center;">
									<a href="login.php" class="btn btn-warning btn-block" style="background: #ffd814; border-color: #fcd200; color: #111; font-weight: 700; border-radius: 20px; padding: 8px; font-size: 13px;">
										Sign in
									</a>
									<p style="margin: 10px 0 0; font-size: 12px; color: #555;">
										New customer? <a href="registration.php" style="color: #007185; font-weight: 600; text-decoration: underline;">Start here.</a>
									</p>
								</div>
								<div class="divider"></div>
								<a href="login.php"><i class="fa fa-user" style="margin-right: 8px;"></i> Your Account</a>
								<a href="customer-order.php"><i class="fa fa-archive" style="margin-right: 8px;"></i> Your Orders</a>
								<a href="wishlist.php"><i class="fa fa-heart-o" style="margin-right: 8px;"></i> Your Wishlist</a>
							<?php endif; ?>
						</div>
					</div>

					<!-- Returns & Orders Link -->
					<a href="<?php echo isset($_SESSION['customer']) ? 'customer-order.php' : 'login.php'; ?>" class="nav-action-link hidden-xs">
						<span class="top-line">Returns</span>
						<span class="bottom-line">&amp; Orders</span>
					</a>

					<!-- Amazon Cart Link -->
					<a href="cart.php" class="cart-action-link" title="Shopping Cart">
						<div class="cart-icon-wrapper">
							<i class="fa fa-shopping-cart"></i>
							<span class="cart-counter-bubble"><?php echo $cart_item_count; ?></span>
						</div>
						<div class="cart-total-text">
							<span class="top-line" style="display: block; font-size: 10px; color: #ccc; line-height: 1;">Cart</span>
							<?php echo format_price($table_total_price); ?>
						</div>
					</a>

				</div>
			</div>

		</div>
	</div>
</header>

<!-- Amazon-Style Sub-Navigation Bar -->
<nav class="modern-subnav">
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<ul>
					<li class="all-btn">
						<a href="index.php"><i class="fa fa-home"></i> Home</a>
					</li>
					<?php foreach (array_slice($header_top_categories, 0, 5) as $top_c): ?>
						<li class="nav-mega-item">
							<a href="product-category.php?id=<?php echo $top_c['tcat_id']; ?>&type=top-category">
								<?php echo htmlspecialchars($top_c['tcat_name']); ?> <i class="fa fa-angle-down nav-caret"></i>
							</a>
							<div class="modern-mega-menu">
								<div class="mega-menu-header">
									<div class="mega-header-info">
										<span class="mega-badge">Department</span>
										<h4 class="mega-title"><?php echo htmlspecialchars($top_c['tcat_name']); ?></h4>
									</div>
									<a href="product-category.php?id=<?php echo $top_c['tcat_id']; ?>&type=top-category" class="mega-view-all-btn">
										Browse All <?php echo htmlspecialchars($top_c['tcat_name']); ?> <i class="fa fa-arrow-right"></i>
									</a>
								</div>
								<div class="mega-menu-grid">
									<?php 
									$cur_mids = isset($mid_cats_by_top[$top_c['tcat_id']]) ? $mid_cats_by_top[$top_c['tcat_id']] : [];
									if (!empty($cur_mids)): 
										foreach ($cur_mids as $mid_c): 
											$cur_ends = isset($end_cats_by_mid[$mid_c['mcat_id']]) ? $end_cats_by_mid[$mid_c['mcat_id']] : [];
									?>
										<div class="mega-col">
											<a href="product-category.php?id=<?php echo $mid_c['mcat_id']; ?>&type=mid-category" class="mega-mid-link">
												<i class="fa fa-folder-open-o"></i> <?php echo htmlspecialchars($mid_c['mcat_name']); ?>
											</a>
											<ul class="mega-end-list">
												<?php if (!empty($cur_ends)): ?>
													<?php foreach ($cur_ends as $end_c): ?>
														<li>
															<a href="product-category.php?id=<?php echo $end_c['ecat_id']; ?>&type=end-category">
																<i class="fa fa-angle-right"></i> <?php echo htmlspecialchars($end_c['ecat_name']); ?>
															</a>
														</li>
													<?php endforeach; ?>
												<?php else: ?>
													<li class="empty-hint"><em>All in <?php echo htmlspecialchars($mid_c['mcat_name']); ?></em></li>
												<?php endif; ?>
											</ul>
										</div>
									<?php 
										endforeach; 
									else: 
									?>
										<div class="mega-col">
											<p class="text-muted">Explore all products in <?php echo htmlspecialchars($top_c['tcat_name']); ?>.</p>
										</div>
									<?php endif; ?>
								</div>
								<div class="mega-menu-footer">
									<span class="mega-footer-item"><i class="fa fa-truck text-primary"></i> Fast Delivery across Cambodia</span>
									<span class="mega-footer-item"><i class="fa fa-shield text-success"></i> 100% Genuine Quality Guaranteed</span>
									<span class="mega-footer-item"><i class="fa fa-refresh text-warning"></i> 30-Day Easy Returns</span>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
					<li class="highlight">
						<a href="search-result.php?search_text="><i class="fa fa-bolt" style="color:#febd69;"></i> Today's Deals</a>
					</li>
					<li>
						<a href="wishlist.php"><i class="fa fa-heart" style="color:#e4144d;"></i> Wishlist</a>
					</li>
					<?php
					$statement = $pdo->prepare("SELECT * FROM tbl_page WHERE id=1");
					$statement->execute();
					$result = $statement->fetchAll(PDO::FETCH_ASSOC);		
					foreach ($result as $row) {
						$about_title = $row['about_title'];
						$faq_title = $row['faq_title'];
						$contact_title = $row['contact_title'];
					}
					?>
					<li><a href="about.php"><?php echo $about_title; ?></a></li>
					<li><a href="faq.php"><?php echo $faq_title; ?></a></li>
					<li><a href="contact.php"><?php echo $contact_title; ?></a></li>
				</ul>
			</div>
		</div>
	</div>
</nav>