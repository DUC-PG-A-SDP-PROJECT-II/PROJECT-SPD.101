<?php
ob_start();
session_start();
require_once(__DIR__ . '/../../admin/inc/config.php');
require_once(__DIR__ . '/../../admin/inc/functions.php');
require_once(__DIR__ . '/../../inc/bakong-config.php');

if (!isset($_SESSION['customer'])) {
    header('location: ../../login.php');
    exit;
}

if (!isset($_SESSION['cart_p_id']) || empty($_SESSION['cart_p_id'])) {
    header('location: ../../cart.php');
    exit;
}

// 1. Calculate Cart Totals
$table_total_price = 0;
$i = 0;
$arr_cart_p_id = [];
$arr_cart_size_name = [];
$arr_cart_color_name = [];
$arr_cart_p_qty = [];
$arr_cart_p_current_price = [];
$arr_cart_p_name = [];

foreach ($_SESSION['cart_p_id'] as $key => $value) {
    $i++;
    $arr_cart_p_id[$i] = $value;
    $arr_cart_size_name[$i] = $_SESSION['cart_size_name'][$key] ?? '';
    $arr_cart_color_name[$i] = $_SESSION['cart_color_name'][$key] ?? '';
    $arr_cart_p_qty[$i] = (int)($_SESSION['cart_p_qty'][$key] ?? 1);
    $arr_cart_p_current_price[$i] = (float)($_SESSION['cart_p_current_price'][$key] ?? 0);
    $arr_cart_p_name[$i] = $_SESSION['cart_p_name'][$key] ?? '';
    $table_total_price += ($arr_cart_p_current_price[$i] * $arr_cart_p_qty[$i]);
}

// 2. Shipping Cost
$shipping_cost = 0;
if (isset($_SESSION['customer']['cust_country'])) {
    $stmt_ship = $pdo->prepare("SELECT amount FROM tbl_shipping_cost WHERE country_id = ?");
    $stmt_ship->execute([$_SESSION['customer']['cust_country']]);
    $res_ship = $stmt_ship->fetch(PDO::FETCH_ASSOC);
    if ($res_ship) {
        $shipping_cost = (float)$res_ship['amount'];
    } else {
        $stmt_ship_all = $pdo->prepare("SELECT amount FROM tbl_shipping_cost_all WHERE sca_id = 1");
        $stmt_ship_all->execute();
        $res_all = $stmt_ship_all->fetch(PDO::FETCH_ASSOC);
        if ($res_all) {
            $shipping_cost = (float)$res_all['amount'];
        }
    }
}

// 3. Coupon Discount
$discount_amount = 0;
if (isset($_SESSION['coupon'])) {
    if ($_SESSION['coupon']['type'] === 'percentage') {
        $discount_amount = round(($table_total_price * (float)$_SESSION['coupon']['value']) / 100, 2);
    } else {
        $discount_amount = min($table_total_price, (float)$_SESSION['coupon']['value']);
    }
    $_SESSION['coupon']['discount'] = $discount_amount;
}

$final_total_usd = max(0, $table_total_price - $discount_amount + $shipping_cost);

// Currency preference
$requested_currency = strtoupper($_POST['khqr_currency'] ?? 'USD');
if (!in_array($requested_currency, ['USD', 'KHR'])) {
    $requested_currency = 'USD';
}

$config = get_bakong_config($pdo);
if ($requested_currency === 'KHR') {
    $charge_amount = round($final_total_usd * $config['khr_exchange_rate']);
} else {
    $charge_amount = $final_total_usd;
}

// Generate unique Payment ID and Order Number
$payment_id = 'BK' . time() . rand(100, 999);
$order_number = 'ORD-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 5));

$payment_date = date('Y-m-d H:i:s');

try {
    $pdo->beginTransaction();

    // Insert into tbl_payment
    $stmt_pay = $pdo->prepare("INSERT INTO tbl_payment (
        customer_id, customer_name, customer_email, payment_date,
        txnid, paid_amount, card_number, card_cvv, card_month, card_year,
        bank_transaction_info, payment_method, payment_status, shipping_status, payment_id
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt_pay->execute([
        $_SESSION['customer']['cust_id'],
        $_SESSION['customer']['cust_name'],
        $_SESSION['customer']['cust_email'],
        $payment_date,
        '', // txnid will be updated with Bakong transaction hash once paid
        $final_total_usd,
        '', '', '', '',
        "Bakong KHQR ({$order_number})",
        'Bakong KHQR',
        'Pending',
        'Pending',
        $payment_id
    ]);

    // Insert into tbl_order
    $stmt_ord = $pdo->prepare("INSERT INTO tbl_order (
        product_id, product_name, size, color, quantity, unit_price, payment_id
    ) VALUES (?,?,?,?,?,?,?)");

    for ($i = 1; $i <= count($arr_cart_p_name); $i++) {
        $stmt_ord->execute([
            $arr_cart_p_id[$i],
            $arr_cart_p_name[$i],
            $arr_cart_size_name[$i],
            $arr_cart_color_name[$i],
            $arr_cart_p_qty[$i],
            $arr_cart_p_current_price[$i],
            $payment_id
        ]);
    }

    // Generate Dynamic KHQR & Record into tbl_bakong_transaction
    $khqr_info = generate_order_khqr([
        'payment_id'     => $payment_id,
        'order_number'   => $order_number,
        'amount'         => $charge_amount,
        'currency'       => $requested_currency,
        'customer_id'    => $_SESSION['customer']['cust_id'],
        'customer_email' => $_SESSION['customer']['cust_email']
    ], $pdo);

    $pdo->commit();

    // Redirect to the dedicated KHQR Pay page
    header('location: ../../khqr-pay.php?payment_id=' . urlencode($payment_id));
    exit;

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['checkout_error'] = "Unable to generate Bakong KHQR: " . $e->getMessage();
    header('location: ../../checkout.php');
    exit;
}
