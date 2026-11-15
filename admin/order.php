<?php require_once('header.php'); ?>

<?php
$error_message = '';
$success_message = '';

if(isset($_POST['form1'])) {
    $valid = 1;
    if(empty($_POST['subject_text'])) {
        $valid = 0;
        $error_message .= 'Subject cannot be empty\n';
    }
    if(empty($_POST['message_text'])) {
        $valid = 0;
        $error_message .= 'Message cannot be empty\n';
    }
    if($valid == 1) {

        $subject_text = strip_tags($_POST['subject_text']);
        $message_text = strip_tags($_POST['message_text']);

        // Getting Customer Email Address
        $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=?");
        $statement->execute(array($_POST['cust_id']));
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $cust_email = $row['cust_email'];
        }

        // Getting Admin Email Address
        $statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $admin_email = $row['contact_email'];
        }

        $order_detail = '';
        $statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_id=?");
        $statement->execute(array($_POST['payment_id']));
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
        	
        	if($row['payment_method'] == 'PayPal'):
        		$payment_details = 'Transaction Id: '.$row['txnid'].'<br>';
        	elseif($row['payment_method'] == 'Stripe'):
				$payment_details = 'Transaction Id: '.$row['txnid'].'<br>Card number: '.$row['card_number'].'<br>Card CVV: '.$row['card_cvv'].'<br>Card Month: '.$row['card_month'].'<br>Card Year: '.$row['card_year'].'<br>';
        	elseif($row['payment_method'] == 'Bank Deposit'):
				$payment_details = 'Transaction Details: <br>'.$row['bank_transaction_info'];
        	elseif($row['payment_method'] == 'Bakong KHQR'):
				$payment_details = 'Method: Bakong Dynamic KHQR<br>Transaction Hash: '.$row['txnid'].'<br>Reference: '.$row['bank_transaction_info'];
        	else:
                $payment_details = 'Method: '.$row['payment_method'].'<br>Txn ID: '.$row['txnid'];
        	endif;

            $order_detail .= '
Customer Name: '.$row['customer_name'].'<br>
Customer Email: '.$row['customer_email'].'<br>
Payment Method: '.$row['payment_method'].'<br>
Payment Date: '.$row['payment_date'].'<br>
Payment Details: <br>'.$payment_details.'<br>
Paid Amount: '.$row['paid_amount'].'<br>
Payment Status: '.$row['payment_status'].'<br>
Shipping Status: '.$row['shipping_status'].'<br>
Payment Id: '.$row['payment_id'].'<br>
            ';
        }

        $i=0;
        $statement = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
        $statement->execute(array($_POST['payment_id']));
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $i++;
            $order_detail .= '
<br><b><u>Product Item '.$i.'</u></b><br>
Product Name: '.$row['product_name'].'<br>
Size: '.$row['size'].'<br>
Color: '.$row['color'].'<br>
Quantity: '.$row['quantity'].'<br>
Unit Price: '.$row['unit_price'].'<br>
            ';
        }

        $statement = $pdo->prepare("INSERT INTO tbl_customer_message (subject,message,order_detail,cust_id) VALUES (?,?,?,?)");
        $statement->execute(array($subject_text,$message_text,$order_detail,$_POST['cust_id']));

        // sending email
        $to_customer = $cust_email;
        $message = '
<html><body>
<h3>Message: </h3>
'.$message_text.'
<h3>Order Details: </h3>
'.$order_detail.'
</body></html>
';
        $headers = 'From: ' . $admin_email . "\r\n" .
                   'Reply-To: ' . $admin_email . "\r\n" .
                   'X-Mailer: PHP/' . phpversion() . "\r\n" . 
                   "MIME-Version: 1.0\r\n" . 
                   "Content-Type: text/html; charset=ISO-8859-1\r\n";

        // Sending email to customer                  
        @mail($to_customer, $subject_text, $message, $headers);
        
        $success_message = 'Your email to customer is sent successfully.';

    }
}
?>

