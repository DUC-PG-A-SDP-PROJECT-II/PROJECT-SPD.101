<?php
ob_start();
session_start();
include("inc/config.php");
include("inc/functions.php");
include("inc/CSRF_Protect.php");
$csrf = new CSRF_Protect();
$error_message = '';
$success_message = '';
$error_message1 = '';
$success_message1 = '';

// Check if the user is logged in or not
if(!isset($_SESSION['user'])) {
	header('location: login.php');
	exit;
}

// Currency Switcher handler for Admin Panel
if (isset($_GET['admin_currency']) && in_array(strtoupper($_GET['admin_currency']), ['USD', 'KHR'])) {
	$_SESSION['currency'] = strtoupper($_GET['admin_currency']);
	$redirect_url = preg_replace('/([?&])admin_currency=[^&]+(&|$)/', '$1', $_SERVER['REQUEST_URI']);
	$redirect_url = rtrim($redirect_url, '?&');
	if (empty($redirect_url)) {
		$redirect_url = 'index.php';
	}
	header('Location: ' . $redirect_url);
	exit;
}

$admin_currency = get_current_currency();
$admin_rate = get_usd_to_khr_rate();

function get_admin_currency_url($curr) {
	$params = $_GET;
	$params['admin_currency'] = $curr;
	$base = basename($_SERVER['SCRIPT_NAME']);
	return $base . '?' . http_build_query($params);
}

// Check pending orders for sidebar badge
$stmt_badge = $pdo->prepare("SELECT COUNT(*) FROM tbl_payment WHERE payment_status = 'Pending'");
$stmt_badge->execute();
$count_pending_badge = (int)$stmt_badge->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Admin Dashboard &bull; eCommerce PHP</title>

	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/font-awesome.min.css">
	<link rel="stylesheet" href="css/ionicons.min.css">
	<link rel="stylesheet" href="css/datepicker3.css">
	<link rel="stylesheet" href="css/all.css">
	<link rel="stylesheet" href="css/select2.min.css">
	<link rel="stylesheet" href="css/dataTables.bootstrap.css">
	<link rel="stylesheet" href="css/jquery.fancybox.css">
	<link rel="stylesheet" href="css/AdminLTE.min.css">
	<link rel="stylesheet" href="css/_all-skins.min.css">
	<link rel="stylesheet" href="css/on-off-switch.css"/>
	<link rel="stylesheet" href="css/summernote.css">
	<link rel="stylesheet" href="style.css">
	<!-- Modern Enterprise Admin UI Styling with Cache Buster -->
	<link rel="stylesheet" href="css/modern-admin.css?v=<?php echo time(); ?>">

</head>

