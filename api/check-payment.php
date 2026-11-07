<?php
/**
 * Bakong KHQR Payment Verification API
 * Method: GET / POST
 * Params: payment_id, action (optional: 'check' | 'retry')
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();
require_once(__DIR__ . '/../admin/inc/config.php');
require_once(__DIR__ . '/../admin/inc/functions.php');
require_once(__DIR__ . '/../inc/bakong-config.php');

// Accept both GET, POST, FormData and raw JSON input
$input = array_merge($_GET, $_POST, $_REQUEST);
if (empty($input['payment_id'])) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = array_merge($input, $json);
        }
    }
}

$payment_id = trim($input['payment_id'] ?? '');
$action = trim($input['action'] ?? 'check');

if (empty($payment_id)) {
    echo json_encode([
        'success' => false,
        'status'  => 'ERROR',
        'message' => 'Missing required payment_id parameter.'
    ]);
    exit;
}

// 1. Fetch transaction record
$stmt = $pdo->prepare("SELECT * FROM tbl_bakong_transaction WHERE payment_id = ? OR khqr_md5 = ? LIMIT 1");
$stmt->execute([$payment_id, $payment_id]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trx) {
    echo json_encode([
        'success' => false,
        'status'  => 'NOT_FOUND',
        'message' => 'Order payment record not found.'
    ]);
    exit;
}

// 2. Handle Retry / Refresh action if requested and not yet paid
if ($action === 'retry') {
    if ($trx['payment_status'] === 'PAID') {
        echo json_encode([
            'success'      => true,
            'status'       => 'PAID',
            'order_number' => $trx['order_number'],
            'trx_hash'     => $trx['trx_hash'],
            'message'      => 'Order has already been paid!',
            'redirect_url' => 'payment_success.php?payment_id=' . urlencode($trx['payment_id'])
        ]);
        exit;
    }

    try {
        $new_khqr = generate_order_khqr([
            'payment_id'     => $trx['payment_id'],
            'order_number'   => $trx['order_number'],
            'amount'         => (float)$trx['amount'],
            'currency'       => $trx['currency'],
            'customer_id'    => $trx['customer_id'],
            'customer_email' => $trx['customer_email']
        ], $pdo);

        echo json_encode([
            'success'           => true,
            'status'            => 'RENEWED',
            'message'           => 'New dynamic KHQR generated successfully.',
            'qr_image_data_uri' => $new_khqr['qr_image_data_uri'],
            'qr_string'         => $new_khqr['qr_string'],
            'md5'               => $new_khqr['md5'],
            'deeplink'          => $new_khqr['deeplink'],
            'expires_at'        => $new_khqr['expires_at'],
            'seconds_left'      => $new_khqr['seconds_left']
        ]);
        exit;
    } catch (\Throwable $e) {
        echo json_encode([
            'success' => false,
            'status'  => 'ERROR',
            'message' => 'Failed to regenerate KHQR: ' . $e->getMessage()
        ]);
        exit;
    }
}

// 3. Regular Payment Verification Check
$res = verify_bakong_payment($trx['payment_id'], $pdo);

// Re-fetch fresh transaction state after verification check
$stmt_fresh = $pdo->prepare("SELECT payment_status, expires_at, trx_hash FROM tbl_bakong_transaction WHERE id = ?");
$stmt_fresh->execute([$trx['id']]);
$fresh = $stmt_fresh->fetch(PDO::FETCH_ASSOC);

// Calculate remaining countdown seconds from fresh state
$expires_timestamp = strtotime($fresh['expires_at'] ?? $trx['expires_at']);
$seconds_left = max(0, $expires_timestamp - time());

$res['order_number'] = $trx['order_number'];
$res['payment_id']   = $trx['payment_id'];
$res['seconds_left'] = $seconds_left;
if (!empty($fresh['trx_hash'])) {
    $res['trx_hash'] = $fresh['trx_hash'];
}

if ($res['status'] === 'PAID') {
    $res['redirect_url'] = 'payment_success.php?payment_id=' . urlencode($trx['payment_id']);
}

echo json_encode($res);
exit;