<?php
// Auto-verify recent pending Bakong KHQR orders on admin page load so admin sees real-time status automatically
require_once('../inc/bakong-config.php');
$stmt_pending_bakong = $pdo->prepare("SELECT payment_id FROM tbl_payment WHERE payment_method = 'Bakong KHQR' AND payment_status = 'Pending' AND payment_date >= DATE_SUB(NOW(), INTERVAL 48 HOUR)");
$stmt_pending_bakong->execute();
$pending_bakong_orders = $stmt_pending_bakong->fetchAll(PDO::FETCH_ASSOC);

foreach ($pending_bakong_orders as $p_order) {
    try {
        verify_bakong_payment($p_order['payment_id'], $pdo);
    } catch (\Throwable $e) {
        // Continue silently if API check times out
    }
}

// Compute summary counts
$total_orders = (int)$pdo->query("SELECT COUNT(*) FROM tbl_payment")->fetchColumn();
$pending_orders = (int)$pdo->query("SELECT COUNT(*) FROM tbl_payment WHERE payment_status = 'Pending'")->fetchColumn();
$completed_orders = (int)$pdo->query("SELECT COUNT(*) FROM tbl_payment WHERE payment_status = 'Completed'")->fetchColumn();
$bakong_orders = (int)$pdo->query("SELECT COUNT(*) FROM tbl_payment WHERE payment_method = 'Bakong KHQR'")->fetchColumn();

// Filter handling
$filter = trim($_GET['filter'] ?? 'all');
$where_clause = '';
if ($filter === 'pending') {
    $where_clause = "WHERE payment_status = 'Pending'";
} elseif ($filter === 'completed') {
    $where_clause = "WHERE payment_status = 'Completed'";
} elseif ($filter === 'bakong') {
    $where_clause = "WHERE payment_method = 'Bakong KHQR'";
}
?>

<section class="content-header">
	<div class="content-header-left">
		<h1>Manage & View Orders</h1>
	</div>
</section>

