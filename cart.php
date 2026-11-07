<?php require_once('header.php'); ?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_cart = $row['banner_cart'];
}
?>

<?php
$error_message = '';
if(isset($_POST['form1'])) {

    $i = 0;
    $statement = $pdo->prepare("SELECT * FROM tbl_product");
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        $i++;
        $table_product_id[$i] = $row['p_id'];
        $table_quantity[$i] = $row['p_qty'];
    }

    $i=0;
    if(isset($_POST['product_id'])) {
        foreach($_POST['product_id'] as $val) {
            $i++;
            $arr1[$i] = $val;
        }
    }
    $i=0;
    if(isset($_POST['quantity'])) {
        foreach($_POST['quantity'] as $val) {
            $i++;
            $arr2[$i] = $val;
        }
    }
    $i=0;
    if(isset($_POST['product_name'])) {
        foreach($_POST['product_name'] as $val) {
            $i++;
            $arr3[$i] = $val;
        }
    }
    
    $allow_update = 1;
    if(!empty($arr1)) {
        for($i=1;$i<=count($arr1);$i++) {
            $temp_index = 0;
            for($j=1;$j<=count($table_product_id);$j++) {
                if($arr1[$i] == $table_product_id[$j]) {
                    $temp_index = $j;
                    break;
                }
            }
            if($temp_index > 0 && $table_quantity[$temp_index] < $arr2[$i]) {
                $allow_update = 0;
                $error_message .= '"'.$arr2[$i].'" items are not available for "'.$arr3[$i].'"\n';
            } else {
                $_SESSION['cart_p_qty'][$i] = $arr2[$i];
            }
        }
    }
    ?>
    
    <?php if($allow_update == 0): ?>
    	<script>alert('<?php echo $error_message; ?>');</script>
	<?php else: ?>
		<script>alert('All Items Quantity Update is Successful!');</script>
	<?php endif; ?>
    <?php

}

// Coupon Handling
$coupon_error = '';
$coupon_success = '';

if(isset($_POST['apply_coupon'])) {
    $coupon_code = strtoupper(trim(strip_tags($_POST['coupon_code'])));
    if(empty($coupon_code)) {
        $coupon_error = 'Please enter a coupon code.';
    } else {
        $cart_subtotal = 0;
        if(isset($_SESSION['cart_p_qty'])) {
            for($c_i=1; $c_i<=count($_SESSION['cart_p_qty']); $c_i++) {
                $cart_subtotal += $_SESSION['cart_p_current_price'][$c_i] * $_SESSION['cart_p_qty'][$c_i];
            }
        }
        $stmt = $pdo->prepare("SELECT * FROM tbl_coupon WHERE coupon_code=? AND status=1");
        $stmt->execute([$coupon_code]);
        $cpn = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$cpn) {
            $coupon_error = "Invalid or inactive coupon code.";
        } elseif(!empty($cpn['expire_date']) && $cpn['expire_date'] < date('Y-m-d')) {
            $coupon_error = "This coupon code has expired.";
        } elseif($cpn['min_order_amount'] > 0 && $cart_subtotal < $cpn['min_order_amount']) {
            $coupon_error = "Minimum subtotal of $" . number_format($cpn['min_order_amount'], 2) . " required.";
        } elseif($cpn['usage_limit'] > 0 && $cpn['usage_count'] >= $cpn['usage_limit']) {
            $coupon_error = "This coupon has reached its maximum usage limit.";
        } else {
            $discount = ($cpn['coupon_type'] === 'percentage') 
                ? round(($cpn['coupon_value'] / 100) * $cart_subtotal, 2)
                : min($cart_subtotal, (float)$cpn['coupon_value']);
            $_SESSION['coupon'] = [
                'id'       => (int)$cpn['coupon_id'],
                'code'     => $cpn['coupon_code'],
                'type'     => $cpn['coupon_type'],
                'value'    => (float)$cpn['coupon_value'],
                'discount' => $discount
            ];
            $coupon_success = "Coupon '{$cpn['coupon_code']}' applied! You save " . format_price($discount);
        }
    }
}

