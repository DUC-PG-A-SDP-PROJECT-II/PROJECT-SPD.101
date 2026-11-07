<?php
/**
 * Bakong KHQR Central Configuration & Payment Engine
 * Compatibility: PHP 8.2.12
 * Supports: fidele007/bakong-khqr-php, endroid/qr-code, Bakong Open API
 */

if (!defined('BASE_URL')) {
    // If included from subfolders or directly, ensure BASE_URL and DB are available
    if (file_exists(__DIR__ . '/../admin/inc/config.php')) {
        require_once(__DIR__ . '/../admin/inc/config.php');
    }
}

// Ensure Composer autoload is loaded
$autoload_paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    'vendor/autoload.php'
];
foreach ($autoload_paths as $ap) {
    if (file_exists($ap)) {
        require_once($ap);
        break;
    }
}

use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;
use KHQR\Models\SourceInfo;
use KHQR\Exceptions\KHQRException;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

/**
 * Get Bakong KHQR configuration from tbl_settings or defaults
 */
function get_bakong_config($pdo = null) {
    global $pdo;

    $defaults = [
        'account_id'      => 'rathanychoeur@bkrt',
        'merchant_name'   => 'Rathany Choeur',
        'merchant_city'   => 'Phnom Penh',
        'environment'     => 'production', // 'production' or 'sandbox'
        'api_token'       => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkYXRhIjp7ImlkIjoiMDQ4Nzg3NTQ0NTUwNDcwOSJ9LCJpYXQiOjE3OTM3NzE0MTQsImV4cCI6MTgwMTU0NzQxNH0.BdOLbeEhA4Dy2eFa70sF2cG4TZei_ekfLWus9PQ55Us',
        'expiry_minutes'  => 15,
        'status'          => 1, // 1=active, 0=inactive
        'currency'        => 'USD', // default order currency
        'khr_exchange_rate' => 4100 // 1 USD = 4100 KHR
    ];

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT bakong_account_id, bakong_merchant_name, bakong_merchant_city, bakong_api_token, bakong_environment, bakong_qr_expiry_minutes, bakong_status, usd_to_khr_rate FROM tbl_settings WHERE id=1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if (!empty($row['bakong_account_id'])) $defaults['account_id'] = trim($row['bakong_account_id']);
                if (!empty($row['bakong_merchant_name'])) $defaults['merchant_name'] = trim($row['bakong_merchant_name']);
                if (!empty($row['bakong_merchant_city'])) $defaults['merchant_city'] = trim($row['bakong_merchant_city']);
                if (!empty($row['bakong_environment'])) $defaults['environment'] = trim($row['bakong_environment']);
                if (!empty($row['bakong_api_token'])) $defaults['api_token'] = trim($row['bakong_api_token']);
                if (!empty($row['bakong_qr_expiry_minutes'])) $defaults['expiry_minutes'] = (int)$row['bakong_qr_expiry_minutes'];
                if (isset($row['bakong_status'])) $defaults['status'] = (int)$row['bakong_status'];
                if (!empty($row['usd_to_khr_rate'])) $defaults['khr_exchange_rate'] = (int)$row['usd_to_khr_rate'];
            }
        } catch (\Throwable $e) {
            // Keep defaults if query fails or columns not yet added
        }
    }

    $is_test = ($defaults['environment'] === 'sandbox' || $defaults['environment'] === 'sit');
    $defaults['is_test'] = $is_test;
    $defaults['api_base_url'] = $is_test ? 'https://sit-api-bakong.nbc.gov.kh' : 'https://api-bakong.nbc.gov.kh';

    return $defaults;
}

/**
 * Generate QR Image Data URI using Endroid QR Code
 * Uses SvgWriter for pixel-perfect sharpness and 0-dependency resilience,
 * with PngWriter support if GD extension is active.
 */
