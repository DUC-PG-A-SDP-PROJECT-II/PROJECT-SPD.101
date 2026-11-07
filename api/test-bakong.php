<?php
/**
 * Bakong KHQR Diagnostic & Testing Utility
 * Accessible for testing and verifying Bakong account and KHQR generation
 */

header('Content-Type: text/html; charset=UTF-8');
require_once(__DIR__ . '/../admin/inc/config.php');
require_once(__DIR__ . '/../inc/bakong-config.php');

use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

$config = get_bakong_config($pdo);
$test_amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 1.00;
$test_currency = isset($_GET['currency']) ? strtoupper($_GET['currency']) : 'USD';

$account_exists = null;
$account_error = null;
try {
    $accCheck = BakongKHQR::checkBakongAccount($config['account_id']);
    $account_exists = $accCheck->data['bakongAccountExists'] ?? false;
} catch (\Throwable $e) {
    $account_error = $e->getMessage();
}

$sample_qr = null;
$sample_md5 = null;
$sample_data_uri = null;
$gen_error = null;

try {
    $cur_code = ($test_currency === 'KHR') ? KHQRData::CURRENCY_KHR : KHQRData::CURRENCY_USD;
    $expires_timestamp_ms = floor(microtime(true) * 1000) + (15 * 60 * 1000);

    $individualInfo = new IndividualInfo(
        bakongAccountID: $config['account_id'],
        merchantName: $config['merchant_name'],
        merchantCity: $config['merchant_city'],
        currency: $cur_code,
        amount: $test_amount,
        expirationTimestamp: strval($expires_timestamp_ms),
        merchantCategoryCode: "5999"
    );

    $response = BakongKHQR::generateIndividual($individualInfo);
    if ($response->status['code'] === 0) {
        $sample_qr = $response->data['qr'];
        $sample_md5 = $response->data['md5'];
        $sample_data_uri = render_khqr_image_data_uri($sample_qr);
    } else {
        $gen_error = $response->status['message'];
    }
} catch (\Throwable $e) {
    $gen_error = $e->getMessage();
}

$token_status = 'Not configured';
if (!empty($config['api_token'])) {
    try {
        $is_exp = BakongKHQR::isExpiredToken($config['api_token']);
        $token_status = $is_exp ? '<span style="color:red; font-weight:bold;">EXPIRED</span>' : '<span style="color:green; font-weight:bold;">VALID</span>';
    } catch (\Throwable $e) {
        $token_status = 'Error checking: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bakong Dynamic KHQR Diagnostic Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; padding: 40px 20px; color: #1e293b; }
        .card { max-width: 650px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); padding: 32px; border: 1px solid #e2e8f0; }
        h1 { font-size: 20px; color: #e1251b; margin-top: 0; display: flex; align-items: center; gap: 8px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
        table td:first-child { font-weight: 700; width: 40%; color: #64748b; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 12px; word-break: break-all; }
        .qr-box { text-align: center; margin: 25px 0 15px; }
        .qr-box img { width: 220px; height: 220px; border: 3px solid #e1251b; border-radius: 12px; padding: 8px; }
    </style>
</head>
<body>

<div class="card">
    <h1><span style="background:#e1251b; color:#fff; padding:2px 8px; border-radius:4px; font-size:14px;">KHQR</span> Bakong Diagnostic & Status</h1>
    <p style="font-size: 13px; color: #64748b;">Testing real-time connection with National Bank of Cambodia (NBC) Bakong network.</p>

    <table>
        <tr>
            <td>PHP Version</td>
            <td><?php echo PHP_VERSION; ?> (Compatibility OK)</td>
        </tr>
        <tr>
            <td>Bakong Account ID</td>
            <td><code><?php echo htmlspecialchars(substr($config['account_id'], 0, 2) . '***' . strstr($config['account_id'], '@')); ?></code></td>
        </tr>
        <tr>
            <td>Account Existence (NBC API)</td>
            <td>
                <?php if ($account_exists === true): ?>
                    <span class="badge badge-success">✓ Verified Active Account</span>
                <?php elseif ($account_exists === false): ?>
                    <span class="badge badge-danger">✗ Account Not Found</span>
                <?php else: ?>
                    <span class="badge badge-warning">Error: <?php echo htmlspecialchars($account_error ?? 'Unknown'); ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>API Environment</td>
            <td><code><?php echo htmlspecialchars($config['environment']); ?></code> (<?php echo htmlspecialchars($config['api_base_url']); ?>)</td>
        </tr>
        <tr>
            <td>API Token Status</td>
            <td><?php echo $token_status; ?></td>
        </tr>
        <tr>
            <td>Merchant Name</td>
            <td><?php echo htmlspecialchars($config['merchant_name']); ?></td>
        </tr>
        <tr>
            <td>Test Payment Amount</td>
            <td><strong><?php echo number_format($test_amount, 2); ?> <?php echo $test_currency; ?></strong></td>
        </tr>
        <?php if ($sample_md5): ?>
        <tr>
            <td>Generated KHQR MD5</td>
            <td><code><?php echo htmlspecialchars($sample_md5); ?></code></td>
        </tr>
        <?php endif; ?>
    </table>

    <?php if ($sample_data_uri): ?>
        <div class="qr-box">
            <img src="<?php echo $sample_data_uri; ?>" alt="Dynamic KHQR Preview">
            <div style="font-size: 12px; color: #64748b; margin-top: 8px;">Scan with ABA, ACLEDA, or Bakong App</div>
        </div>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; font-size: 11px; word-break: break-all;">
            <strong>Raw KHQR String:</strong><br>
            <code><?php echo htmlspecialchars($sample_qr); ?></code>
        </div>
    <?php elseif ($gen_error): ?>
        <div style="background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; margin-top: 15px; font-size: 13px;">
            <strong>Generation Error:</strong> <?php echo htmlspecialchars($gen_error); ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 25px; text-align: center;">
        <a href="../checkout.php" style="font-size: 13px; font-weight: 700; color: #007185; text-decoration: none;">&larr; Back to Checkout</a>
    </div>
</div>

</body>
</html>
