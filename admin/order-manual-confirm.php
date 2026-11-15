<?php
ob_start();
session_start();
require_once(__DIR__ . '/inc/config.php');

if (!isset($_SESSION['user'])) {
    header('location: login.php');
    exit;
}

$payment_id = trim($_POST['payment_id'] ?? $_GET['payment_id'] ?? '');

if (empty($payment_id)) {
    header('location: order.php');
    exit;
}

// Fetch payment record
$stmt = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_id = ? LIMIT 1");
$stmt->execute([$payment_id]);
$pay = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pay) {
    header('location: order.php');
    exit;
}

$manual_ref = trim($_POST['manual_ref'] ?? '');
$admin_note = trim($_POST['admin_note'] ?? '');

if (empty($manual_ref)) {
    $manual_ref = !empty($pay['txnid']) ? $pay['txnid'] : ('MANUAL-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6)));
}

try {
    $pdo->beginTransaction();

    // 1. Update tbl_payment to Completed
    if ($pay['payment_status'] !== 'Completed') {
        $stmt_pay = $pdo->prepare("UPDATE tbl_payment SET payment_status = 'Completed', txnid = ? WHERE payment_id = ?");
        $stmt_pay->execute([$manual_ref, $payment_id]);

        // 2. Decrement stock for ordered items if not already done
        $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM tbl_order WHERE payment_id = ?");
        $stmt_items->execute([$payment_id]);
        $ordered_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ordered_items as $item) {
            $pid = (int)$item['product_id'];
            $qty = (int)$item['quantity'];
            $stmt_stk = $pdo->prepare("UPDATE tbl_product SET p_qty = GREATEST(0, p_qty - ?) WHERE p_id = ?");
            $stmt_stk->execute([$qty, $pid]);
        }
    }

    // 3. Update tbl_bakong_transaction if applicable
    $stmt_b = $pdo->prepare("SELECT id FROM tbl_bakong_transaction WHERE payment_id = ?");
    $stmt_b->execute([$payment_id]);
    $trx_b = $stmt_b->fetch(PDO::FETCH_ASSOC);
    if ($trx_b) {
        $note_info = !empty($admin_note) ? ('Admin Note: ' . $admin_note) : 'Manually approved by Admin';
        $stmt_up_b = $pdo->prepare("UPDATE tbl_bakong_transaction SET payment_status = 'PAID', trx_hash = ?, raw_response = ?, updated_at = NOW() WHERE payment_id = ?");
        $stmt_up_b->execute([$manual_ref, json_encode(['manual_confirmation' => true, 'note' => $note_info, 'confirmed_by' => $_SESSION['user']['full_name'] ?? 'Admin', 'at' => date('Y-m-d H:i:s')]), $payment_id]);
    }

    $pdo->commit();

    header('location: order.php?msg=manual_confirmed&id=' . urlencode($payment_id) . '&ref=' . urlencode($manual_ref));
    exit;

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('location: order.php?msg=manual_error&id=' . urlencode($payment_id) . '&detail=' . urlencode($e->getMessage()));
    exit;
}