<section class="content">

  <!-- Action Status Alerts -->
  <?php if(isset($_GET['msg'])): ?>
      <?php if($_GET['msg'] === 'bakong_paid'): ?>
          <div class="alert alert-success alert-dismissible" style="border-radius:6px;">
              <button type="button" class="close" data-dismiss="alert">&times;</button>
              <h4><i class="icon fa fa-check"></i> Bakong Payment Verified!</h4>
              Order <strong>#<?php echo htmlspecialchars($_GET['id'] ?? ''); ?></strong> has been successfully verified via NBC Bakong Open API and marked as <strong>PAID</strong>. Inventory stock has been updated.
              <?php if(!empty($_GET['hash'])): ?>
                  <br>Transaction Hash: <code><?php echo htmlspecialchars($_GET['hash']); ?></code>
              <?php endif; ?>
          </div>
      <?php elseif($_GET['msg'] === 'bakong_pending'): ?>
          <div class="alert alert-warning alert-dismissible" style="border-radius:6px;">
              <button type="button" class="close" data-dismiss="alert">&times;</button>
              <h4><i class="icon fa fa-clock-o"></i> Bakong Status: Pending</h4>
              Payment for order <strong>#<?php echo htmlspecialchars($_GET['id'] ?? ''); ?></strong> has not been completed on the NBC Bakong network yet. 
              (<?php echo htmlspecialchars($_GET['detail'] ?? 'Awaiting customer scan & transfer'); ?>).
              <br>If the customer paid manually via bank receipt, you can click <strong>Manual Confirm</strong> below.
          </div>
      <?php elseif($_GET['msg'] === 'bakong_failed'): ?>
          <div class="alert alert-danger alert-dismissible" style="border-radius:6px;">
              <button type="button" class="close" data-dismiss="alert">&times;</button>
              <h4><i class="icon fa fa-ban"></i> Verification Alert</h4>
              <?php echo htmlspecialchars($_GET['detail'] ?? 'Verification did not pass security checks.'); ?>
          </div>
      <?php elseif($_GET['msg'] === 'manual_confirmed'): ?>
          <div class="alert alert-success alert-dismissible" style="border-radius:6px;">
              <button type="button" class="close" data-dismiss="alert">&times;</button>
              <h4><i class="icon fa fa-check-circle"></i> Manual Payment Approved</h4>
              Order <strong>#<?php echo htmlspecialchars($_GET['id'] ?? ''); ?></strong> has been manually reviewed and marked as <strong>PAID</strong>. Inventory stock has been safely decremented. Reference: <code><?php echo htmlspecialchars($_GET['ref'] ?? ''); ?></code>
          </div>
      <?php endif; ?>
  <?php endif; ?>

  <?php if($error_message != ''): ?>
      <div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?php echo nl2br(htmlspecialchars($error_message)); ?></div>
  <?php endif; ?>
  <?php if($success_message != ''): ?>
      <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?php echo nl2br(htmlspecialchars($success_message)); ?></div>
  <?php endif; ?>

  <!-- Summary Metric Cards -->
  <div class="row">
      <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box bg-aqua" style="border-radius:8px;">
              <span class="info-box-icon"><i class="fa fa-shopping-cart"></i></span>
              <div class="info-box-content">
                  <span class="info-box-text">Total Orders</span>
                  <span class="info-box-number" style="font-size:24px; font-weight:800;"><?php echo $total_orders; ?></span>
                  <div class="progress"><div class="progress-bar" style="width: 100%"></div></div>
                  <span class="progress-description">All recorded orders</span>
              </div>
          </div>
      </div>

      <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box bg-yellow" style="border-radius:8px;">
              <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
              <div class="info-box-content">
                  <span class="info-box-text">Pending Payments</span>
                  <span class="info-box-number" style="font-size:24px; font-weight:800;"><?php echo $pending_orders; ?></span>
                  <div class="progress"><div class="progress-bar" style="width: <?php echo $total_orders > 0 ? round(($pending_orders/$total_orders)*100) : 0; ?>%"></div></div>
                  <span class="progress-description">Requires scan or manual review</span>
              </div>
          </div>
      </div>

      <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box bg-green" style="border-radius:8px;">
              <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
              <div class="info-box-content">
                  <span class="info-box-text">Paid / Completed</span>
                  <span class="info-box-number" style="font-size:24px; font-weight:800;"><?php echo $completed_orders; ?></span>
                  <div class="progress"><div class="progress-bar" style="width: <?php echo $total_orders > 0 ? round(($completed_orders/$total_orders)*100) : 0; ?>%"></div></div>
                  <span class="progress-description">Verified and confirmed</span>
              </div>
          </div>
      </div>

      <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box bg-red" style="border-radius:8px;">
              <span class="info-box-icon"><i class="fa fa-qrcode"></i></span>
              <div class="info-box-content">
                  <span class="info-box-text">Bakong KHQR Orders</span>
                  <span class="info-box-number" style="font-size:24px; font-weight:800;"><?php echo $bakong_orders; ?></span>
                  <div class="progress"><div class="progress-bar" style="width: <?php echo $total_orders > 0 ? round(($bakong_orders/$total_orders)*100) : 0; ?>%"></div></div>
                  <span class="progress-description">Dynamic KHQR transfers</span>
              </div>
          </div>
      </div>
  </div>

  <div class="row">
    <div class="col-md-12">

      <div class="box box-info" style="border-radius:8px; overflow:hidden;">
        
        <!-- Filter Tabs -->
        <div class="box-header with-border" style="background:#f8fafc; padding:12px 18px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div class="btn-group">
                    <a href="order.php?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-default'; ?>" style="font-weight:700;">
                        All Orders (<?php echo $total_orders; ?>)
                    </a>
                    <a href="order.php?filter=pending" class="btn btn-sm <?php echo $filter === 'pending' ? 'btn-warning' : 'btn-default'; ?>" style="font-weight:700;">
                        <i class="fa fa-clock-o"></i> Pending Payments (<?php echo $pending_orders; ?>)
                    </a>
                    <a href="order.php?filter=completed" class="btn btn-sm <?php echo $filter === 'completed' ? 'btn-success' : 'btn-default'; ?>" style="font-weight:700;">
                        <i class="fa fa-check"></i> Paid / Completed (<?php echo $completed_orders; ?>)
                    </a>
                    <a href="order.php?filter=bakong" class="btn btn-sm <?php echo $filter === 'bakong' ? 'btn-danger' : 'btn-default'; ?>" style="font-weight:700;">
                        <i class="fa fa-qrcode"></i> Bakong KHQR (<?php echo $bakong_orders; ?>)
                    </a>
                </div>

                <div>
                    <a href="settings.php#tab_payment" class="btn btn-default btn-sm" style="border-radius:4px; font-weight:600;">
                        <i class="fa fa-gear"></i> Payment Gateway Settings
                    </a>
                </div>
            </div>
        </div>

        <div class="box-body table-responsive">
          <table id="example1" class="table table-bordered table-hover table-striped">
			<thead>
			    <tr style="background:#f1f5f9;">
			        <th style="width:30px;">#</th>
                    <th style="width:160px;">Customer</th>
			        <th>Product Details</th>
                    <th style="width:230px;">Payment Information</th>
                    <th style="width:110px;">Paid Amount</th>
                    <th style="width:140px; text-align:center;">Payment Status</th>
                    <th style="width:110px; text-align:center;">Shipping Status</th>
			        <th style="width:50px; text-align:center;">Action</th>
			    </tr>
			</thead>
            <tbody>
            	<?php
            	$i=0;
            	$statement = $pdo->prepare("SELECT * FROM tbl_payment $where_clause ORDER by id DESC");
            	$statement->execute();
            	$result = $statement->fetchAll(PDO::FETCH_ASSOC);							
            	foreach ($result as $row) {
            		$i++;

                    // Query Bakong transaction record if payment method is Bakong KHQR
                    $bakong_trx = null;
                    if ($row['payment_method'] === 'Bakong KHQR') {
                        $stmt_b = $pdo->prepare("SELECT * FROM tbl_bakong_transaction WHERE payment_id = ? LIMIT 1");
                        $stmt_b->execute([$row['payment_id']]);
                        $bakong_trx = $stmt_b->fetch(PDO::FETCH_ASSOC);
                    }
            		?>
					<tr>
	                    <td><?php echo $i; ?></td>
	                    <td>
                            <b>Id:</b> <?php echo $row['customer_id']; ?><br>
                            <b>Name:</b> <?php echo htmlspecialchars($row['customer_name']); ?><br>
                            <b>Email:</b> <span style="font-size:11px; word-break:break-all;"><?php echo htmlspecialchars($row['customer_email']); ?></span><br><br>
                            <a href="#" data-toggle="modal" data-target="#model-<?php echo $i; ?>" class="btn btn-default btn-xs" style="width:100%; margin-bottom:4px; border:1px solid #cbd5e1; font-weight:600;"><i class="fa fa-envelope-o"></i> Send Message</a>
                            
                            <!-- Send Message Modal -->
                            <div id="model-<?php echo $i; ?>" class="modal fade" role="dialog">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal">&times;</button>
											<h4 class="modal-title" style="font-weight: bold;">Send Message to Customer</h4>
										</div>
										<div class="modal-body" style="font-size: 14px">
											<form action="" method="post">
                                                <input type="hidden" name="cust_id" value="<?php echo $row['customer_id']; ?>">
                                                <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
												<table class="table table-bordered">
													<tr>
														<td style="width:25%;">Subject</td>
														<td>
                                                            <input type="text" name="subject_text" class="form-control" style="width: 100%;">
														</td>
													</tr>
                                                    <tr>
                                                        <td>Message</td>
                                                        <td>
                                                            <textarea name="message_text" class="form-control" cols="30" rows="10" style="width:100%;height: 180px;"></textarea>
                                                        </td>
                                                    </tr>
													<tr>
														<td></td>
														<td><input type="submit" value="Send Message" name="form1" class="btn btn-primary"></td>
													</tr>
												</table>
											</form>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
										</div>
									</div>
								</div>
							</div>
                        </td>
                        <td>
                           <?php
                           $statement1 = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
                           $statement1->execute(array($row['payment_id']));
                           $result1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
                           foreach ($result1 as $row1) {
                                echo '<div style="margin-bottom:6px; padding-bottom:6px; border-bottom:1px dashed #e2e8f0;">';
                                echo '<b>' . htmlspecialchars($row1['product_name']) . '</b>';
                                echo '<br><span style="font-size:11px; color:#64748b;">Size: ' . htmlspecialchars($row1['size']) . ' | Color: ' . htmlspecialchars($row1['color']) . '</span>';
                                echo '<br><span style="font-size:11px; font-weight:600;">Qty: ' . $row1['quantity'] . ' &times; ' . format_price($row1['unit_price']) . '</span>';
                                echo '</div>';
                           }
                           ?>
                        </td>
                        <td>
                        	<div style="font-size:12px; line-height:1.6;">
                                <?php if($row['payment_method'] === 'Bakong KHQR'): ?>
                                    <span class="label label-danger" style="background:#e1251b !important; font-weight:700; padding:3px 8px; border-radius:4px;">
                                        <i class="fa fa-qrcode"></i> Bakong KHQR
                                    </span>
                                <?php elseif($row['payment_method'] === 'PayPal'): ?>
                                    <span class="label label-primary" style="background:#003087 !important; padding:3px 8px; border-radius:4px;">PayPal</span>
                                <?php elseif($row['payment_method'] === 'Bank Deposit'): ?>
                                    <span class="label label-info" style="background:#0284c7 !important; padding:3px 8px; border-radius:4px;">Bank Deposit</span>
                                <?php else: ?>
                                    <span class="label label-default" style="padding:3px 8px; border-radius:4px;"><?php echo htmlspecialchars($row['payment_method']); ?></span>
                                <?php endif; ?>

                                <div style="margin-top:6px;">
                                    <strong>Order ID:</strong> #<?php echo htmlspecialchars($row['payment_id']); ?><br>
                                    <span style="color:#64748b; font-size:11px;"><i class="fa fa-calendar"></i> <?php echo date('M d, Y h:i A', strtotime($row['payment_date'])); ?></span>
                                </div>

                                <div style="margin-top:4px;">
                                    <strong>Txn Ref:</strong> 
                                    <?php if(!empty($row['txnid'])): ?>
                                        <code style="font-size:11px; word-break:break-all; color:#15803d;"><?php echo htmlspecialchars($row['txnid']); ?></code>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:11px; font-style:italic;">Awaiting Confirmation</span>
                                    <?php endif; ?>
                                </div>

                        		<?php if($row['payment_method'] == 'Bakong KHQR' && $bakong_trx): ?>
                        			<div style="background:#fff1f2; padding:6px 8px; border-radius:6px; font-size:11px; margin-top:6px; border:1px solid #fecdd3; color:#9f1239;">
                                        <div><strong>Currency:</strong> <?php echo htmlspecialchars($bakong_trx['currency']); ?> (<?php echo $bakong_trx['currency'] === 'KHR' ? number_format($bakong_trx['amount']) . ' ៛' : '$' . number_format($bakong_trx['amount'], 2); ?>)</div>
                                        <div><strong>KHQR MD5:</strong> <code style="font-size:10px;"><?php echo substr($bakong_trx['khqr_md5'], 0, 16); ?>...</code></div>
                                        <div><strong>Status:</strong> 
                                            <?php if($bakong_trx['payment_status'] === 'PAID'): ?>
                                                <span class="label label-success" style="font-size:10px;">PAID</span>
                                            <?php elseif($bakong_trx['payment_status'] === 'EXPIRED'): ?>
                                                <span class="label label-default" style="font-size:10px;">EXPIRED</span>
                                            <?php else: ?>
                                                <span class="label label-warning" style="font-size:10px;">PENDING</span>
                                            <?php endif; ?>
                                        </div>
                        			</div>
                        		<?php elseif($row['payment_method'] == 'Bank Deposit'): ?>
                        			<div style="background:#f8fafc; padding:6px 8px; border-radius:6px; font-size:11px; margin-top:6px; border:1px solid #e2e8f0;"><?php echo nl2br(htmlspecialchars($row['bank_transaction_info'])); ?></div>
                        		<?php endif; ?>
                        	</div>
                        </td>
                        <td>
                            <strong style="font-size:15px; color:#0f172a;"><?php echo format_price($row['paid_amount']); ?></strong>
                            <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                <?php if(get_current_currency() === 'KHR'): ?>
                                    ≈ $<?php echo number_format($row['paid_amount'], 2); ?> USD
                                <?php else: ?>
                                    ≈ <?php echo format_price_khr($row['paid_amount']); ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <!-- Payment Status & Verification Action -->
                        <td style="text-align:center;">
                            <?php if($row['payment_status'] === 'Completed'): ?>
                                <span class="label label-success" style="display:inline-block; padding:5px 10px; font-size:12px; border-radius:4px;">
                                    <i class="fa fa-check-circle"></i> Paid
                                </span>
                                <div style="margin-top:6px;">
                                    <a href="order-change-status.php?id=<?php echo $row['id']; ?>&task=Pending" class="btn btn-default btn-xs" onclick="return confirm('Change status back to Pending and restore stock?');" style="border:1px solid #cbd5e1; font-size:11px; color:#64748b;">
                                        <i class="fa fa-undo"></i> Set Pending
                                    </a>
                                </div>
                            <?php else: ?>
                                <span class="label label-warning" style="display:inline-block; padding:5px 10px; font-size:12px; border-radius:4px;">
                                    <i class="fa fa-clock-o"></i> Pending
                                </span>

                                <?php if($row['payment_method'] === 'Bakong KHQR'): ?>
                                    <!-- 1. Verify via Bakong API -->
                                    <div style="margin-top:8px;">
                                        <a href="order-verify-bakong.php?payment_id=<?php echo urlencode($row['payment_id']); ?>" class="btn btn-primary btn-xs" style="width:100%; font-weight:700; border-radius:4px; padding:4px 6px;" title="Check real-time status with Bakong Open API">
                                            <i class="fa fa-refresh"></i> Check Bakong API
                                        </a>
                                    </div>
                                    <!-- 2. Manual Review & Confirm Modal -->
                                    <div style="margin-top:4px;">
                                        <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#modal-manual-<?php echo $row['id']; ?>" style="width:100%; font-weight:700; border-radius:4px; padding:4px 6px;">
                                            <i class="fa fa-check"></i> Manual Review
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top:8px;">
                                        <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#modal-manual-<?php echo $row['id']; ?>" style="width:100%; font-weight:700; border-radius:4px; padding:4px 6px;">
                                            <i class="fa fa-check"></i> Mark Paid
                                        </button>
                                    </div>
                                <?php endif; ?>

                            <?php endif; ?>

                            <!-- Modal: Manual Review & Confirm Payment -->
                            <div id="modal-manual-<?php echo $row['id']; ?>" class="modal fade" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content" style="text-align:left;">
                                        <form action="order-manual-confirm.php" method="post">
                                            <input type="hidden" name="payment_id" value="<?php echo htmlspecialchars($row['payment_id']); ?>">
                                            <div class="modal-header" style="background:#0f172a; color:#fff;">
                                                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                                                <h4 class="modal-title" style="font-weight:bold;"><i class="fa fa-check-square-o"></i> Manual Payment Review & Confirmation</h4>
                                            </div>
                                            <div class="modal-body" style="font-size:13px;">
                                                <div class="alert alert-info" style="font-size:12px; margin-bottom:14px;">
                                                    <i class="fa fa-info-circle"></i> Use this manual review if automatic API verification is unavailable or if the customer sent a direct bank transfer receipt. Confirming will set payment to <strong>PAID</strong> and automatically update product stock.
                                                </div>

                                                <table class="table table-bordered table-striped" style="margin-bottom:15px; font-size:12px;">
                                                    <tr><th style="width:35%;">Order ID</th><td>#<?php echo htmlspecialchars($row['payment_id']); ?></td></tr>
                                                    <tr><th>Customer</th><td><?php echo htmlspecialchars($row['customer_name']); ?> (<?php echo htmlspecialchars($row['customer_email']); ?>)</td></tr>
                                                    <tr><th>Payment Method</th><td><span class="label label-default"><?php echo htmlspecialchars($row['payment_method']); ?></span></td></tr>
                                                    <tr><th>Total Amount</th><td><strong style="color:#b12704; font-size:15px;">$<?php echo number_format($row['paid_amount'], 2); ?> USD</strong></td></tr>
                                                    <tr><th>Order Date</th><td><?php echo date('M d, Y h:i A', strtotime($row['payment_date'])); ?></td></tr>
                                                    <?php if($bakong_trx): ?>
                                                        <tr><th>Bakong Amount</th><td><?php echo $bakong_trx['currency'] === 'KHR' ? number_format($bakong_trx['amount']) . ' ៛ KHR' : '$' . number_format($bakong_trx['amount'], 2) . ' USD'; ?></td></tr>
                                                        <tr><th>KHQR MD5</th><td><code><?php echo htmlspecialchars($bakong_trx['khqr_md5']); ?></code></td></tr>
                                                    <?php endif; ?>
                                                </table>

                                                <div class="form-group">
                                                    <label>Transaction / Bank Transfer Reference (Optional):</label>
                                                    <input type="text" name="manual_ref" class="form-control" placeholder="e.g. ABA-123456789 or ACLEDA-TXN-...">
                                                    <small class="text-muted">Enter the banking app transaction hash or transfer receipt number for record keeping.</small>
                                                </div>

                                                <div class="form-group">
                                                    <label>Admin Verification Note (Optional):</label>
                                                    <textarea name="admin_note" class="form-control" rows="2" placeholder="e.g. Verified receipt received via ABA mobile"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success" style="font-weight:700;"><i class="fa fa-check"></i> Confirm Payment as PAID</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Shipping Status -->
                        <td style="text-align:center;">
                            <?php if($row['shipping_status'] === 'Completed'): ?>
                                <span class="label label-success" style="display:inline-block; padding:5px 10px; font-size:12px; border-radius:4px;"><i class="fa fa-truck"></i> Shipped</span>
                            <?php else: ?>
                                <span class="label label-info" style="display:inline-block; padding:5px 10px; font-size:12px; border-radius:4px;"><i class="fa fa-clock-o"></i> Pending</span>
                                <?php if($row['payment_status'] === 'Completed'): ?>
                                    <div style="margin-top:6px;">
                                        <a href="shipping-change-status.php?id=<?php echo $row['id']; ?>&task=Completed" class="btn btn-info btn-xs" style="width:100%; border-radius:4px; font-weight:600;"><i class="fa fa-truck"></i> Mark Shipped</a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>

                        <!-- Delete Action -->
	                    <td style="text-align:center;">
                            <a href="#" class="btn btn-danger btn-xs" data-href="order-delete.php?id=<?php echo $row['id']; ?>" data-toggle="modal" data-target="#confirm-delete" title="Delete Order"><i class="fa fa-trash"></i></a>
	                    </td>
	                </tr>
            		<?php
            	}
            	?>
            </tbody>
          </table>
        </div>
      </div>
  

</section>


<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">Delete Confirmation</h4>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this order? Stock will be restored if payment was incomplete.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger btn-ok">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