function render_khqr_image_data_uri($qr_string) {
    try {
        $qrCode = new QrCode(
            data: $qr_string,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 320,
            margin: 10,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        // Try PNG if GD is loaded, otherwise use SVG
        if (extension_loaded('gd')) {
            try {
                $writer = new PngWriter();
                $result = $writer->write($qrCode);
                return $result->getDataUri();
            } catch (\Throwable $e) {
                // Fall back to SVG
            }
        }

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        return $result->getDataUri();
    } catch (\Throwable $e) {
        // Fallback: return encoded SVG manually or empty
        return '';
    }
}

/**
 * Generate Dynamic KHQR for an order
 * 
 * @param array $params [
 *    'payment_id' => string,
 *    'order_number' => string,
 *    'amount' => float,
 *    'currency' => 'USD'|'KHR',
 *    'customer_id' => int,
 *    'customer_email' => string
 * ]
 * @param PDO $pdo
 * @return array Result containing QR, MD5, image Data URI, and expiry
 */
function generate_order_khqr(array $params, PDO $pdo) {
    $config = get_bakong_config($pdo);

    $payment_id = $params['payment_id'];
    $order_number = $params['order_number'] ?? ('ORD-' . $payment_id);
    $customer_id = (int)($params['customer_id'] ?? 0);
    $customer_email = $params['customer_email'] ?? '';
    $currency = strtoupper($params['currency'] ?? 'USD');
    $amount = (float)($params['amount'] ?? 0);
    $amount_khr = 0;

    // Convert amount if KHR
    if ($currency === 'KHR') {
        $khqr_currency = KHQRData::CURRENCY_KHR;
        $amount_khr = $amount;
    } else {
        $khqr_currency = KHQRData::CURRENCY_USD;
        $amount_khr = round($amount * $config['khr_exchange_rate']);
    }

    $expiry_minutes = $config['expiry_minutes'];
    $expires_timestamp_ms = floor(microtime(true) * 1000) + ($expiry_minutes * 60 * 1000);
    $expires_at = date('Y-m-d H:i:s', time() + ($expiry_minutes * 60));

    // Create Individual Dynamic KHQR using the official SDK
    $individualInfo = new IndividualInfo(
        bakongAccountID: $config['account_id'],
        merchantName: $config['merchant_name'],
        merchantCity: $config['merchant_city'],
        currency: $khqr_currency,
        amount: $amount,
        expirationTimestamp: strval($expires_timestamp_ms),
        merchantCategoryCode: "5999"
    );

    $response = BakongKHQR::generateIndividual($individualInfo);

    if (!isset($response->status['code']) || $response->status['code'] !== 0) {
        $errMsg = $response->status['message'] ?? 'Failed to generate Bakong KHQR';
        throw new \Exception($errMsg);
    }

    $qr_string = $response->data['qr'];
    $md5_hash  = $response->data['md5'];

    // Generate Deep Link if possible
    $deeplink = '';
    try {
        $sourceInfo = new SourceInfo(
            appIconUrl: (defined('BASE_URL') ? BASE_URL : '') . 'assets/uploads/bakong-logo.png',
            appName: 'eCommerce Store',
            appDeepLinkCallback: (defined('BASE_URL') ? BASE_URL : '') . 'khqr-pay.php?payment_id=' . urlencode($payment_id)
        );
        $dlResponse = BakongKHQR::generateDeepLink($qr_string, $sourceInfo, $config['is_test']);
        if (isset($dlResponse->data->shortLink)) {
            $deeplink = $dlResponse->data->shortLink;
        }
    } catch (\Throwable $e) {
        // Deep link is optional
    }

    // Generate QR image data URI
    $qr_image_data_uri = render_khqr_image_data_uri($qr_string);

    // Save or update transaction in tbl_bakong_transaction
    $stmt_check = $pdo->prepare("SELECT id FROM tbl_bakong_transaction WHERE payment_id = ?");
    $stmt_check->execute([$payment_id]);
    $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $stmt_up = $pdo->prepare("UPDATE tbl_bakong_transaction SET
            amount = ?,
            currency = ?,
            amount_khr = ?,
            khqr_string = ?,
            khqr_md5 = ?,
            expires_at = ?,
            payment_status = 'PENDING',
            updated_at = NOW()
            WHERE payment_id = ?");
        $stmt_up->execute([$amount, $currency, $amount_khr, $qr_string, $md5_hash, $expires_at, $payment_id]);
    } else {
        $stmt_ins = $pdo->prepare("INSERT INTO tbl_bakong_transaction (
            payment_id, order_number, customer_id, customer_email,
            amount, currency, amount_khr,
            bakong_account_id, merchant_name, merchant_city,
            khqr_string, khqr_md5, payment_status,
            expires_at, created_at
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
        $stmt_ins->execute([
            $payment_id, $order_number, $customer_id, $customer_email,
            $amount, $currency, $amount_khr,
            $config['account_id'], $config['merchant_name'], $config['merchant_city'],
            $qr_string, $md5_hash, 'PENDING',
            $expires_at
        ]);
    }

    return [
        'payment_id'         => $payment_id,
        'order_number'       => $order_number,
        'amount'             => $amount,
        'currency'           => $currency,
        'amount_khr'         => $amount_khr,
        'account_id'         => $config['account_id'],
        'merchant_name'      => $config['merchant_name'],
        'merchant_city'      => $config['merchant_city'],
        'qr_string'          => $qr_string,
        'md5'                => $md5_hash,
        'qr_image_data_uri'  => $qr_image_data_uri,
        'deeplink'           => $deeplink,
        'expires_at'         => $expires_at,
        'expires_timestamp_ms' => $expires_timestamp_ms,
        'seconds_left'       => max(0, strtotime($expires_at) - time())
    ];
}

/**
 * Verify Bakong KHQR Payment
 * 
 * Strict verification process:
 * 1. Checks payment status using KHQR MD5 via Bakong Open API.
 * 2. Verifies transaction belongs to receiving Bakong account (rathanychoeur@bkrt).
 * 3. Verifies exact amount and currency.
 * 4. Matches payment with the correct order.
 * 5. Prevents transaction hash reuse across orders.
 * 6. Updates order status to PAID only after all checks pass.
 * 7. Never trusts browser input.
 */
function verify_bakong_payment($identifier, PDO $pdo) {
    $config = get_bakong_config($pdo);

    // Look up transaction by payment_id or khqr_md5
    $stmt = $pdo->prepare("SELECT * FROM tbl_bakong_transaction WHERE payment_id = ? OR khqr_md5 = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $trx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trx) {
        return [
            'success' => false,
            'status'  => 'NOT_FOUND',
            'message' => 'Transaction not found in database.'
        ];
    }

    // If already marked PAID in our database, return success immediately
    if ($trx['payment_status'] === 'PAID') {
        return [
            'success'      => true,
            'status'       => 'PAID',
            'order_number' => $trx['order_number'],
            'payment_id'   => $trx['payment_id'],
            'trx_hash'     => $trx['trx_hash'],
            'message'      => 'Payment already completed and verified.'
        ];
    }

    $is_time_expired = (strtotime($trx['expires_at']) <= time());

    // Token check
    if (empty($config['api_token'])) {
        if ($is_time_expired && $trx['payment_status'] !== 'PAID') {
            $stmt_exp = $pdo->prepare("UPDATE tbl_bakong_transaction SET payment_status = 'EXPIRED', updated_at = NOW() WHERE id = ?");
            $stmt_exp->execute([$trx['id']]);

            return [
                'success' => false,
                'status'  => 'EXPIRED',
                'token_configured' => false,
                'message' => 'Dynamic KHQR has expired. Please regenerate a new QR code to pay.'
            ];
        }

        return [
            'success' => false,
            'status'  => 'PENDING',
            'token_configured' => false,
            'message' => 'Awaiting customer scan & transfer...'
        ];
    }

    // Call Bakong Open API checkTransactionByMD5
    $api_response = null;
    try {
        $bakongKhqr = new BakongKHQR($config['api_token']);
        $api_response = $bakongKhqr->checkTransactionByMD5($trx['khqr_md5'], $config['is_test']);
    } catch (KHQRException $e) {
        if ($is_time_expired && $trx['payment_status'] !== 'PAID') {
            $stmt_exp = $pdo->prepare("UPDATE tbl_bakong_transaction SET payment_status = 'EXPIRED', updated_at = NOW() WHERE id = ?");
            $stmt_exp->execute([$trx['id']]);

            return [
                'success' => false,
                'status'  => 'EXPIRED',
                'message' => 'Dynamic KHQR has expired. Please regenerate a new QR code to pay.'
            ];
        }

        // Handle common API codes
        if ($e->getCode() === 6) {
            return [
                'success' => false,
                'status'  => 'PENDING',
                'api_error' => 'Bakong API token invalid or expired. Please renew token in Admin Settings.',
                'message' => 'Awaiting customer scan & transfer...'
            ];
        }
        return [
            'success' => false,
            'status'  => 'PENDING',
            'api_error' => $e->getMessage(),
            'message' => 'Waiting for transaction confirmation from Bakong...'
        ];
    } catch (\Throwable $e) {
        if ($is_time_expired && $trx['payment_status'] !== 'PAID') {
            $stmt_exp = $pdo->prepare("UPDATE tbl_bakong_transaction SET payment_status = 'EXPIRED', updated_at = NOW() WHERE id = ?");
            $stmt_exp->execute([$trx['id']]);

            return [
                'success' => false,
                'status'  => 'EXPIRED',
                'message' => 'Dynamic KHQR has expired. Please regenerate a new QR code to pay.'
            ];
        }

        return [
            'success' => false,
            'status'  => 'PENDING',
            'api_error' => $e->getMessage(),
            'message' => 'Connecting to Bakong network...'
        ];
    }

    // Evaluate Bakong API Response
    // If not found yet on Bakong:
    if (!isset($api_response['responseCode']) || $api_response['responseCode'] !== 0 || empty($api_response['data'])) {
        if ($is_time_expired && $trx['payment_status'] !== 'PAID') {
            $stmt_exp = $pdo->prepare("UPDATE tbl_bakong_transaction SET payment_status = 'EXPIRED', updated_at = NOW() WHERE id = ?");
            $stmt_exp->execute([$trx['id']]);

            return [
                'success' => false,
                'status'  => 'EXPIRED',
                'message' => 'Dynamic KHQR has expired. Please regenerate a new QR code to pay.'
            ];
        }

        return [
            'success' => false,
            'status'  => 'PENDING',
            'message' => 'Waiting for customer transfer via banking app...'
        ];
    }

    $data = $api_response['data'];

    // Extract transaction details
    $trx_hash       = trim($data['hash'] ?? '');
    $to_account_id  = strtolower(trim($data['toAccountId'] ?? ''));
    $from_account_id= trim($data['fromAccountId'] ?? '');
    $trx_currency   = strtoupper(trim($data['currency'] ?? ''));
    $trx_amount     = (float)($data['amount'] ?? 0);

    // ==========================================
    // SECURITY CHECK 1: Receiving Bakong Account
    // ==========================================
    $expected_account = strtolower(trim($trx['bakong_account_id']));
    if ($to_account_id !== $expected_account) {
        return [
            'success' => false,
            'status'  => 'FAILED',
            'message' => "Security check failed: Destination account mismatch (expected: {$expected_account}, received: {$to_account_id})."
        ];
    }

    // ==========================================
    // SECURITY CHECK 2: Currency
    // ==========================================
    $expected_currency = strtoupper(trim($trx['currency']));
    if ($trx_currency !== $expected_currency) {
        return [
            'success' => false,
            'status'  => 'FAILED',
            'message' => "Security check failed: Currency mismatch (expected: {$expected_currency}, received: {$trx_currency})."
        ];
    }

    // ==========================================
    // SECURITY CHECK 3: Amount Verification
    // ==========================================
    $expected_amount = (float)$trx['amount'];
    // Allow slight float epsilon tolerance 0.001
    if (($expected_amount - $trx_amount) > 0.009) {
        return [
            'success' => false,
            'status'  => 'FAILED',
            'message' => "Security check failed: Paid amount insufficient (expected: {$expected_amount}, received: {$trx_amount})."
        ];
    }

    // ==========================================
    // SECURITY CHECK 4: Prevent Duplicate Hash Reuse
    // ==========================================
    if (!empty($trx_hash)) {
        $stmt_dup = $pdo->prepare("SELECT id, payment_id FROM tbl_bakong_transaction WHERE trx_hash = ? AND payment_id != ?");
        $stmt_dup->execute([$trx_hash, $trx['payment_id']]);
        $dup = $stmt_dup->fetch(PDO::FETCH_ASSOC);
        if ($dup) {
            return [
                'success' => false,
                'status'  => 'FAILED',
                'message' => "Security check failed: Transaction hash {$trx_hash} has already been claimed for another order ({$dup['payment_id']})."
            ];
        }
    }

    // ==========================================
    // ALL CHECKS PASSED -> Atomic Update Order to PAID
    // ==========================================
    try {
        $pdo->beginTransaction();

        // 1. Update tbl_bakong_transaction
        $stmt_b = $pdo->prepare("UPDATE tbl_bakong_transaction SET
            payment_status = 'PAID',
            trx_hash = ?,
            from_account_id = ?,
            to_account_id = ?,
            updated_at = NOW(),
            raw_response = ?
            WHERE id = ?");
        $stmt_b->execute([
            $trx_hash,
            $from_account_id,
            $to_account_id,
            json_encode($data),
            $trx['id']
        ]);

        // 2. Update tbl_payment
        $stmt_p = $pdo->prepare("UPDATE tbl_payment SET
            payment_status = 'Completed',
            txnid = ?
            WHERE payment_id = ?");
        $stmt_p->execute([
            $trx_hash,
            $trx['payment_id']
        ]);

        // 3. Decrement product stock in tbl_product for ordered items
        $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM tbl_order WHERE payment_id = ?");
        $stmt_items->execute([$trx['payment_id']]);
        $ordered_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ordered_items as $item) {
            $pid = (int)$item['product_id'];
            $qty = (int)$item['quantity'];
            $stmt_stk = $pdo->prepare("UPDATE tbl_product SET p_qty = GREATEST(0, p_qty - ?) WHERE p_id = ?");
            $stmt_stk->execute([$qty, $pid]);
        }

        // 4. Update coupon usage if coupon applied in session
        if (isset($_SESSION['coupon']['id'])) {
            $stmt_cpn = $pdo->prepare("UPDATE tbl_coupon SET usage_count = usage_count + 1 WHERE coupon_id = ?");
            $stmt_cpn->execute([$_SESSION['coupon']['id']]);
            unset($_SESSION['coupon']);
        }

        // 5. Clear cart session
        unset($_SESSION['cart_p_id']);
        unset($_SESSION['cart_size_id']);
        unset($_SESSION['cart_size_name']);
        unset($_SESSION['cart_color_id']);
        unset($_SESSION['cart_color_name']);
        unset($_SESSION['cart_p_qty']);
        unset($_SESSION['cart_p_current_price']);
        unset($_SESSION['cart_p_name']);
        unset($_SESSION['cart_p_featured_photo']);

        $pdo->commit();

        return [
            'success'      => true,
            'status'       => 'PAID',
            'order_number' => $trx['order_number'],
            'payment_id'   => $trx['payment_id'],
            'trx_hash'     => $trx_hash,
            'message'      => 'Payment confirmed and verified successfully!'
        ];

    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'status'  => 'ERROR',
            'message' => 'Error finalizing order: ' . $e->getMessage()
        ];
    }
}

