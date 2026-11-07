<?php
require_once('header.php');
require_once('inc/bakong-config.php');

if (!isset($_SESSION['customer'])) {
    header('location: login.php');
    exit;
}

$payment_id = trim($_GET['payment_id'] ?? '');

if (empty($payment_id)) {
    header('location: checkout.php');
    exit;
}

// Fetch transaction from tbl_bakong_transaction
$stmt = $pdo->prepare("SELECT * FROM tbl_bakong_transaction WHERE payment_id = ? LIMIT 1");
$stmt->execute([$payment_id]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trx) {
    header('location: checkout.php');
    exit;
}

// Security: customer must own this transaction
if ($trx['customer_id'] != $_SESSION['customer']['cust_id']) {
    header('location: customer-order.php');
    exit;
}

// If already paid, redirect straight to payment_success.php
if ($trx['payment_status'] === 'PAID') {
    header('location: payment_success.php?payment_id=' . urlencode($payment_id));
    exit;
}

// REAL-TIME CHECK ON LOAD: If customer completed the payment in their banking app
// and reloads or revisits this page, verify it immediately with Bakong Open API and redirect!
$verify_load = verify_bakong_payment($payment_id, $pdo);
if ($verify_load['status'] === 'PAID') {
    header('location: payment_success.php?payment_id=' . urlencode($payment_id));
    exit;
}

// Calculate remaining expiry seconds
$expires_timestamp = strtotime($trx['expires_at']);
$seconds_left = max(0, $expires_timestamp - time());

// AUTO-RENEWAL: If the KHQR is expired (or has less than 15s left) and unpaid, automatically renew it
// so the customer ALWAYS sees a fresh QR code with a ticking 15-minute countdown clock!
if ($seconds_left <= 15 && $trx['payment_status'] !== 'PAID') {
    try {
        $renewed = generate_order_khqr([
            'payment_id'     => $trx['payment_id'],
            'order_number'   => $trx['order_number'],
            'amount'         => (float)$trx['amount'],
            'currency'       => $trx['currency'],
            'customer_id'    => $trx['customer_id'],
            'customer_email' => $trx['customer_email']
        ], $pdo);
        $trx['khqr_string'] = $renewed['qr_string'];
        $trx['khqr_md5']    = $renewed['md5'];
        $trx['expires_at']  = $renewed['expires_at'];
        $seconds_left       = (int)$renewed['seconds_left'];
    } catch (\Throwable $e) {
        // Fall back to existing transaction
    }
}

$is_expired = ($seconds_left <= 0);
$qr_image_data_uri = render_khqr_image_data_uri($trx['khqr_string']);
$config = get_bakong_config($pdo);
$total_expiry_seconds = ($config['expiry_minutes'] > 0 ? (int)$config['expiry_minutes'] * 60 : 900);
$initial_min = floor($seconds_left / 60);
$initial_sec = $seconds_left % 60;
$initial_timer_str = sprintf('%02d:%02d', $initial_min, $initial_sec);
?>

<style>
@keyframes khqrPulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes khqrGlow {
    0% { box-shadow: 0 0 0 0 rgba(225, 37, 27, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(225, 37, 27, 0); }
    100% { box-shadow: 0 0 0 0 rgba(225, 37, 27, 0); }
}

.khqr-timer-active {
    animation: khqrGlow 2s infinite;
}

.khqr-pulse-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #2563eb;
    display: inline-block;
    animation: khqrPulse 1.2s infinite;
}

