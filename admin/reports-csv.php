<?php
ob_start();
session_start();
require_once('inc/config.php');

// Check admin authentication
if(!isset($_SESSION['user'])) {
	header('location: login.php');
	exit;
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$where = ["payment_status = 'Completed'"];
$params = [];

if (!empty($start_date)) {
    $where[] = "payment_date >= ?";
    $params[] = $start_date . " 00:00:00";
}
if (!empty($end_date)) {
    $where[] = "payment_date <= ?";
    $params[] = $end_date . " 23:59:59";
}

$where_sql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT * FROM tbl_payment WHERE $where_sql ORDER BY id DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "sales_report_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header row
fputcsv($output, [
    'Order ID / Payment ID',
    'Customer Name',
    'Customer Email',
    'Order Date',
    'Payment Method',
    'Txn ID',
    'Amount ($)',
    'Payment Status',
    'Shipping Status'
]);

foreach ($orders as $o) {
    fputcsv($output, [
        $o['payment_id'],
        $o['customer_name'],
        $o['customer_email'],
        $o['payment_date'],
        $o['payment_method'],
        $o['txnid'],
        number_format((float)$o['paid_amount'], 2),
        $o['payment_status'],
        $o['shipping_status']
    ]);
}

fclose($output);
exit;
?>