<body class="hold-transition fixed skin-blue sidebar-mini">

	<div class="wrapper">

		<header class="main-header">

			<a href="index.php" class="logo">
				<span class="logo-mini"><i class="fa fa-shopping-bag"></i></span>
				<span class="logo-lg"><i class="fa fa-shopping-bag" style="color:#60a5fa; margin-right: 6px;"></i> eCommerce PHP</span>
			</a>

			<nav class="navbar navbar-static-top">
				
				<div style="display:flex; align-items:center;">
					<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button" title="Toggle navigation">
						<span class="sr-only">Toggle navigation</span>
					</a>
					<div class="header-brand-title hidden-xs">
						<span>Admin Console</span>
						<span class="header-brand-badge"><i class="fa fa-circle" style="color:#10b981; font-size:8px;"></i> Live</span>
					</div>
					<a href="../index.php" target="_blank" class="btn btn-default btn-xs hidden-xs" style="margin-left: 15px; border-radius: 20px; font-weight: 600; padding: 5px 12px;">
						<i class="fa fa-external-link" style="color:#2563eb;"></i> View Store
					</a>
				</div>

				<!-- Top Bar ... User Information .. Login/Log out Area -->
				<div class="navbar-custom-menu">
					<ul class="nav navbar-nav" style="display:flex; align-items:center;">

						<!-- Quick 1-Click Segmented Currency Toggle (Desktop) -->
						<li class="hidden-xs" style="display:flex; align-items:center; margin-right:8px;">
							<div class="btn-group btn-group-xs" style="background:#f1f5f9; padding:2px; border-radius:20px; border:1px solid #e2e8f0; display:inline-flex;">
								<a href="<?php echo get_admin_currency_url('USD'); ?>" class="btn btn-xs <?php echo $admin_currency === 'USD' ? 'btn-primary' : 'btn-default'; ?>" style="border-radius:18px; font-weight:700; padding:4px 10px; font-size:11px; border:none; box-shadow:none; <?php echo $admin_currency === 'USD' ? 'background:#2563eb; color:#fff;' : 'background:transparent; color:#64748b;'; ?>" title="Switch Admin Display to US Dollars ($)">
									$ USD
								</a>
								<a href="<?php echo get_admin_currency_url('KHR'); ?>" class="btn btn-xs <?php echo $admin_currency === 'KHR' ? 'btn-primary' : 'btn-default'; ?>" style="border-radius:18px; font-weight:700; padding:4px 10px; font-size:11px; border:none; box-shadow:none; <?php echo $admin_currency === 'KHR' ? 'background:#2563eb; color:#fff;' : 'background:transparent; color:#64748b;'; ?>" title="Switch Admin Display to Khmer Riels (៛)">
									៛ KHR
								</a>
							</div>
						</li>

						<!-- Admin Currency Switcher Dropdown (Matches Storefront) -->
						<li class="dropdown currency-menu" style="margin-right:10px;">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" style="display:flex; align-items:center; gap:7px; padding:18px 10px; font-weight:700; color:#0f172a; text-decoration:none;" title="Change Admin Display Currency">
								<span style="display:inline-flex; align-items:center; box-shadow:0 1px 2px rgba(0,0,0,0.15); border-radius:3px; overflow:hidden;">
									<?php echo render_flag_svg($admin_currency === 'KHR' ? 'KH' : 'US'); ?>
								</span>
								<span class="hidden-xs" style="font-size:12px; font-weight:700; color:#0f172a;">
									<?php echo $admin_currency === 'KHR' ? 'KHR (៛)' : 'USD ($)'; ?>
								</span>
								<i class="fa fa-caret-down" style="font-size:10px; color:#64748b;"></i>
							</a>
							<ul class="dropdown-menu" style="width:280px; padding:0; border-radius:12px; box-shadow:0 12px 28px -4px rgba(15,23,42,0.15); border:1px solid #e2e8f0; overflow:hidden; left:auto; right:0;">
								<li style="background:#f8fafc; padding:12px 16px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
									<span style="font-size:11px; font-weight:800; text-transform:uppercase; color:#475569; letter-spacing:0.5px;">
										<i class="fa fa-money" style="color:#2563eb;"></i> Currency Switcher
									</span>
									<span class="label label-info" style="font-size:10px; border-radius:10px; padding:3px 8px;">Admin View</span>
								</li>
								
								<li style="padding:8px 10px;">
									<a href="<?php echo get_admin_currency_url('USD'); ?>" style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-radius:8px; text-decoration:none; <?php echo $admin_currency === 'USD' ? 'background:#eff6ff; color:#1d4ed8;' : 'color:#334155;'; ?>">
										<div style="display:flex; align-items:center; gap:10px;">
											<span style="display:inline-flex; border-radius:2px; overflow:hidden;"><?php echo render_flag_svg('US'); ?></span>
											<div>
												<div style="font-weight:700; font-size:13px;">USD - US Dollar</div>
												<div style="font-size:11px; color:#64748b;">Primary Store Currency ($)</div>
											</div>
										</div>
										<?php if($admin_currency === 'USD'): ?>
											<span class="label label-success" style="border-radius:12px; font-size:10px; padding:3px 8px;"><i class="fa fa-check"></i> Active</span>
										<?php endif; ?>
									</a>
								</li>

								<li style="padding:0 10px 8px 10px;">
									<a href="<?php echo get_admin_currency_url('KHR'); ?>" style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-radius:8px; text-decoration:none; <?php echo $admin_currency === 'KHR' ? 'background:#eff6ff; color:#1d4ed8;' : 'color:#334155;'; ?>">
										<div style="display:flex; align-items:center; gap:10px;">
											<span style="display:inline-flex; border-radius:2px; overflow:hidden;"><?php echo render_flag_svg('KH'); ?></span>
											<div>
												<div style="font-weight:700; font-size:13px;">KHR - Khmer Riel</div>
												<div style="font-size:11px; color:#64748b;">Cambodian Riel (៛)</div>
											</div>
										</div>
										<?php if($admin_currency === 'KHR'): ?>
											<span class="label label-success" style="border-radius:12px; font-size:10px; padding:3px 8px;"><i class="fa fa-check"></i> Active</span>
										<?php endif; ?>
									</a>
								</li>

								<li style="background:#f8fafc; padding:10px 16px; border-top:1px solid #e2e8f0; font-size:12px; color:#64748b; display:flex; justify-content:space-between; align-items:center;">
									<span><i class="fa fa-exchange" style="color:#10b981;"></i> $1 USD = <strong><?php echo number_format($admin_rate); ?> ៛</strong></span>
									<a href="settings.php#tab_9" style="font-size:11px; font-weight:700; color:#2563eb; text-decoration:none;" title="Update Rate in Website Settings"><i class="fa fa-pencil"></i> Edit Rate</a>
								</li>
							</ul>
						</li>

						<li class="dropdown user user-menu">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">
								<?php
								$header_user_photo = (!empty($_SESSION['user']['photo']) && file_exists('../assets/uploads/'.$_SESSION['user']['photo']) && is_file('../assets/uploads/'.$_SESSION['user']['photo']))
									? '../assets/uploads/'.$_SESSION['user']['photo']
									: '../assets/uploads/user-13.jpg';
								?>
								<img src="<?php echo $header_user_photo; ?>" class="user-image" alt="User Image">
								<span class="hidden-xs" style="font-weight:600;"><?php echo htmlspecialchars($_SESSION['user']['full_name']); ?></span>
								<i class="fa fa-angle-down" style="font-size:12px; color:#94a3b8; margin-left:4px;"></i>
							</a>
							<ul class="dropdown-menu">
								<li class="user-header">
									<img src="<?php echo $header_user_photo; ?>" class="img-circle" alt="User Image">
									<p>
										<?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>
										<small><?php echo htmlspecialchars($_SESSION['user']['role']); ?></small>
									</p>
								</li>
								<li class="user-footer">
									<div class="pull-left">
										<a href="profile-edit.php" class="btn btn-default btn-sm"><i class="fa fa-user"></i> Profile</a>
									</div>
									<div class="pull-right">
										<a href="logout.php" class="btn btn-danger btn-sm"><i class="fa fa-sign-out"></i> Sign Out</a>
									</div>
								</li>
							</ul>
						</li>
					</ul>
				</div>

			</nav>
		</header>

  		<?php $cur_page = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1); ?>

		<!-- Side Bar to Manage Shop Activities -->
  		<aside class="main-sidebar">
    		<section class="sidebar">
      
      			<ul class="sidebar-menu">

					<li class="header">MAIN</li>

			        <li class="treeview <?php if($cur_page == 'index.php') {echo 'active';} ?>">
						<a href="index.php">
							<i class="fa fa-dashboard"></i> <span>Dashboard</span>
						</a>
			        </li>

					<li class="header">CATALOG & PRODUCTS</li>

                    <li class="treeview <?php if( ($cur_page == 'product.php') || ($cur_page == 'product-add.php') || ($cur_page == 'product-edit.php') ) {echo 'active';} ?>">
                        <a href="product.php">
                            <i class="fa fa-shopping-bag"></i> <span>All Products</span>
                        </a>
                    </li>

                    <li class="treeview <?php if( ($cur_page == 'top-category.php') || ($cur_page == 'top-category-add.php') || ($cur_page == 'top-category-edit.php') || ($cur_page == 'mid-category.php') || ($cur_page == 'mid-category-add.php') || ($cur_page == 'mid-category-edit.php') || ($cur_page == 'end-category.php') || ($cur_page == 'end-category-add.php') || ($cur_page == 'end-category-edit.php') ) {echo 'active';} ?>">
                        <a href="#">
                            <i class="fa fa-sitemap"></i>
                            <span>Category Tree</span>
                            <span class="pull-right-container">
								<i class="fa fa-angle-left pull-right"></i>
							</span>
                        </a>
                        <ul class="treeview-menu">
                            <li><a href="top-category.php"><i class="fa fa-angle-right"></i> Top Level Categories</a></li>
                            <li><a href="mid-category.php"><i class="fa fa-angle-right"></i> Mid Level Categories</a></li>
                            <li><a href="end-category.php"><i class="fa fa-angle-right"></i> End Level Categories</a></li>
                        </ul>
                    </li>

                    <li class="treeview <?php if( ($cur_page == 'size.php') || ($cur_page == 'size-add.php') || ($cur_page == 'size-edit.php') || ($cur_page == 'color.php') || ($cur_page == 'color-add.php') || ($cur_page == 'color-edit.php') ) {echo 'active';} ?>">
                        <a href="#">
                            <i class="fa fa-tags"></i>
                            <span>Product Attributes</span>
                            <span class="pull-right-container">
								<i class="fa fa-angle-left pull-right"></i>
							</span>
                        </a>
                        <ul class="treeview-menu">
                            <li><a href="size.php"><i class="fa fa-angle-right"></i> Sizes</a></li>
                            <li><a href="color.php"><i class="fa fa-angle-right"></i> Colors</a></li>
                        </ul>
                    </li>

                    <li class="treeview <?php if( ($cur_page == 'coupon.php') || ($cur_page == 'coupon-add.php') || ($cur_page == 'coupon-edit.php') ) {echo 'active';} ?>">
                        <a href="coupon.php">
                            <i class="fa fa-tag"></i> <span>Coupons & Discounts</span>
                        </a>
                    </li>

                    <li class="treeview <?php if( ($cur_page == 'review.php') ) {echo 'active';} ?>">
                        <a href="review.php">
                            <i class="fa fa-star"></i> <span>Product Reviews</span>
                        </a>
                    </li>

					<li class="header">SALES & CUSTOMERS</li>

                    <li class="treeview <?php if( ($cur_page == 'reports.php') ) {echo 'active';} ?>">
                        <a href="reports.php">
                            <i class="fa fa-bar-chart"></i> <span>Sales Reports & Analytics</span>
                        </a>
                    </li>

                    <li class="treeview <?php if( ($cur_page == 'order.php') ) {echo 'active';} ?>">
                        <a href="order.php">
                            <i class="fa fa-sticky-note"></i> <span>Orders</span>
							<?php if ($count_pending_badge > 0): ?>
								<span class="pull-right-container">
									<small class="label pull-right bg-red"><?php echo $count_pending_badge; ?></small>
								</span>
							<?php endif; ?>
                        </a>
                    </li>

					<li class="treeview <?php if( ($cur_page == 'customer.php') || ($cur_page == 'customer-add.php') || ($cur_page == 'customer-edit.php') ) {echo 'active';} ?>">
			        	<a href="customer.php">
			            	<i class="fa fa-users"></i> <span>Customers</span>
			        	</a>
			        </li>

			        <li class="treeview <?php if( ($cur_page == 'subscriber.php') ) {echo 'active';} ?>">
			        	<a href="subscriber.php">
			            	<i class="fa fa-envelope-o"></i> <span>Subscribers</span>
			        	</a>
			        </li>

					<li class="header">STORE DESIGN & CONTENT</li>

					<li class="treeview <?php if( ($cur_page == 'slider.php') ) {echo 'active';} ?>">
			        	<a href="slider.php">
			            	<i class="fa fa-picture-o"></i> <span>Sliders</span>
			        	</a>
			        </li>

			        <li class="treeview <?php if( ($cur_page == 'service.php') ) {echo 'active';} ?>">
			        	<a href="service.php">
			            	<i class="fa fa-check-square-o"></i> <span>Services</span>
			        	</a>
			        </li>

			        <li class="treeview <?php if( ($cur_page == 'faq.php') ) {echo 'active';} ?>">
			        	<a href="faq.php">
			            	<i class="fa fa-question-circle"></i> <span>FAQ</span>
			        	</a>
			        </li>

			        <li class="treeview <?php if( ($cur_page == 'page.php') ) {echo 'active';} ?>">
			        	<a href="page.php">
			            	<i class="fa fa-file-text-o"></i> <span>Page Settings</span>
			        	</a>
			        </li>

			        <li class="treeview <?php if( ($cur_page == 'social-media.php') ) {echo 'active';} ?>">
			        	<a href="social-media.php">
			            	<i class="fa fa-share-alt"></i> <span>Social Media</span>
			        	</a>
			        </li>

					<li class="header">CONFIGURATION</li>

			        <li class="treeview <?php if( ($cur_page == 'settings.php') ) {echo 'active';} ?>">
			        	<a href="settings.php">
			            	<i class="fa fa-sliders"></i> <span>Website Settings</span>
			        	</a>
			        </li>

					<li class="treeview <?php if( ($cur_page == 'shipping-cost.php') || ($cur_page == 'shipping-cost-edit.php') ) {echo 'active';} ?>">
			        	<a href="shipping-cost.php">
			            	<i class="fa fa-truck"></i> <span>Shipping Rates</span>
			        	</a>
			        </li>

					<li class="treeview <?php if( ($cur_page == 'country.php') || ($cur_page == 'country-add.php') || ($cur_page == 'country-edit.php') ) {echo 'active';} ?>">
			        	<a href="country.php">
			            	<i class="fa fa-globe"></i> <span>Countries</span>
			        	</a>
			        </li>

      			</ul>
    		</section>
  		</aside>

  		<div class="content-wrapper">
