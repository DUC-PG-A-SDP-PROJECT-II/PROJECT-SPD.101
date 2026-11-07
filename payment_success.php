<?php require_once('header.php'); ?>

<?php
$payment_id = trim($_GET['payment_id'] ?? '');
$order_info = null;
$bakong_trx = null;
if (!empty($payment_id)) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_id = ? LIMIT 1");
    $stmt->execute([$payment_id]);
    $order_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order_info && $order_info['payment_method'] === 'Bakong KHQR') {
        $stmt_b = $pdo->prepare("SELECT * FROM tbl_bakong_transaction WHERE payment_id = ? LIMIT 1");
        $stmt_b->execute([$payment_id]);
        $bakong_trx = $stmt_b->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<div class="page modern-cart-container" style="background:#f8fafc; min-height:75vh; padding:60px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-sm-10 col-xs-12" style="margin:0 auto; float:none;">
                <div class="modern-cart-card" style="text-align:center; padding:50px 30px; border-radius:18px; box-shadow:0 10px 30px rgba(0,0,0,0.05); background:#fff; border:1px solid #e2e8f0;">
                    
                    <div style="width:80px; height:80px; border-radius:50%; background:#ecfdf5; color:#10b981; display:flex; align-items:center; justify-content:center; font-size:36px; margin:0 auto 20px; box-shadow:0 0 0 10px rgba(16, 185, 129, 0.1);">
                        <i class="fa fa-check"></i>
                    </div>

                    <h2 style="font-size:26px; font-weight:800; color:#0f172a; margin:0 0 10px;">
                        <?php echo LANG_VALUE_121; ?>
                    </h2>
                    
                    <p style="font-size:15px; color:#64748b; max-width:480px; margin:0 auto 24px; line-height:1.6;">
                        Your payment has been received and your order is now confirmed. We are preparing your items for delivery!
                    </p>

                    <?php if($order_info): ?>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:18px 24px; max-width:440px; margin:0 auto 28px; text-align:left; font-size:13px; line-height:1.7;">
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:#64748b;">Order Reference:</span>
                            <strong style="color:#0f172a;">#<?php echo htmlspecialchars($order_info['payment_id']); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:#64748b;">Payment Method:</span>
                            <strong style="color:#0f172a;"><?php echo htmlspecialchars($order_info['payment_method']); ?></strong>
                        </div>
                        <?php if(!empty($order_info['txnid'])): ?>
                        <div style="display:flex; justify-content:space-between; gap:10px;">
                            <span style="color:#64748b; flex-shrink:0;">Transaction ID:</span>
                            <code style="color:#007185; font-size:11px; word-break:break-all;"><?php echo htmlspecialchars($order_info['txnid']); ?></code>
                        </div>
                        <?php endif; ?>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="color:#64748b;">Paid Amount:</span>
                            <div style="text-align:right;">
                                <strong style="color:#b12704; font-size:16px;">
                                    <?php 
                                    if ($bakong_trx && $bakong_trx['currency'] === 'KHR') {
                                        echo number_format($bakong_trx['amount'], 0) . ' ៛ KHR';
                                    } else {
                                        echo '$' . number_format((float)$order_info['paid_amount'], 2) . ' USD';
                                    }
                                    ?>
                                </strong>
                                <?php if ($bakong_trx && $bakong_trx['currency'] === 'KHR'): ?>
                                    <div style="font-size:11px; color:#64748b;">(≈ $<?php echo number_format((float)$order_info['paid_amount'], 2); ?> USD)</div>
                                <?php else: ?>
                                    <div style="font-size:11px; color:#64748b;">(≈ <?php echo format_price_khr($order_info['paid_amount']); ?>)</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="display:flex; justify-content:center; gap:14px; flex-wrap:wrap;">
                        <a href="customer-order.php" class="btn btn-primary" style="background:#0f172a; border-color:#0f172a; border-radius:24px; padding:12px 26px; font-weight:700; font-size:14px; text-decoration:none;">
                            <i class="fa fa-list-alt"></i> View Your Orders
                        </a>
                        <a href="index.php" class="btn btn-warning" style="background: linear-gradient(180deg, #f7dfa5 0%, #f0c14b 100%); border: 1px solid #a88734; border-radius:24px; padding:12px 26px; font-weight:800; color:#111; font-size:14px; text-decoration:none;">
                            <i class="fa fa-shopping-bag"></i> Continue Shopping
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