if(isset($_GET['remove_coupon'])) {
    unset($_SESSION['coupon']);
    header('location: cart.php');
    exit;
}
?>

<div class="page-banner" style="background-image: url(assets/uploads/<?php echo $banner_cart; ?>)">
    <div class="overlay"></div>
    <div class="page-banner-inner">
        <h1><?php echo LANG_VALUE_18; ?></h1>
    </div>
</div>

<div class="page modern-cart-container">
	<div class="container">
		<div class="row">
			<div class="col-md-12">

                <?php if(!isset($_SESSION['cart_p_id']) || empty($_SESSION['cart_p_id'])): ?>
                    <!-- Modern Empty Cart State -->
                    <div class="empty-cart-card">
                        <div class="empty-cart-icon-circle">
                            <i class="fa fa-shopping-basket"></i>
                        </div>
                        <h2 class="empty-cart-title">Your Shopping Cart is Empty</h2>
                        <p class="empty-cart-subtitle">Looks like you haven't added anything to your cart yet. Explore our featured deals and best-sellers to find something you love!</p>
                        <a href="index.php" class="btn-empty-cart-shop">
                            <i class="fa fa-shopping-cart"></i> Start Shopping Now
                        </a>
                    </div>
                <?php else: ?>

                    <!-- Modern Checkout Progress Steps -->
                    <div class="checkout-progress-bar">
                        <div class="progress-step active">
                            <span class="step-num"><i class="fa fa-shopping-bag"></i></span>
                            <span class="step-text">1. Shopping Cart</span>
                        </div>
                        <div class="progress-connector"></div>
                        <div class="progress-step">
                            <span class="step-num">2</span>
                            <span class="step-text">2. Checkout & Shipping</span>
                        </div>
                        <div class="progress-connector"></div>
                        <div class="progress-step">
                            <span class="step-num">3</span>
                            <span class="step-text">3. Order Confirmation</span>
                        </div>
                    </div>

                    <?php
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

                    // Query product data and available stock for products in cart
                    $stock_map = [];
                    $product_info_map = [];
                    if (!empty($arr_cart_p_id)) {
                        $unique_pids = array_unique($arr_cart_p_id);
                        $placeholders = implode(',', array_fill(0, count($unique_pids), '?'));
                        $stmt_stock = $pdo->prepare("SELECT p_id, p_name, p_qty, p_featured_photo FROM tbl_product WHERE p_id IN ($placeholders)");
                        $stmt_stock->execute(array_values($unique_pids));
                        while ($r = $stmt_stock->fetch(PDO::FETCH_ASSOC)) {
                            $stock_map[$r['p_id']] = (int)$r['p_qty'];
                            $product_info_map[$r['p_id']] = $r;
                        }
                    }

                    for($i=1;$i<=count($arr_cart_p_id);$i++) {
                        $row_price = $arr_cart_p_current_price[$i] * $arr_cart_p_qty[$i];
                        $table_total_price += $row_price;
                    }

                    $discount_amount = 0;
                    if (isset($_SESSION['coupon'])) {
                        if ($_SESSION['coupon']['type'] === 'percentage') {
                            $discount_amount = round(($table_total_price * $_SESSION['coupon']['value']) / 100, 2);
                        } else {
                            $discount_amount = min($table_total_price, $_SESSION['coupon']['value']);
                        }
                        $_SESSION['coupon']['discount'] = $discount_amount;
                    }
                    $payable_total = max(0, $table_total_price - $discount_amount);
                    ?>

                    <div class="row">
                        <!-- Left Column: Shopping Bag Table & Promo Box -->
                        <div class="col-lg-8 col-md-12">
                            <form action="" method="post" id="cartForm">
                                <?php $csrf->echoInputField(); ?>

                                <div class="modern-cart-card">
                                    <div class="modern-cart-card-header">
                                        <h3 class="modern-cart-title">
                                            <i class="fa fa-shopping-basket" style="color:#f3a847;"></i> Shopping Bag
                                            <span id="cart-top-count-pill" class="modern-cart-count-pill"><?php echo $cart_total_items; ?> item<?php echo ($cart_total_items > 1) ? 's' : ''; ?></span>
                                        </h3>
                                        <div id="auto-save-status" class="auto-save-status" title="Your cart automatically saves as you modify quantities">
                                            <span class="pulse-dot"></span>
                                            <span id="auto-save-text">Auto-Save Active</span>
                                        </div>
                                    </div>

                                    <div class="table-responsive" style="overflow-x: visible;">
                                        <table class="modern-cart-table" id="modernCartTable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 48%;">Product Details</th>
                                                    <th style="width: 14%; text-align: center;">Price</th>
                                                    <th style="width: 18%; text-align: center;">Quantity</th>
                                                    <th style="width: 14%; text-align: right;">Total</th>
                                                    <th style="width: 6%; text-align: center;"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php for($i=1;$i<=count($arr_cart_p_id);$i++): 
                                                    $item_pid = $arr_cart_p_id[$i];
                                                    $item_stock = isset($stock_map[$item_pid]) ? $stock_map[$item_pid] : 999;
                                                    $real_p_name = !empty($product_info_map[$item_pid]['p_name']) ? $product_info_map[$item_pid]['p_name'] : $arr_cart_p_name[$i];
                                                    $real_p_photo = !empty($product_info_map[$item_pid]['p_featured_photo']) ? $product_info_map[$item_pid]['p_featured_photo'] : $arr_cart_p_featured_photo[$i];
                                                    $item_row_total = $arr_cart_p_current_price[$i] * $arr_cart_p_qty[$i];
                                                    $_SESSION['cart_p_name'][$i] = $real_p_name;
                                                ?>
                                                <tr id="cart-row-<?php echo $i; ?>" class="cart-item-row" data-row-id="<?php echo $i; ?>" data-product-id="<?php echo $item_pid; ?>">
                                                    <td>
                                                        <div class="cart-product-cell">
                                                            <a href="product.php?id=<?php echo $item_pid; ?>" class="cart-product-thumb">
                                                                <img src="assets/uploads/<?php echo htmlspecialchars($real_p_photo); ?>" alt="<?php echo htmlspecialchars($real_p_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                            </a>
                                                            <div class="cart-product-details">
                                                                <a href="product.php?id=<?php echo $item_pid; ?>" class="cart-product-title">
                                                                    <?php echo htmlspecialchars($real_p_name); ?>
                                                                </a>
                                                                <div class="cart-variants-wrap">
                                                                    <?php if(!empty($arr_cart_size_name[$i])): ?>
                                                                        <span class="badge-variant"><i class="fa fa-tag"></i> Size: <?php echo htmlspecialchars($arr_cart_size_name[$i]); ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if(!empty($arr_cart_color_name[$i])): ?>
                                                                        <span class="badge-variant"><i class="fa fa-paint-brush"></i> Color: <?php echo htmlspecialchars($arr_cart_color_name[$i]); ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if($item_stock > 0): ?>
                                                                        <span class="badge-variant" style="color: #059669; background: #ecfdf5; border-color: #a7f3d0;"><i class="fa fa-check-circle"></i> In Stock</span>
                                                                    <?php else: ?>
                                                                        <span class="badge-variant" style="color: #dc2626; background: #fef2f2; border-color: #fecaca;"><i class="fa fa-times-circle"></i> Out of Stock</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <span class="cart-unit-price" data-unit-price="<?php echo $arr_cart_p_current_price[$i]; ?>">
                                                            <?php echo format_price($arr_cart_p_current_price[$i]); ?>
                                                        </span>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <input type="hidden" name="product_id[]" value="<?php echo $item_pid; ?>">
                                                        <input type="hidden" name="product_name[]" value="<?php echo htmlspecialchars($real_p_name, ENT_QUOTES, 'UTF-8'); ?>">

                                                        <!-- Modern Tactile Quantity Counter (+ / - Buttons) -->
                                                        <div class="modern-qty-counter" data-row-id="<?php echo $i; ?>" data-pid="<?php echo $item_pid; ?>" data-stock="<?php echo $item_stock; ?>">
                                                            <button type="button" class="btn-qty btn-qty-minus" aria-label="Decrease quantity" title="Decrease" <?php echo ($arr_cart_p_qty[$i] <= 1) ? 'disabled' : ''; ?>>
                                                                <i class="fa fa-minus"></i>
                                                            </button>
                                                            <input type="number" 
                                                                   class="cart-qty-input qty-input" 
                                                                   name="quantity[]" 
                                                                   value="<?php echo $arr_cart_p_qty[$i]; ?>" 
                                                                   min="1" 
                                                                   max="<?php echo $item_stock; ?>" 
                                                                   step="1"
                                                                   data-unit-price="<?php echo $arr_cart_p_current_price[$i]; ?>"
                                                                   data-row-id="<?php echo $i; ?>"
                                                                   data-stock="<?php echo $item_stock; ?>"
                                                                   data-prev-qty="<?php echo $arr_cart_p_qty[$i]; ?>"
                                                                   title="Quantity">
                                                            <button type="button" class="btn-qty btn-qty-plus" aria-label="Increase quantity" title="Increase" <?php echo ($item_stock > 0 && $arr_cart_p_qty[$i] >= $item_stock) ? 'disabled' : ''; ?>>
                                                                <i class="fa fa-plus"></i>
                                                            </button>
                                                        </div>
                                                        <span id="stock-warning-<?php echo $i; ?>" class="stock-warning-tag" style="display:none;"></span>
                                                    </td>
                                                    <td style="text-align: right;">
                                                        <span class="cart-row-total" id="row-total-<?php echo $i; ?>" data-row-total="<?php echo $item_row_total; ?>">
                                                            <?php echo format_price($item_row_total); ?>
                                                        </span>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <a href="cart-item-delete.php?id=<?php echo $item_pid; ?>&size=<?php echo $arr_cart_size_id[$i]; ?>&color=<?php echo $arr_cart_color_id[$i]; ?>" 
                                                           class="btn-cart-remove btn-ajax-remove" 
                                                           data-row-id="<?php echo $i; ?>" 
                                                           data-name="<?php echo htmlspecialchars($real_p_name, ENT_QUOTES, 'UTF-8'); ?>"
                                                           title="Remove this item">
                                                            <i class="fa fa-trash-o"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="cart-bottom-actions">
                                        <a href="index.php" class="btn-continue-shopping">
                                            <i class="fa fa-arrow-left"></i> Continue Shopping
                                        </a>
                                        <button type="submit" name="form1" class="btn btn-default" style="display:none;" id="btnManualUpdate">
                                            <i class="fa fa-refresh"></i> Update Cart
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Modern Coupon / Promo Box -->
                            <div class="modern-coupon-box">
                                <form action="cart.php" method="post">
                                    <?php $csrf->echoInputField(); ?>
                                    <h4 class="coupon-header-title">
                                        <i class="fa fa-ticket" style="color: #2563eb;"></i> Have a Promo Code or Voucher?
                                    </h4>
                                    <?php if(!empty($coupon_error)): ?>
                                        <div class="alert alert-danger" style="padding: 8px 12px; font-size: 13px; margin-bottom: 12px; border-radius: 6px;">
                                            <i class="fa fa-exclamation-circle"></i> <?php echo $coupon_error; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(!empty($coupon_success)): ?>
                                        <div class="alert alert-success" style="padding: 8px 12px; font-size: 13px; margin-bottom: 12px; border-radius: 6px;">
                                            <i class="fa fa-check-circle"></i> <?php echo $coupon_success; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="coupon-input-group">
                                        <input type="text" name="coupon_code" class="coupon-input-field" placeholder="Enter coupon code (e.g. WELCOME10)" value="<?php echo isset($_SESSION['coupon']['code']) ? htmlspecialchars($_SESSION['coupon']['code']) : ''; ?>">
                                        <button type="submit" name="apply_coupon" class="btn-apply-coupon">
                                            Apply Code
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Right Column: Sticky Order Summary Card -->
                        <div class="col-lg-4 col-md-12">
                            <div class="modern-summary-card">
                                <h4 class="summary-card-title">
                                    <span>Order Summary</span>
                                    <span class="badge" id="summary-items-count" style="background:#eef2f6; color:#0f172a; font-size:12px; font-weight:700;">
                                        <?php echo $cart_total_items; ?> item<?php echo ($cart_total_items > 1) ? 's' : ''; ?>
                                    </span>
                                </h4>

                                <!-- Subtotal -->
                                <div class="summary-row">
                                    <span>Subtotal</span>
                                    <span id="summary-subtotal-val" class="summary-val summary-subtotal-price" data-raw="<?php echo $table_total_price; ?>">
                                        <?php echo format_price($table_total_price); ?>
                                    </span>
                                </div>

                                <!-- Estimated Shipping -->
                                <div class="summary-row">
                                    <span>Shipping</span>
                                    <span class="summary-badge-free"><i class="fa fa-truck"></i> FREE</span>
                                </div>

                                <!-- Promo Code Discount Row -->
                                <div id="summary-discount-row" class="summary-discount-row" style="<?php echo (isset($_SESSION['coupon']) && $discount_amount > 0) ? '' : 'display:none;'; ?>">
                                    <span>
                                        <i class="fa fa-tag"></i> Coupon (<strong id="summary-coupon-code"><?php echo isset($_SESSION['coupon']['code']) ? htmlspecialchars($_SESSION['coupon']['code']) : ''; ?></strong>)
                                        <a href="cart.php?remove_coupon=1" style="font-size:11px; color:#dc2626; margin-left:6px; font-weight:normal;" title="Remove this coupon">[Remove]</a>
                                    </span>
                                    <span id="summary-discount-val">
                                        -<?php echo format_price($discount_amount); ?>
                                    </span>
                                </div>

                                <div class="summary-divider"></div>

                                <!-- Final Total -->
                                <div class="summary-total-row">
                                    <span class="summary-total-label">Final Total</span>
                                    <span id="summary-final-total-val" class="summary-final-price" data-raw="<?php echo $payable_total; ?>">
                                        <?php echo format_price($payable_total); ?>
                                    </span>
                                </div>

                                <!-- Dual Currency Display -->
                                <div id="summary-dual-currency" class="summary-dual-currency">
                                    <?php if (get_current_currency() === 'KHR'): ?>
                                        <span>($<?php echo number_format($payable_total, 2); ?> USD)</span>
                                    <?php else: ?>
                                        <span>(≈ <?php echo format_price_khr($payable_total); ?>)</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Proceed to Checkout Button -->
                                <a href="checkout.php" class="btn-modern-checkout">
                                    <i class="fa fa-lock"></i>
                                    <span>Proceed to Checkout</span>
                                    <i class="fa fa-arrow-right"></i>
                                </a>

                                <!-- Reassurance & Security -->
                                <div class="summary-security-box">
                                    <div class="security-item">
                                        <i class="fa fa-shield"></i>
                                        <span><strong>Safe & Secure Checkout</strong> with 256-bit encryption</span>
                                    </div>
                                    <div class="security-item">
                                        <i class="fa fa-check-circle"></i>
                                        <span><strong>30-Day Guarantee</strong> on genuine products</span>
                                    </div>
                                    <div class="security-item">
                                        <i class="fa fa-truck"></i>
                                        <span><strong>Fast Delivery</strong> throughout Cambodia</span>
                                    </div>

                                    <div class="summary-payment-icons">
                                        <span><i class="fa fa-qrcode"></i> Bakong KHQR</span>
                                        <span><i class="fa fa-cc-visa"></i> Visa</span>
                                        <span><i class="fa fa-cc-mastercard"></i> Mastercard</span>
                                        <span><i class="fa fa-money"></i> Cash On Delivery</span>
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
