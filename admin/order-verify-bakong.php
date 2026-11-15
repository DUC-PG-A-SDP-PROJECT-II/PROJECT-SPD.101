<?php
ob_start();
session_start();
require_once(__DIR__ . '/inc/config.php');
require_once(__DIR__ . '/../inc/bakong-config.php');

// Security: Verify user is logged in as admin
if (!isset($_SESSION['user'])) {
    header('location: login.php');
    exit;
}

$payment_id = trim($_GET['payment_id'] ?? '');

if (empty($payment_id)) {
    header('location: order.php');
    exit;
}

$res = verify_bakong_payment($payment_id, $pdo);

if ($res['status'] === 'PAID') {
    $hash = $res['trx_hash'] ?? '';
    header('location: order.php?msg=bakong_paid&id=' . urlencode($payment_id) . '&hash=' . urlencode($hash));
    exit;
} elseif ($res['status'] === 'PENDING') {
    $detail = $res['api_error'] ?? $res['message'] ?? 'Transaction is still awaiting customer scan/transfer.';
    header('location: order.php?msg=bakong_pending&id=' . urlencode($payment_id) . '&detail=' . urlencode($detail));
    exit;
} else {
    $detail = $res['message'] ?? 'Payment verification failed.';
    header('location: order.php?msg=bakong_failed&id=' . urlencode($payment_id) . '&detail=' . urlencode($detail));
    exit;
}
