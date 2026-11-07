<?php
/**
 * Bakong KHQR Webhook / IPN Endpoint
 * Receives external payment notifications from NBC Bakong / Payment Gateways
 * 
 * Strict Verification:
 * 1. Transaction status from NBC Bakong
 * 2. Order reference match
 * 3. Exact payment amount match
 * 4. Correct currency match (USD / KHR)
 * 5. Receiving merchant account match (rathanychoeur@bkrt)
 * 6. Anti-replay prevention (transaction hash uniqueness)
 */

header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__ . '/../admin/inc/config.php');
require_once(__DIR__ . '/../admin/inc/functions.php');
require_once(__DIR__ . '/../inc/bakong-config.php');

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true) ?? [];
$data = array_merge($_REQUEST, $payload);

$identifier = trim($data['payment_id'] ?? $data['externalRef'] ?? $data['md5'] ?? $data['hash'] ?? $data['order_number'] ?? '');

if (empty($identifier)) {
    http_response_code(400);
    echo json_encode([
        'responseCode'    => 1,
        'responseMessage' => 'Missing transaction identifier (payment_id, md5, externalRef, or hash).'
    ]);
    exit;
}

// Find transaction by any identifier
$stmt = $pdo->prepare("SELECT payment_id FROM tbl_bakong_transaction WHERE payment_id = ? OR khqr_md5 = ? OR order_number = ? OR trx_hash = ? LIMIT 1");
$stmt->execute([$identifier, $identifier, $identifier, $identifier]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trx) {
    http_response_code(404);
    echo json_encode([
        'responseCode'    => 1,
        'responseMessage' => 'Transaction not found for identifier: ' . $identifier
    ]);
    exit;
}

// Perform strict verification
$result = verify_bakong_payment($trx['payment_id'], $pdo);

if ($result['status'] === 'PAID') {
    http_response_code(200);
    echo json_encode([
        'responseCode'    => 0,
        'responseMessage' => 'Payment verified and confirmed successfully.',
        'data'            => [
            'order_number' => $result['order_number'] ?? '',
            'payment_id'   => $result['payment_id'] ?? '',
            'trx_hash'     => $result['trx_hash'] ?? ''
        ]
    ]);
    exit;
} elseif ($result['status'] === 'PENDING') {
    http_response_code(202);
    echo json_encode([
        'responseCode'    => 2,
        'responseMessage' => $result['message'] ?? 'Payment still pending verification.'
    ]);
    exit;
} else {
    http_response_code(422);
    echo json_encode([
        'responseCode'    => 3,
        'responseMessage' => $result['message'] ?? 'Payment verification failed.'
    ]);
    exit;
}