.khqr-countdown-banner {
    background: linear-gradient(180deg, #fff5f5 0%, #fff1f2 100%);
    border: 1.5px solid #fecdd3;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 22px;
    text-align: center;
    position: relative;
    box-shadow: 0 4px 15px rgba(225, 37, 27, 0.05);
}

.khqr-countdown-banner.warning {
    background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
    border-color: #fde68a;
}

.khqr-clock-digits {
    font-size: 38px;
    font-weight: 900;
    font-family: 'Consolas', 'Courier New', monospace;
    color: #e1251b;
    letter-spacing: 2px;
    line-height: 1;
    display: inline-block;
    text-shadow: 0 2px 4px rgba(225, 37, 27, 0.12);
}

.khqr-progress-track {
    background: #fee2e2;
    border-radius: 999px;
    height: 8px;
    overflow: hidden;
    margin-top: 10px;
    position: relative;
}

.khqr-progress-bar {
    background: linear-gradient(90deg, #e1251b 0%, #f97316 100%);
    height: 100%;
    width: 100%;
    border-radius: 999px;
    transition: width 1s linear, background 0.5s ease;
}
</style>

<div class="page modern-cart-container" style="background: #f8fafc; min-height: 85vh; padding: 40px 0 80px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-12 col-xs-12" style="margin: 0 auto; float: none;">

                <!-- KHQR Official Styled Payment Card -->
                <div class="khqr-payment-card" style="background:#ffffff; border-radius: 20px; box-shadow: 0 12px 40px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; overflow: hidden; position: relative;">
                    
                    <!-- Official KHQR Red Header -->
                    <div class="khqr-card-header" style="background: linear-gradient(135deg, #e1251b 0%, #b31217 100%); color:#ffffff; padding: 22px 26px; text-align: center; position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <!-- Official KHQR Emblem Badge -->
                            <div style="background: #ffffff; color: #e1251b; font-weight: 900; font-size: 16px; padding: 4px 12px; border-radius: 6px; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                                <i class="fa fa-qrcode" style="font-size: 18px;"></i> KHQR
                            </div>
                            <span style="font-size: 11px; background: rgba(255,255,255,0.22); padding: 5px 12px; border-radius: 20px; font-weight: 700; letter-spacing: 0.5px;">
                                <i class="fa fa-shield"></i> NBC Bakong Official
                            </span>
                        </div>

                        <h3 style="color:#ffffff; margin: 0 0 4px; font-size: 20px; font-weight: 800; letter-spacing: -0.3px;">
                            <?php echo htmlspecialchars($trx['merchant_name']); ?>
                        </h3>
                        <div style="font-size: 13px; color: rgba(255,255,255,0.92); display: flex; justify-content: center; align-items: center; gap: 8px;">
                            <span><i class="fa fa-check-circle"></i> Official Verified Merchant</span>
                            <span>&bull;</span>
                            <span><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($trx['merchant_city']); ?></span>
                        </div>
                    </div>

                    <!-- Payment Body -->
                    <div class="khqr-card-body" style="padding: 26px 24px; text-align: center;">

                        <!-- Order & Amount Box -->
                        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <div style="text-align: left;">
                                <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #64748b; letter-spacing: 0.6px;">Order Number</div>
                                <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 2px;">
                                    <?php echo htmlspecialchars($trx['order_number']); ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #64748b; letter-spacing: 0.6px;">Payment Amount</div>
                                <div style="font-size: 24px; font-weight: 900; color: #e1251b; line-height: 1.2;">
                                    <?php if ($trx['currency'] === 'KHR'): ?>
                                        <?php echo number_format($trx['amount']); ?> ៛
                                    <?php else: ?>
                                        $<?php echo number_format($trx['amount'], 2); ?> USD
                                    <?php endif; ?>
                                </div>
                                <?php if ($trx['currency'] === 'USD' && $trx['amount_khr'] > 0): ?>
                                    <div style="font-size: 12px; color: #64748b; font-weight: 600;">(≈ <?php echo number_format($trx['amount_khr']); ?> ៛)</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Active State: QR Code & Countdown Display -->
                        <div id="qrActiveContainer" style="<?php echo $is_expired ? 'display:none;' : ''; ?>">
                            
                            <!-- ============================================== -->
                            <!-- PROMINENT HIGH-VISIBILITY EXPIRATION COUNTDOWN -->
                            <!-- ============================================== -->
                            <div id="khqrCountdownBanner" class="khqr-countdown-banner">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                    <span style="font-size: 12px; font-weight: 800; color: #9f1239; text-transform: uppercase; letter-spacing: 0.6px; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fa fa-clock-o" style="font-size: 15px; color: #e1251b;"></i> KHQR Expiration Timer
                                    </span>
                                    <span id="countdownStatusBadge" style="font-size: 11px; font-weight: 700; color: #166534; background: #dcfce7; padding: 2px 8px; border-radius: 12px;">
                                        <i class="fa fa-circle" style="font-size: 8px;"></i> Session Active
                                    </span>
                                </div>

                                <div style="margin: 4px 0 6px;">
                                    <span id="countdownTimerBig" class="khqr-clock-digits"><?php echo $initial_timer_str; ?></span>
                                </div>

                                <!-- Animated Progress Bar -->
                                <div class="khqr-progress-track">
                                    <div id="countdownProgressBar" class="khqr-progress-bar"></div>
                                </div>

                                <div id="countdownHelperText" style="font-size: 12px; color: #64748b; margin-top: 8px; font-weight: 600;">
                                    Please scan and confirm payment with your banking app before time runs out.
                                </div>
                            </div>

                            <!-- Framed QR Code with Official KHQR Style -->
                            <div style="display: inline-block; position: relative; margin: 0 auto 16px; padding: 14px; background: #ffffff; border: 3px solid #e1251b; border-radius: 18px; box-shadow: 0 8px 26px rgba(225, 37, 27, 0.15);" class="khqr-timer-active">
                                <img id="khqrImage" src="<?php echo $qr_image_data_uri; ?>" alt="Bakong KHQR" style="width: 250px; height: 250px; display: block; border-radius: 10px; object-fit: contain;">
                                
                                <!-- Center Official KHQR Emblem -->
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #ffffff; border-radius: 8px; padding: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.22);">
                                    <div style="background: #e1251b; color: #ffffff; font-weight: 900; font-size: 12px; padding: 4px 9px; border-radius: 5px; letter-spacing: 0.5px;">
                                        KHQR
                                    </div>
                                </div>
                            </div>

                            <!-- Secondary Timer Pill under QR -->
                            <div style="margin-bottom: 14px;">
                                <div style="display: inline-flex; align-items: center; gap: 8px; background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    <i class="fa fa-hourglass-half" style="font-size: 13px; color: #e1251b;"></i>
                                    <span>Time Left: <strong id="countdownTimer" style="color: #e1251b;"><?php echo $initial_timer_str; ?></strong></span>
                                </div>
                            </div>

                            <!-- Live Auto-Verification Polling Status Pill -->
                            <div id="khqrStatusPill" style="display: inline-flex; align-items: center; gap: 8px; background: #eff6ff; border: 1.5px solid #bfdbfe; color: #1e40af; padding: 8px 20px; border-radius: 25px; font-size: 13px; font-weight: 700; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);">
                                <span class="khqr-pulse-dot"></span>
                                <span id="statusMessage">Awaiting Customer Scan & Transfer...</span>
                            </div>

                            <!-- Clear Instructions -->
                            <div style="background: #fafbfc; border-radius: 12px; padding: 16px 18px; text-align: left; margin-bottom: 20px; border: 1px dashed #cbd5e1;">
                                <div style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa fa-mobile" style="font-size: 18px; color: #007185;"></i> How to Pay with KHQR:
                                </div>
                                <ol style="margin: 0; padding-left: 20px; font-size: 12px; color: #475569; line-height: 1.65;">
                                    <li>Open your banking app (<strong>ABA Mobile, ACLEDA, Bakong, Wing, Canadia</strong>, etc.).</li>
                                    <li>Tap <strong>Scan QR / QR Pay</strong> and point your camera at the QR code above.</li>
                                    <li>Confirm the transfer to <strong><?php echo htmlspecialchars($trx['merchant_name']); ?></strong>.</li>
                                    <li>This page will <strong>automatically verify and confirm</strong> your payment instantly.</li>
                                </ol>
                            </div>

                            <!-- Supported Bank Badges -->
                            <div style="margin-bottom: 22px;">
                                <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8; letter-spacing: 0.5px; margin-bottom: 10px;">
                                    Supported by All Cambodian Banking Apps:
                                </div>
                                <div style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 8px;">
                                    <span style="font-size: 11px; font-weight: 700; color: #004b87; background: #f0f7fc; border: 1px solid #d0e4f5; padding: 4px 10px; border-radius: 6px;">ABA Bank</span>
                                    <span style="font-size: 11px; font-weight: 700; color: #002d62; background: #f0f4fa; border: 1px solid #ccd8ea; padding: 4px 10px; border-radius: 6px;">ACLEDA</span>
                                    <span style="font-size: 11px; font-weight: 700; color: #e1251b; background: #fff1f2; border: 1px solid #fed7da; padding: 4px 10px; border-radius: 6px;">Bakong App</span>
                                    <span style="font-size: 11px; font-weight: 700; color: #62a304; background: #f6fbf0; border: 1px solid #d9ecc4; padding: 4px 10px; border-radius: 6px;">Wing Bank</span>
                                    <span style="font-size: 11px; font-weight: 700; color: #d62228; background: #fff3f3; border: 1px solid #fedddd; padding: 4px 10px; border-radius: 6px;">Canadia</span>
                                    <span style="font-size: 11px; font-weight: 700; color: #005a9c; background: #f0f6fc; border: 1px solid #d4e5f7; padding: 4px 10px; border-radius: 6px;">Sathapana</span>
                                    <span style="font-size: 11px; font-weight: 700; color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 4px 8px; border-radius: 6px;">+ Any Bank</span>
                                </div>
                            </div>

                            <!-- Manual Check Status Button & Deep Link -->
                            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                                <button type="button" id="btnManualCheck" class="btn btn-primary" style="background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%); border: none; border-radius: 25px; padding: 12px 26px; font-weight: 800; font-size: 14px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3); display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <i id="btnCheckIcon" class="fa fa-refresh"></i> Check Payment Status
                                </button>

                                <?php if (!empty($trx['khqr_string'])): ?>
                                    <a href="bakong://qr/<?php echo urlencode($trx['khqr_string']); ?>" class="btn btn-default visible-xs-inline-block visible-sm-inline-block" style="border-radius: 25px; padding: 12px 20px; font-weight: 700; font-size: 13px; border: 1.5px solid #cbd5e1; color: #0f172a;">
                                        <i class="fa fa-external-link"></i> Open Bakong App
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Expired State Box -->
                        <div id="qrExpiredContainer" style="<?php echo $is_expired ? '' : 'display:none;'; ?> padding: 35px 20px;">
                            <div style="width: 76px; height: 76px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px; box-shadow: 0 0 0 8px rgba(220, 38, 38, 0.1);">
                                <i class="fa fa-clock-o"></i>
                            </div>
                            <h4 style="font-weight: 800; color: #0f172a; margin: 0 0 8px; font-size: 20px;">QR Code Expired</h4>
                            <p style="font-size: 13px; color: #64748b; max-width: 380px; margin: 0 auto 20px; line-height: 1.6;">
                                This dynamic KHQR has reached its time limit. Don't worry, no money was charged. Click below to generate a fresh QR code with a reset timer.
                            </p>
                            <button type="button" id="btnRetryKhqr" class="btn btn-warning" style="background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%); border: none; border-radius: 25px; padding: 13px 30px; font-weight: 800; font-size: 14px; color: #fff; box-shadow: 0 4px 15px rgba(217, 119, 6, 0.35); display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                                <i class="fa fa-refresh"></i> Generate New QR Code
                            </button>
                        </div>

                        <!-- Success State Box -->
                        <div id="qrSuccessContainer" style="display:none; padding: 35px 20px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 38px; margin: 0 auto 16px; box-shadow: 0 0 0 10px rgba(34, 197, 94, 0.1);">
                                <i class="fa fa-check"></i>
                            </div>
                            <h3 style="font-weight: 900; color: #15803d; margin: 0 0 8px; font-size: 22px;">Payment Confirmed!</h3>
                            <p style="font-size: 14px; color: #166534; font-weight: 600; margin: 0 0 16px;">
                                Thank you! Your transfer was successfully verified through Bakong.
                            </p>
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; margin-bottom: 20px; font-size: 12px; color: #14532d; word-break: break-all;">
                                <strong>Transaction Hash:</strong><br>
                                <code id="confirmedTrxHash" style="color: #15803d; font-size: 11px;">--</code>
                            </div>
                            <div style="font-size: 13px; color: #64748b;">
                                <i class="fa fa-spinner fa-spin"></i> Redirecting to order confirmation page...
                            </div>
                        </div>

                    </div>

                    <!-- Card Footer Back Link -->
                    <div style="background: #f8fafc; border-top: 1px solid #f1f5f9; padding: 14px 20px; text-align: center;">
                        <a href="checkout.php" style="color: #64748b; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa fa-arrow-left"></i> Return to Checkout Options
                        </a>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var paymentId = <?php echo json_encode($payment_id); ?>;
    var totalDuration = <?php echo (int)$total_expiry_seconds; ?>;
    var initialSeconds = <?php echo max(0, (int)$seconds_left); ?>;
    var isExpiredInitial = <?php echo $is_expired ? 'true' : 'false'; ?>;

    var endTime = Date.now() + (initialSeconds * 1000);
    var timerInterval = null;
    var pollInterval = null;
    var isChecking = false;
    var isPaid = false;

    function formatTime(secs) {
        var m = Math.floor(secs / 60);
        var s = secs % 60;
        return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    function updateCountdown() {
        var now = Date.now();
        var remaining = Math.max(0, Math.round((endTime - now) / 1000));
        var timeStr = formatTime(remaining);

        var elBig = document.getElementById('countdownTimerBig');
        var elSmall = document.getElementById('countdownTimer');
        var elBar = document.getElementById('countdownProgressBar');
        var elBanner = document.getElementById('khqrCountdownBanner');
        var elBadge = document.getElementById('countdownStatusBadge');
        var elHelper = document.getElementById('countdownHelperText');

        if (elBig) elBig.textContent = timeStr;
        if (elSmall) elSmall.textContent = timeStr;

        var pct = Math.max(0, Math.min(100, (remaining / totalDuration) * 100));
        if (elBar) elBar.style.width = pct + '%';

        if (remaining <= 180 && remaining > 0) {
            if (elBanner) elBanner.classList.add('warning');
            if (elBadge) {
                elBadge.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Expiring Soon';
                elBadge.style.background = '#fef3c7';
                elBadge.style.color = '#b45309';
            }
            if (elBig) elBig.style.color = '#b45309';
            if (elHelper) elHelper.textContent = 'Hurry! Less than 3 minutes remaining to complete your payment.';
        } else if (remaining > 180) {
            if (elBanner) elBanner.classList.remove('warning');
            if (elBadge) {
                elBadge.innerHTML = '<i class="fa fa-circle" style="font-size: 8px;"></i> Session Active';
                elBadge.style.background = '#dcfce7';
                elBadge.style.color = '#166534';
            }
            if (elBig) elBig.style.color = '#e1251b';
            if (elHelper) elHelper.textContent = 'Please scan and confirm payment with your banking app before time runs out.';
        }

        if (remaining <= 0) {
            clearInterval(timerInterval);
            clearInterval(pollInterval);
            if (elBig) elBig.textContent = '00:00';
            if (elSmall) elSmall.textContent = '00:00';
            if (elBar) elBar.style.width = '0%';

            var activeCont = document.getElementById('qrActiveContainer');
            var expiredCont = document.getElementById('qrExpiredContainer');
            if (activeCont) activeCont.style.display = 'none';
            if (expiredCont) expiredCont.style.display = 'block';
        }
    }

    // Auto-check payment status via native fetch (runs every 2.5s)
    function checkPaymentStatus(isManual) {
        if (isChecking || isPaid) return;
        isChecking = true;

        var icon = document.getElementById('btnCheckIcon');
        var btnCheck = document.getElementById('btnManualCheck');
        var msg = document.getElementById('statusMessage');

        if (isManual) {
            if (icon) icon.classList.add('fa-spin');
            if (btnCheck) btnCheck.disabled = true;
            if (msg) msg.textContent = 'Verifying with Bakong Network...';
        }

        var formData = new FormData();
        formData.append('payment_id', paymentId);
        formData.append('action', 'check');

        fetch('api/check-payment.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res.status === 'PAID') {
                isPaid = true;
                clearInterval(pollInterval);
                clearInterval(timerInterval);

                var activeCont = document.getElementById('qrActiveContainer');
                var expiredCont = document.getElementById('qrExpiredContainer');
                var successCont = document.getElementById('qrSuccessContainer');
                var trxHashEl = document.getElementById('confirmedTrxHash');

                if (activeCont) activeCont.style.display = 'none';
                if (expiredCont) expiredCont.style.display = 'none';
                if (trxHashEl) trxHashEl.textContent = res.trx_hash || 'Confirmed';
                if (successCont) successCont.style.display = 'block';

                setTimeout(function() {
                    window.location.href = res.redirect_url || ('payment_success.php?payment_id=' + encodeURIComponent(paymentId));
                }, 1200);
            } else if (res.status === 'EXPIRED') {
                var now = Date.now();
                if ((endTime - now) <= 3000) {
                    clearInterval(pollInterval);
                    clearInterval(timerInterval);
                    var activeCont = document.getElementById('qrActiveContainer');
                    var expiredCont = document.getElementById('qrExpiredContainer');
                    if (activeCont) activeCont.style.display = 'none';
                    if (expiredCont) expiredCont.style.display = 'block';
                }
            } else {
                if (res.message && msg && !isManual) {
                    msg.textContent = res.message;
                }
            }
        })
        .catch(function(err) {
            console.warn('Bakong verification check:', err);
        })
        .finally(function() {
            isChecking = false;
            if (isManual) {
                if (icon) icon.classList.remove('fa-spin');
                if (btnCheck) btnCheck.disabled = false;
            }
        });
    }

    // Manual check button event
    var btnCheck = document.getElementById('btnManualCheck');
    if (btnCheck) {
        btnCheck.addEventListener('click', function() {
            checkPaymentStatus(true);
        });
    }

    // Retry / Renew button event
    var btnRetry = document.getElementById('btnRetryKhqr');
    if (btnRetry) {
        btnRetry.addEventListener('click', function() {
            btnRetry.disabled = true;
            btnRetry.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating New QR...';

            var formData = new FormData();
            formData.append('payment_id', paymentId);
            formData.append('action', 'retry');

            fetch('api/check-payment.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (res.success && res.qr_image_data_uri) {
                    var img = document.getElementById('khqrImage');
                    if (img) img.src = res.qr_image_data_uri;

                    var newSecs = parseInt(res.seconds_left) || totalDuration;
                    endTime = Date.now() + (newSecs * 1000);

                    var msg = document.getElementById('statusMessage');
                    if (msg) msg.textContent = 'Awaiting Customer Scan & Transfer...';

                    var activeCont = document.getElementById('qrActiveContainer');
                    var expiredCont = document.getElementById('qrExpiredContainer');
                    if (expiredCont) expiredCont.style.display = 'none';
                    if (activeCont) activeCont.style.display = 'block';

                    clearInterval(timerInterval);
                    clearInterval(pollInterval);
                    updateCountdown();
                    timerInterval = setInterval(updateCountdown, 1000);
                    pollInterval = setInterval(function() { checkPaymentStatus(false); }, 2500);
                } else {
                    alert(res.message || 'Could not regenerate QR code. Please refresh the page.');
                }
            })
            .catch(function() {
                alert('Connection error. Please refresh the page.');
            })
            .finally(function() {
                btnRetry.disabled = false;
                btnRetry.innerHTML = '<i class="fa fa-refresh"></i> Generate New QR Code';
            });
        });
    }

    // Start timer and polling immediately
    if (!isExpiredInitial) {
        updateCountdown();
        timerInterval = setInterval(updateCountdown, 1000);
        pollInterval = setInterval(function() {
            checkPaymentStatus(false);
        }, 2500);
    }
})();
</script>

<?php require_once('footer.php'); ?>
