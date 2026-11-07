<?php
/**
 * Bakong KHQR Order Creation API
 * Endpoint for initiating a Bakong KHQR order via JSON / AJAX
 */

header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once(__DIR__ . '/../admin/inc/config.php');
require_once(__DIR__ . '/../admin/inc/functions.php');
require_once(__DIR__ . '/../inc/bakong-config.php');

if (!isset($_SESSION['customer'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please sign in to place an order.'
    ]);
    exit;
}

if (!isset($_SESSION['cart_p_id']) || empty($_SESSION['cart_p_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Shopping cart is empty.'
    ]);
    exit;
}

$input = $_POST;
$raw = file_get_contents('php://input');
if (!empty($raw)) {
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $input = array_merge($input, $json);
    }
}

$currency = strtoupper($input['currency'] ?? 'USD');
if (!in_array($currency, ['USD', 'KHR'])) {
    $currency = 'USD';
}

// Calculate totals from session
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

$discount_amount = 0;
if (isset($_SESSION['coupon'])) {
    if ($_SESSION['coupon']['type'] === 'percentage') {
        $discount_amount = round(($table_total_price * (float)$_SESSION['coupon']['value']) / 100, 2);
    } else {
        $discount_amount = min($table_total_price, (float)$_SESSION['coupon']['value']);
    }
}

$final_total_usd = max(0, $table_total_price - $discount_amount + $shipping_cost);

$config = get_bakong_config($pdo);
if ($currency === 'KHR') {
    $charge_amount = round($final_total_usd * $config['khr_exchange_rate']);
} else {
    $charge_amount = $final_total_usd;
}

$payment_id = 'BK' . time() . rand(100, 999);
$order_number = 'ORD-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 5));

try {
    $pdo->beginTransaction();

    $stmt_pay = $pdo->prepare("INSERT INTO tbl_payment (
        customer_id, customer_name, customer_email, payment_date,
        txnid, paid_amount, card_number, card_cvv, card_month, card_year,
        bank_transaction_info, payment_method, payment_status, shipping_status, payment_id
    ) VALUES (?,?,?,NOW(),?,?,?,?,?,?,?,?,?,?,?)");

    $stmt_pay->execute([
        $_SESSION['customer']['cust_id'],
        $_SESSION['customer']['cust_name'],
        $_SESSION['customer']['cust_email'],
        '',
        $final_total_usd,
        '', '', '', '',
        "Bakong KHQR ({$order_number})",
        'Bakong KHQR',
        'Pending',
        'Pending',
        $payment_id
    ]);

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

    $khqr_info = generate_order_khqr([
        'payment_id'     => $payment_id,
        'order_number'   => $order_number,
        'amount'         => $charge_amount,
        'currency'       => $currency,
        'customer_id'    => $_SESSION['customer']['cust_id'],
        'customer_email' => $_SESSION['customer']['cust_email']
    ], $pdo);

    $pdo->commit();

    echo json_encode([
        'success'           => true,
        'payment_id'        => $payment_id,
        'order_number'      => $order_number,
        'amount'            => $charge_amount,
        'currency'          => $currency,
        'qr_image_data_uri' => $khqr_info['qr_image_data_uri'],
        'qr_string'         => $khqr_info['qr_string'],
        'md5'               => $khqr_info['md5'],
        'expires_at'        => $khqr_info['expires_at'],
        'seconds_left'      => $khqr_info['seconds_left'],
        'pay_url'           => 'khqr-pay.php?payment_id=' . urlencode($payment_id)
    ]);
    exit;

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Order generation failed: ' . $e->getMessage()
    ]);
    exit;
}
