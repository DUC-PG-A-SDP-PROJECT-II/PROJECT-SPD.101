<?php require_once('header.php'); ?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_checkout = $row['banner_checkout'];
}
?>

<?php
if(!isset($_SESSION['cart_p_id']) || empty($_SESSION['cart_p_id'])) {
    header('location: cart.php');
    exit;
}

// Refresh customer profile from database if customer is logged in
if(isset($_SESSION['customer']['cust_id'])) {
    $stmt_cust = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=?");
    $stmt_cust->execute(array($_SESSION['customer']['cust_id']));
    $fresh_cust = $stmt_cust->fetch(PDO::FETCH_ASSOC);
    if($fresh_cust) {
        $_SESSION['customer'] = $fresh_cust;
    }
}
?>

<div class="page-banner" style="background-image: url(assets/uploads/<?php echo $banner_checkout; ?>)">
    <div class="overlay"></div>
    <div class="page-banner-inner">
        <h1><?php echo LANG_VALUE_22; ?></h1>
    </div>
</div>

<div class="page modern-cart-container cart">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                
                <!-- Modern Checkout Progress Steps -->
                <div class="checkout-progress-bar">
                    <div class="progress-step completed">
                        <span class="step-num"><i class="fa fa-check"></i></span>
                        <span class="step-text"><a href="cart.php" style="color:inherit;text-decoration:none;">1. Shopping Cart</a></span>
                    </div>
                    <div class="progress-connector" style="background:#10b981;"></div>
                    <div class="progress-step active">
                        <span class="step-num"><i class="fa fa-truck"></i></span>
                        <span class="step-text">2. Checkout & Shipping</span>
                    </div>
                    <div class="progress-connector"></div>
                    <div class="progress-step">
                        <span class="step-num">3</span>
                        <span class="step-text">3. Order Confirmation</span>
                    </div>
                </div>

                <?php if(!isset($_SESSION['customer'])): ?>
                    <div class="empty-cart-card">
                        <div class="empty-cart-icon-circle" style="color:#2563eb; background:#eff6ff; border-color:#bfdbfe;">
                            <i class="fa fa-user"></i>
                        </div>
                        <h2 class="empty-cart-title">Please Sign In to Complete Your Order</h2>
                        <p class="empty-cart-subtitle">You need an active customer account to review shipping addresses and complete your purchase.</p>
                        <a href="login.php" class="btn btn-warning" style="background: linear-gradient(180deg, #f7dfa5 0%, #f0c14b 100%); border: 1px solid #a88734; border-radius: 25px; padding: 12px 28px; font-weight: 800; color: #111; font-size: 14px; text-decoration: none;">
                            <i class="fa fa-sign-in"></i> <?php echo LANG_VALUE_160; ?>
                        </a>
                    </div>
                <?php else: ?>

                <?php
                // Build array of items
                $table_total_price = 0;
                $cart_total_items = 0;

                $arr_cart_p_id = array();
                $i=0;
                foreach($_SESSION['cart_p_id'] as $key => $value) {
                    $i++;
                    $arr_cart_p_id[$i] = $value;
                }

                $arr_cart_size_id = array();
                $i=0;
                foreach($_SESSION['cart_size_id'] as $key => $value) {
                    $i++;
                    $arr_cart_size_id[$i] = $value;
                }

                $arr_cart_size_name = array();
                $i=0;
                foreach($_SESSION['cart_size_name'] as $key => $value) {
                    $i++;
                    $arr_cart_size_name[$i] = $value;
                }

                $arr_cart_color_id = array();
                $i=0;
                foreach($_SESSION['cart_color_id'] as $key => $value) {
                    $i++;
                    $arr_cart_color_id[$i] = $value;
                }

                $arr_cart_color_name = array();
                $i=0;
                foreach($_SESSION['cart_color_name'] as $key => $value) {
                    $i++;
                    $arr_cart_color_name[$i] = $value;
                }

                $arr_cart_p_qty = array();
                $i=0;
                foreach($_SESSION['cart_p_qty'] as $key => $value) {
                    $i++;
                    $arr_cart_p_qty[$i] = $value;
                    $cart_total_items += (int)$value;
                }

                $arr_cart_p_current_price = array();
                $i=0;
                foreach($_SESSION['cart_p_current_price'] as $key => $value) {
                    $i++;
                    $arr_cart_p_current_price[$i] = $value;
                }

                $arr_cart_p_name = array();
                $i=0;
                foreach($_SESSION['cart_p_name'] as $key => $value) {
                    $i++;
                    $arr_cart_p_name[$i] = $value;
                }

                $arr_cart_p_featured_photo = array();
                $i=0;
                foreach($_SESSION['cart_p_featured_photo'] as $key => $value) {
                    $i++;
                    $arr_cart_p_featured_photo[$i] = $value;
                }

                // Query database for authoritative names and photos (preserves quotes like 27" and prevents broken html)
                $product_info_map = [];
                if (!empty($arr_cart_p_id)) {
                    $unique_pids = array_unique($arr_cart_p_id);
                    $placeholders = implode(',', array_fill(0, count($unique_pids), '?'));
                    $stmt_pinfo = $pdo->prepare("SELECT p_id, p_name, p_featured_photo FROM tbl_product WHERE p_id IN ($placeholders)");
                    $stmt_pinfo->execute(array_values($unique_pids));
                    while ($r = $stmt_pinfo->fetch(PDO::FETCH_ASSOC)) {
                        $product_info_map[$r['p_id']] = $r;
                    }
                }

                for($i=1;$i<=count($arr_cart_p_id);$i++) {
                    $row_price = $arr_cart_p_current_price[$i] * $arr_cart_p_qty[$i];
                    $table_total_price += $row_price;
                }

                // Shipping cost calculation
                $shipping_cost = 0;
                if(isset($_SESSION['customer']['cust_country'])) {
                    $statement = $pdo->prepare("SELECT * FROM tbl_shipping_cost WHERE country_id=?");
                    $statement->execute(array($_SESSION['customer']['cust_country']));
                    $total = $statement->rowCount();
                    if($total) {
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($result as $row) {
                            $shipping_cost = (float)$row['amount'];
                        }
                    } else {
                        $statement = $pdo->prepare("SELECT * FROM tbl_shipping_cost_all WHERE sca_id=1");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($result as $row) {
                            $shipping_cost = (float)$row['amount'];
                        }
                    }
                }

                // Coupon discount calculation
                $discount_amount = 0;
                if (isset($_SESSION['coupon'])) {
                    if ($_SESSION['coupon']['type'] === 'percentage') {
                        $discount_amount = round(($table_total_price * (float)$_SESSION['coupon']['value']) / 100, 2);
                    } else {
                        $discount_amount = min($table_total_price, (float)$_SESSION['coupon']['value']);
                    }
                    $_SESSION['coupon']['discount'] = $discount_amount;
                }
                $final_total = max(0, $table_total_price - $discount_amount + $shipping_cost);

                // Country names
                $b_country_name = '';
                $s_country_name = '';
                if(!empty($_SESSION['customer']['cust_b_country'])) {
                    $st = $pdo->prepare("SELECT country_name FROM tbl_country WHERE country_id=?");
                    $st->execute(array($_SESSION['customer']['cust_b_country']));
                    $b_country_name = $st->fetchColumn();
                }
                if(!empty($_SESSION['customer']['cust_s_country'])) {
                    $st = $pdo->prepare("SELECT country_name FROM tbl_country WHERE country_id=?");
                    $st->execute(array($_SESSION['customer']['cust_s_country']));
                    $s_country_name = $st->fetchColumn();
                }

                // Verify checkout requirements
                $checkout_access = 1;
                if(
                    empty($_SESSION['customer']['cust_b_name']) ||
                    empty($_SESSION['customer']['cust_b_phone']) ||
                    empty($_SESSION['customer']['cust_b_country']) ||
                    empty($_SESSION['customer']['cust_b_address']) ||
                    empty($_SESSION['customer']['cust_b_city']) ||
                    empty($_SESSION['customer']['cust_b_state']) ||
                    empty($_SESSION['customer']['cust_b_zip']) ||
                    empty($_SESSION['customer']['cust_s_name']) ||
                    empty($_SESSION['customer']['cust_s_phone']) ||
                    empty($_SESSION['customer']['cust_s_country']) ||
                    empty($_SESSION['customer']['cust_s_address']) ||
                    empty($_SESSION['customer']['cust_s_city']) ||
                    empty($_SESSION['customer']['cust_s_state']) ||
                    empty($_SESSION['customer']['cust_s_zip'])
                ) {
                    $checkout_access = 0;
                }
                ?>

                <!-- 2-Column Responsive Modern Checkout Layout -->
                <div class="row">
                    <!-- Left Column: Order Items, Addresses, Payment Selection -->
                    <div class="col-lg-8 col-md-12">
                        
                        <!-- 1. Order Items Card (Clean, compact, and perfectly formatted) -->
                        <div class="modern-cart-card" style="margin-bottom: 24px;">
                            <div class="modern-cart-card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                                <h3 class="modern-cart-title" style="margin:0;">
                                    <i class="fa fa-shopping-basket" style="color:#f3a847;"></i> <?php echo LANG_VALUE_26; ?>
                                    <span class="modern-cart-count-pill"><?php echo $cart_total_items; ?> item<?php echo ($cart_total_items > 1) ? 's' : ''; ?></span>
                                </h3>
                                <a href="cart.php" class="btn btn-sm" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1; border-radius:20px; font-weight:700; font-size:12px; padding:6px 14px; text-decoration:none;">
                                    <i class="fa fa-pencil" style="color:#007185;"></i> Edit Cart
                                </a>
                            </div>

                            <div class="table-responsive">
                                <table class="modern-cart-table checkout-order-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 52%;">Product Details</th>
                                            <th style="width: 16%; text-align: center;">Price</th>
                                            <th style="width: 14%; text-align: center;">Qty</th>
                                            <th style="width: 18%; text-align: right;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for($i=1;$i<=count($arr_cart_p_id);$i++): 
                                            $item_pid = $arr_cart_p_id[$i];
                                            $real_p_name = !empty($product_info_map[$item_pid]['p_name']) ? $product_info_map[$item_pid]['p_name'] : $arr_cart_p_name[$i];
                                            $real_p_photo = !empty($product_info_map[$item_pid]['p_featured_photo']) ? $product_info_map[$item_pid]['p_featured_photo'] : $arr_cart_p_featured_photo[$i];
                                            $row_total_price = $arr_cart_p_current_price[$i] * $arr_cart_p_qty[$i];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="cart-product-cell">
                                                    <div class="cart-product-thumb" style="width:60px !important; height:60px !important; min-width:60px !important; max-width:60px !important; border-radius:8px; border:1px solid #e2e8f0; background:#fff; display:flex; align-items:center; justify-content:center; overflow:hidden; padding:3px; flex-shrink:0;">
                                                        <img src="assets/uploads/<?php echo htmlspecialchars($real_p_photo); ?>" alt="<?php echo htmlspecialchars($real_p_name, ENT_QUOTES, 'UTF-8'); ?>" style="width:100% !important; height:100% !important; max-width:60px !important; max-height:60px !important; object-fit:contain !important; display:block;">
                                                    </div>
                                                    <div class="cart-product-details">
                                                        <a href="product.php?id=<?php echo $item_pid; ?>" class="cart-product-title" style="font-size:14px; font-weight:700; line-height:1.4; color:#0f172a; margin-bottom:6px; display:inline-block;" target="_blank">
                                                            <?php echo htmlspecialchars($real_p_name); ?>
                                                        </a>
                                                        <div class="cart-variants-wrap">
                                                            <?php if(!empty($arr_cart_size_name[$i])): ?>
                                                                <span class="badge-variant"><i class="fa fa-tag"></i> Size: <?php echo htmlspecialchars($arr_cart_size_name[$i]); ?></span>
                                                            <?php endif; ?>
                                                            <?php if(!empty($arr_cart_color_name[$i])): ?>
                                                                <span class="badge-variant"><i class="fa fa-paint-brush"></i> Color: <?php echo htmlspecialchars($arr_cart_color_name[$i]); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="text-align: center; font-weight: 600; color: #334155;">
                                                <?php echo format_price($arr_cart_p_current_price[$i]); ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="checkout-qty-badge">
                                                    × <?php echo (int)$arr_cart_p_qty[$i]; ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 15px;">
                                                <?php echo format_price($row_total_price); ?>
                                            </td>
                                        </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 2. Billing & Shipping Address Cards -->
                        <div class="row" style="margin-bottom: 24px;">
                            <!-- Billing Address Card -->
                            <div class="col-md-6 col-sm-12" style="margin-bottom: 16px;">
                                <div class="checkout-address-card">
                                    <div class="checkout-address-card-header">
                                        <h4 class="checkout-address-card-title">
                                            <i class="fa fa-file-text-o" style="color:#007185;"></i> <?php echo LANG_VALUE_161; ?>
                                        </h4>
                                        <a href="customer-billing-shipping-update.php" class="checkout-address-edit-link">
                                            <i class="fa fa-pencil"></i> Edit
                                        </a>
                                    </div>
                                    <ul class="checkout-address-list">
                                        <li>
                                            <span class="addr-label">Name:</span>
                                            <span class="addr-value"><strong><?php echo htmlspecialchars($_SESSION['customer']['cust_b_name'] ?? ''); ?></strong></span>
                                        </li>
                                        <?php if(!empty($_SESSION['customer']['cust_b_cname'])): ?>
                                        <li>
                                            <span class="addr-label">Company:</span>
                                            <span class="addr-value"><?php echo htmlspecialchars($_SESSION['customer']['cust_b_cname']); ?></span>
                                        </li>
                                        <?php endif; ?>
                                        <li>
                                            <span class="addr-label">Phone:</span>
                                            <span class="addr-value"><?php echo htmlspecialchars($_SESSION['customer']['cust_b_phone'] ?? ''); ?></span>
                                        </li>
                                        <li>
                                            <span class="addr-label">Country:</span>
                                            <span class="addr-value"><?php echo htmlspecialchars($b_country_name ?: 'Not specified'); ?></span>
                                        </li>
                                        <li>
                                            <span class="addr-label">Address:</span>
                                            <span class="addr-value"><?php echo nl2br(htmlspecialchars($_SESSION['customer']['cust_b_address'] ?? '')); ?></span>
                                        </li>
                                        <li>
                                            <span class="addr-label">City / State:</span>
                                            <span class="addr-value"><?php echo htmlspecialchars($_SESSION['customer']['cust_b_city'] ?? ''); ?>, <?php echo htmlspecialchars($_SESSION['customer']['cust_b_state'] ?? ''); ?> <?php echo htmlspecialchars($_SESSION['customer']['cust_b_zip'] ?? ''); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Shipping Destination Card -->
                            <div class="col-md-6 col-sm-12" style="margin-bottom: 16px;">
                                <div class="checkout-address-card">
                                    <div class="checkout-address-card-header">
                                        <h4 class="checkout-address-card-title">
                                            <i class="fa fa-truck" style="color:#f3a847;"></i> <?php echo LANG_VALUE_162; ?>
                                        </h4>
                                        <a href="customer-billing-shipping-update.php" class="checkout-address-edit-link">
                                            <i class="fa fa-pencil"></i> Edit
                                        </a>
                                    </div>
                                    <ul class="checkout-address-list">
                                        <li>
                                            <span class="addr-label">Name:</span>
                                            <span class="addr-value"><strong><?php echo htmlspecialchars($_SESSION['customer']['cust_s_name'] ?? ''); ?></strong></span>
                                        </li>
                                        <?php if(!empty($_SESSION['customer']['cust_s_cname'])): ?>
                                        <li>
                                            <span class="addr-label">Company:</span>
                                            <span class="addr-value"><?php echo htmlspecialchars($_SESSION['customer']['cust_s_cname']); ?></span>
                                        </li>
                                        <?php endif; ?>
                                        <li>
                                            <span class="addr-label">Phone:</span>
                                            <span class="addr-value"><?php echo htmlspecialchars($_SESSION['customer']['cust_s_phone'] ?? ''); ?></span>
                                        </li>
                                        <li>
                                            <span class="addr-label">Country:</span>
                                            <span class="addr-value"><?php echo htmlspecialchars($s_country_name ?: 'Not specified'); ?></span>
                                        </li>
                                        <li>
                                            <span class="addr-label">Address:</span>
                                            <span class="addr-value"><?php echo nl2br(htmlspecialchars($_SESSION['customer']['cust_s_address'] ?? '')); ?></span>
                                        </li>
                                        <li>
                                            <span class="addr-label">City / State:</span>
                                            <span class="addr-value"><?php echo htmlspecialchars($_SESSION['customer']['cust_s_city'] ?? ''); ?>, <?php echo htmlspecialchars($_SESSION['customer']['cust_s_state'] ?? ''); ?> <?php echo htmlspecialchars($_SESSION['customer']['cust_s_zip'] ?? ''); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Payment Method Card -->
                        <div class="modern-cart-card" style="margin-bottom: 24px; padding: 24px;">
                            <div class="modern-cart-card-header" style="border:none; padding:0 0 16px 0; margin-bottom:18px; border-bottom:1px solid #f1f5f9;">
                                <h3 class="modern-cart-title" style="margin:0; font-size:18px;">
                                    <i class="fa fa-credit-card" style="color:#007185;"></i> <?php echo LANG_VALUE_33; ?>
                                </h3>
                            </div>

                            <?php if($checkout_access == 0): ?>
                                <div class="alert" style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 20px; color: #92400e;">
                                    <div style="display:flex; align-items:flex-start; gap:14px;">
                                        <div style="font-size:24px; color:#d97706; line-height:1;"><i class="fa fa-exclamation-triangle"></i></div>
                                        <div>
                                            <h5 style="margin:0 0 6px; font-weight:800; color:#b45309; font-size:15px;">Complete Shipping & Billing Details Required</h5>
                                            <p style="margin:0 0 12px; font-size:13px; line-height:1.5; color:#78350f;">
                                                Please make sure you have filled out your full billing and shipping information (name, address, city, state, zip code, phone, and country) in your customer profile to place this order.
                                            </p>
                                            <a href="customer-billing-shipping-update.php" class="btn btn-warning" style="background:#f59e0b; border:none; color:#fff; font-weight:700; border-radius:20px; padding:8px 18px; font-size:13px; text-decoration:none;">
                                                <i class="fa fa-pencil"></i> Update Information Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Interactive Payment Method Tiles -->
                                <div class="payment-method-tiles" style="display:flex; gap:14px; margin-bottom:20px; flex-wrap:wrap;">
                                    <div class="payment-tile" data-method="Bakong KHQR" title="Scan & Pay via ABA, ACLEDA, Bakong, or any Cambodian bank" style="border: 2px solid #e1251b; background: #fffaf0;">
                                        <div style="width:40px; height:40px; border-radius:50%; background:#fee2e2; display:flex; align-items:center; justify-content:center; color:#e1251b; font-size:20px; flex-shrink:0;">
                                            <i class="fa fa-qrcode"></i>
                                        </div>
                                        <div>
                                            <div style="display:flex; align-items:center; gap:6px;">
                                                <strong style="display:block; font-size:14px; color:#0f172a;">Bakong KHQR</strong>
                                                <span style="background:#e1251b; color:#fff; font-size:9px; font-weight:800; padding:1px 5px; border-radius:4px;">POPULAR</span>
                                            </div>
                                            <span style="font-size:11px; color:#64748b;">Scan & Pay with ABA, ACLEDA, Bakong, etc.</span>
                                        </div>
                                    </div>

                                    <div class="payment-tile" data-method="PayPal" title="Click to pay via PayPal">
                                        <div style="width:40px; height:40px; border-radius:50%; background:#eff6ff; display:flex; align-items:center; justify-content:center; color:#2563eb; font-size:20px; flex-shrink:0;">
                                            <i class="fa fa-paypal"></i>
                                        </div>
                                        <div>
                                            <strong style="display:block; font-size:14px; color:#0f172a;"><?php echo LANG_VALUE_36; ?></strong>
                                            <span style="font-size:11px; color:#64748b;">Instant online checkout with PayPal</span>
                                        </div>
                                    </div>

                                    <div class="payment-tile" data-method="Bank Deposit" title="Click to pay via Bank Deposit or Manual Transfer">
                                        <div style="width:40px; height:40px; border-radius:50%; background:#ecfdf5; display:flex; align-items:center; justify-content:center; color:#059669; font-size:18px; flex-shrink:0;">
                                            <i class="fa fa-university"></i>
                                        </div>
                                        <div>
                                            <strong style="display:block; font-size:14px; color:#0f172a;"><?php echo LANG_VALUE_38; ?></strong>
                                            <span style="font-size:11px; color:#64748b;">Direct Bank Deposit / Wire Transfer</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Selector Dropdown for Full Script Compatibility -->
                                <div class="form-group" style="margin-bottom:20px;">
                                    <label for="advFieldsStatus" style="font-size:13px; font-weight:700; color:#334155; margin-bottom:6px; display:block;">
                                        <?php echo LANG_VALUE_34; ?> *
                                    </label>
                                    <select name="payment_method" class="form-control" id="advFieldsStatus" style="height:44px; border-radius:8px; border:1.5px solid #cbd5e1; font-size:14px; font-weight:600; width:100%;">
                                        <option value=""><?php echo LANG_VALUE_35; ?></option>
                                        <option value="Bakong KHQR">Bakong Dynamic KHQR (Cambodia Banks)</option>
                                        <option value="PayPal"><?php echo LANG_VALUE_36; ?></option>
                                        <option value="Bank Deposit"><?php echo LANG_VALUE_38; ?></option>
                                    </select>
                                </div>

                                <!-- Bakong Dynamic KHQR Checkout Form -->
                                <form action="payment/bakong/init.php" method="post" id="bakong_form" style="margin-top:20px; display:none;">
                                    <input type="hidden" name="final_total" value="<?php echo $final_total; ?>">

                                    <div style="background: linear-gradient(135deg, #fff1f2 0%, #fff7ed 100%); border: 1.5px solid #fecdd3; border-radius: 14px; padding: 20px; margin-bottom: 20px;">
                                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px dashed #fca5a5;">
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <div style="background:#e1251b; color:#fff; font-weight:900; font-size:13px; padding:3px 8px; border-radius:5px;">KHQR</div>
                                                <strong style="color:#9f1239; font-size:15px;">Bakong Dynamic KHQR Payment</strong>
                                            </div>
                                            <span class="badge" style="background:#e1251b; color:#fff; font-size:11px; font-weight:700; padding:4px 10px; border-radius:12px;">Official NBC Bakong</span>
                                        </div>

                                        <p style="font-size:13px; color:#475569; margin-bottom:16px; line-height:1.5;">
                                            Pay instantly by scanning a unique <strong>Dynamic KHQR Code</strong> with any supported banking app in Cambodia. The payment will be automatically verified in real-time.
                                        </p>

                                        <!-- Currency Option: USD or KHR -->
                                        <div class="form-group" style="margin-bottom:16px;">
                                            <label style="font-size:13px; font-weight:700; color:#0f172a; margin-bottom:8px; display:block;">
                                                Choose Payment Currency for KHQR:
                                            </label>
                                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                                <label style="flex:1; min-width:140px; background:#fff; border:1.5px solid #cbd5e1; border-radius:10px; padding:12px 14px; cursor:pointer; display:flex; align-items:center; gap:10px; font-weight:600; font-size:13px; margin:0;">
                                                    <input type="radio" name="khqr_currency" value="USD" checked style="accent-color:#e1251b;">
                                                    <div>
                                                        <div style="color:#0f172a; font-weight:800;">USD ($)</div>
                                                        <div style="font-size:11px; color:#64748b;">$<?php echo number_format($final_total, 2); ?></div>
                                                    </div>
                                                </label>
                                                <label style="flex:1; min-width:140px; background:#fff; border:1.5px solid #cbd5e1; border-radius:10px; padding:12px 14px; cursor:pointer; display:flex; align-items:center; gap:10px; font-weight:600; font-size:13px; margin:0;">
                                                    <input type="radio" name="khqr_currency" value="KHR" style="accent-color:#e1251b;">
                                                    <div>
                                                        <div style="color:#0f172a; font-weight:800;">Khmer Riel (៛)</div>
                                                        <div style="font-size:11px; color:#b12704; font-weight:700;">≈ <?php echo format_price_khr($final_total); ?></div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Supported Bank Logos -->
                                        <div style="margin-bottom:14px;">
                                            <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#64748b; margin-bottom:6px;">Supported Mobile Apps:</div>
                                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                                <span style="font-size:10px; font-weight:700; color:#004b87; background:#fff; border:1px solid #cbd5e1; padding:2px 8px; border-radius:4px;">ABA Mobile</span>
                                                <span style="font-size:10px; font-weight:700; color:#002d62; background:#fff; border:1px solid #cbd5e1; padding:2px 8px; border-radius:4px;">ACLEDA</span>
                                                <span style="font-size:10px; font-weight:700; color:#e1251b; background:#fff; border:1px solid #cbd5e1; padding:2px 8px; border-radius:4px;">Bakong</span>
                                                <span style="font-size:10px; font-weight:700; color:#62a304; background:#fff; border:1px solid #cbd5e1; padding:2px 8px; border-radius:4px;">Wing Bank</span>
                                                <span style="font-size:10px; font-weight:700; color:#d62228; background:#fff; border:1px solid #cbd5e1; padding:2px 8px; border-radius:4px;">Canadia</span>
                                                <span style="font-size:10px; font-weight:700; color:#475569; background:#fff; border:1px solid #cbd5e1; padding:2px 8px; border-radius:4px;">+ All KHQR Banks</span>
                                            </div>
                                        </div>

                                        <!-- Merchant trust note -->
                                        <div style="background:#ffffff; border:1px solid #fed7aa; border-radius:8px; padding:10px 14px; font-size:12px; color:#475569; display:flex; align-items:center; gap:8px;">
                                            <i class="fa fa-shield" style="color:#e1251b; font-size:14px;"></i>
                                            <span>Official NBC Verified Merchant Payment • Instant Automated Confirmation</span>
                                        </div>
                                    </div>

                                    <button type="submit" name="form_khqr" class="btn-modern-checkout" style="max-width:400px; background: linear-gradient(180deg, #e1251b 0%, #b31217 100%); color:#fff !important; border-color:#990f13; box-shadow: 0 4px 14px rgba(225, 37, 27, 0.35);">
                                        <i class="fa fa-qrcode"></i> Generate KHQR & Pay (<?php echo format_price($final_total); ?>) <i class="fa fa-arrow-right"></i>
                                    </button>
                                </form>

                                <!-- PayPal Checkout Form -->
                                <form class="paypal" action="<?php echo BASE_URL; ?>payment/paypal/payment_process.php" method="post" id="paypal_form" target="_blank" style="margin-top:20px; display:none;">
                                    <input type="hidden" name="cmd" value="_xclick" />
                                    <input type="hidden" name="no_note" value="1" />
                                    <input type="hidden" name="lc" value="UK" />
                                    <input type="hidden" name="currency_code" value="USD" />
                                    <input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest" />
                                    <input type="hidden" name="final_total" value="<?php echo $final_total; ?>">
                                    
                                    <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:16px; margin-bottom:18px;">
                                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                                            <i class="fa fa-shield" style="color:#0284c7; font-size:18px;"></i>
                                            <strong style="color:#0369a1; font-size:14px;">Secure PayPal Redirection</strong>
                                        </div>
                                        <p style="margin:0; font-size:13px; color:#0c4a6e; line-height:1.5;">
                                            You will be securely redirected to PayPal to complete your payment of <strong>$<?php echo number_format($final_total, 2); ?> USD</strong>. After completing payment, your order will automatically be confirmed.
                                        </p>
                                    </div>

                                    <button type="submit" name="form1" class="btn-modern-checkout" style="max-width:380px;">
                                        <i class="fa fa-paypal"></i> Pay with PayPal (<?php echo format_price($final_total); ?>)
                                    </button>
                                </form>

                                <!-- Bank Deposit Checkout Form -->
                                <form action="payment/bank/init.php" method="post" id="bank_form" style="margin-top:20px; display:none;">
                                    <input type="hidden" name="amount" value="<?php echo $final_total; ?>">

                                    <!-- USD and Khmer Riel Conversion Box -->
                                    <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 18px; margin-bottom: 20px;">
                                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:12px; padding-bottom:10px; border-bottom:1px dashed #cbd5e1;">
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <i class="fa fa-exchange" style="color:#007185; font-size:16px;"></i>
                                                <strong style="color:#0f172a; font-size:14px;">Payment Amount & Exchange Rate</strong>
                                            </div>
                                            <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:700; border:1px solid #bae6fd; padding:4px 8px; border-radius:6px;">Rate: $1 = 4,100 ៛</span>
                                        </div>
                                        
                                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:14px;">
                                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px;">
                                                <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700; letter-spacing:0.5px;">Amount in USD</div>
                                                <div style="font-size:18px; font-weight:800; color:#0f172a; margin-top:2px;">$<?php echo number_format($final_total, 2); ?></div>
                                            </div>
                                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px;">
                                                <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700; letter-spacing:0.5px;">Amount in Khmer Riel</div>
                                                <div style="font-size:18px; font-weight:800; color:#b12704; margin-top:2px;"><?php echo format_price_khr($final_total); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bank Account Details Info Box -->
                                    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:18px; margin-bottom:20px;">
                                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                                            <i class="fa fa-university" style="color:#2563eb; font-size:16px;"></i>
                                            <strong style="color:#1e40af; font-size:14px;"><?php echo LANG_VALUE_43; ?></strong>
                                        </div>
                                        <div style="font-size:13px; color:#1e3a8a; line-height:1.6; background:#fff; border-radius:8px; padding:12px; border:1px solid #dbeafe;">
                                            <?php
                                            $statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
                                            $statement->execute();
                                            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($result as $row) {
                                                echo nl2br($row['bank_detail']);
                                            }
                                            ?>
                                        </div>
                                    </div>

                                    <!-- Transaction Note / Reference Input -->
                                    <div class="form-group" style="margin-bottom:20px;">
                                        <label for="transaction_info" style="font-size:13px; font-weight:700; color:#0f172a; margin-bottom:6px; display:block;">
                                            <?php echo LANG_VALUE_44; ?> * 
                                            <span style="font-size:12px; font-weight:normal; color:#64748b;">(<?php echo LANG_VALUE_45; ?>)</span>
                                        </label>
                                        <textarea name="transaction_info" id="transaction_info" class="form-control" rows="4" placeholder="Enter bank transfer reference number, transfer timestamp, or sender account name..." required style="border-radius:8px; border:1.5px solid #cbd5e1; font-size:13px; padding:10px 14px;"></textarea>
                                    </div>

                                    <button type="submit" name="form3" class="btn-modern-checkout" style="max-width:380px; background: linear-gradient(180deg, #10b981 0%, #059669 100%); color:#fff !important; border-color:#047857; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);">
                                        <i class="fa fa-check-circle"></i> Confirm & Submit Bank Order
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                    </div>

                    <!-- Right Column: Sticky Order Summary & Security Guarantees -->
                    <div class="col-lg-4 col-md-12">
                        <div class="modern-summary-card" style="position: sticky; top: 90px; margin-bottom: 30px;">
                            <h3 class="summary-title" style="display:flex; align-items:center; gap:8px;">
                                <i class="fa fa-calculator" style="color:#007185;"></i> Order Summary
                            </h3>

                            <div class="summary-row">
                                <span class="summary-label">Items Subtotal (<?php echo $cart_total_items; ?>):</span>
                                <span class="summary-value"><?php echo format_price($table_total_price); ?></span>
                            </div>

                            <?php if(isset($_SESSION['coupon']) && $discount_amount > 0): ?>
                            <div class="summary-row" style="color:#15803d; font-weight:600;">
                                <span class="summary-label">
                                    <i class="fa fa-tag"></i> Coupon (<code><?php echo htmlspecialchars($_SESSION['coupon']['code']); ?></code>):
                                </span>
                                <span class="summary-value">-<?php echo format_price($discount_amount); ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="summary-row">
                                <span class="summary-label">Shipping & Handling:</span>
                                <span class="summary-value">
                                    <?php echo ($shipping_cost > 0) ? format_price($shipping_cost) : '<span style="color:#059669; font-weight:700;">FREE</span>'; ?>
                                </span>
                            </div>

                            <div class="summary-divider"></div>

                            <div class="summary-row summary-total-row">
                                <span class="summary-total-label"><?php echo LANG_VALUE_82; ?>:</span>
                                <div style="text-align:right;">
                                    <span class="summary-total-value"><?php echo format_price($final_total); ?></span>
                                    <?php if (get_current_currency() === 'KHR'): ?>
                                        <div style="font-size:12px; color:#64748b; font-weight:500; margin-top:3px;">
                                            ($<?php echo number_format($final_total, 2); ?> USD)
                                        </div>
                                    <?php else: ?>
                                        <div style="font-size:12px; color:#64748b; font-weight:500; margin-top:3px;">
                                            (≈ <?php echo format_price_khr($final_total); ?>)
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="margin-top: 20px;">
                                <a href="cart.php" class="btn btn-default btn-block" style="background:#fff; border:1.5px solid #cbd5e1; border-radius:24px; color:#334155; font-weight:700; font-size:13px; padding:10px 16px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
                                    <i class="fa fa-angle-left"></i> Return to Shopping Cart
                                </a>
                            </div>

                            <!-- Trust and Security Guarantees -->
                            <div class="summary-security-box" style="margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                                <div class="security-item">
                                    <i class="fa fa-lock" style="color:#059669;"></i>
                                    <span>256-Bit SSL Encrypted & Secure Checkout</span>
                                </div>
                                <div class="security-item">
                                    <i class="fa fa-shield" style="color:#2563eb;"></i>
                                    <span>100% Money-Back & Quality Satisfaction Guarantee</span>
                                </div>
                                <div class="security-item">
                                    <i class="fa fa-truck" style="color:#f59e0b;"></i>
                                    <span>Fast & Insured Worldwide Delivery</span>
                                </div>

                                <div class="summary-payment-icons">
                                    <span><i class="fa fa-cc-paypal"></i> PayPal</span>
                                    <span><i class="fa fa-university"></i> Bank Transfer</span>
                                    <span><i class="fa fa-qrcode"></i> KHQR Riels</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
