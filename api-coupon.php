<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once('admin/inc/config.php');

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : 'apply';

if ($action === 'remove') {
    unset($_SESSION['coupon']);
    echo json_encode([
        'success' => true,
        'message' => 'Coupon code removed successfully.'
    ]);
    exit;
}

if ($action === 'apply') {
    $code = isset($_POST['code']) ? strtoupper(trim(strip_tags($_POST['code']))) : (isset($_GET['code']) ? strtoupper(trim(strip_tags($_GET['code']))) : '');
    $subtotal = isset($_REQUEST['subtotal']) ? (float)$_REQUEST['subtotal'] : 0.00;

    if (empty($code)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a coupon code.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM tbl_coupon WHERE coupon_code = ?");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid coupon code. Please verify and try again.'
        ]);
        exit;
    }

    if ($coupon['status'] != 1) {
        echo json_encode([
            'success' => false,
            'message' => 'This coupon code is currently disabled.'
        ]);
        exit;
    }

    $today = date('Y-m-d');
    if (!empty($coupon['start_date']) && $coupon['start_date'] > $today) {
        echo json_encode([
            'success' => false,
            'message' => 'This coupon promo has not started yet.'
        ]);
        exit;
    }

    if (!empty($coupon['expire_date']) && $coupon['expire_date'] < $today) {
        echo json_encode([
            'success' => false,
            'message' => 'This coupon code has expired.'
        ]);
        exit;
    }

    if ($coupon['usage_limit'] > 0 && $coupon['usage_count'] >= $coupon['usage_limit']) {
        echo json_encode([
            'success' => false,
            'message' => 'This coupon has reached its maximum usage limit.'
        ]);
        exit;
    }

    if ($coupon['min_order_amount'] > 0 && $subtotal < (float)$coupon['min_order_amount']) {
        echo json_encode([
            'success' => false,
            'message' => 'Minimum order subtotal of $' . number_format($coupon['min_order_amount'], 2) . ' is required for this coupon.'
        ]);
        exit;
    }

    // Calculate discount
    $discount = 0.00;
    if ($coupon['coupon_type'] === 'percentage') {
        $discount = round(((float)$coupon['coupon_value'] / 100) * $subtotal, 2);
    } else {
        $discount = min($subtotal, (float)$coupon['coupon_value']);
    }

    // Store in session
    $_SESSION['coupon'] = [
        'id'       => (int)$coupon['coupon_id'],
        'code'     => $coupon['coupon_code'],
        'type'     => $coupon['coupon_type'],
        'value'    => (float)$coupon['coupon_value'],
        'discount' => $discount
    ];

    echo json_encode([
        'success'  => true,
        'message'  => 'Coupon ' . htmlspecialchars($coupon['coupon_code']) . ' applied! You saved $' . number_format($discount, 2),
        'code'     => $coupon['coupon_code'],
        'discount' => $discount,
        'new_total'=> max(0, $subtotal - $discount)
    ]);
    exit;
}
?>

