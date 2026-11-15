<?php require_once('header.php'); ?>

<?php
$error_message = '';
$success_message = '';

if(isset($_POST['form1'])) {
	$valid = 1;

	$coupon_code = strtoupper(trim(strip_tags($_POST['coupon_code'])));
	$coupon_type = $_POST['coupon_type'];
	$coupon_value = (float)$_POST['coupon_value'];
	$min_order_amount = !empty($_POST['min_order_amount']) ? (float)$_POST['min_order_amount'] : 0.00;
	$start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
	$expire_date = !empty($_POST['expire_date']) ? $_POST['expire_date'] : null;
	$usage_limit = isset($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : 0;
	$status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

	if(empty($coupon_code)) {
		$valid = 0;
		$error_message .= "Coupon Code cannot be empty.<br>";
	} else {
		// Check duplicate code
		$statement = $pdo->prepare("SELECT * FROM tbl_coupon WHERE coupon_code=?");
		$statement->execute([$coupon_code]);
		if($statement->rowCount() > 0) {
			$valid = 0;
			$error_message .= "Coupon Code already exists. Please choose a different code.<br>";
		}
	}

	if($coupon_value <= 0) {
		$valid = 0;
		$error_message .= "Discount value must be greater than 0.<br>";
	}

	if($coupon_type === 'percentage' && $coupon_value > 100) {
		$valid = 0;
		$error_message .= "Percentage discount cannot exceed 100%.<br>";
	}

	if($valid == 1) {
		$statement = $pdo->prepare("INSERT INTO tbl_coupon (
			coupon_code, coupon_type, coupon_value, min_order_amount, start_date, expire_date, usage_limit, usage_count, status
		) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");
		$statement->execute([
			$coupon_code, $coupon_type, $coupon_value, $min_order_amount, $start_date, $expire_date, $usage_limit, $status
		]);

		$success_message = "Coupon code '{$coupon_code}' was created successfully!";
	}
}
?>

<section class="content-header">
	<div class="content-header-left">
		<h1 style="font-weight: 800; color: #0f172a; margin: 0;">Add New Coupon</h1>
		<p class="header-subtext">Set up promo codes, discount percentages, and validity rules.</p>
	</div>
	<div class="content-header-right">
		<a href="coupon.php" class="btn btn-primary btn-sm">
			<i class="fa fa-list"></i> View All Coupons
		</a>
	</div>
</section>

<section class="content">
	<div class="row">
		<div class="col-md-8 col-md-offset-2">

			<?php if($error_message): ?>
			<div class="callout callout-danger" style="border-radius: 8px;">
				<p><?php echo $error_message; ?></p>
			</div>
			<?php endif; ?>

			<?php if($success_message): ?>
			<div class="callout callout-success" style="border-radius: 8px;">
				<p><?php echo $success_message; ?></p>
			</div>
			<?php endif; ?>

			<form class="form-horizontal" action="" method="post">
				<?php $csrf->echoInputField(); ?>

				<div class="box box-info" style="border-radius: 12px; padding: 20px;">
					<div class="box-body">

						<!-- Coupon Code -->
						<div class="form-group">
							<label for="coupon_code" class="col-sm-3 control-label">Coupon Code <span>*</span></label>
							<div class="col-sm-7">
								<div class="input-group">
									<input type="text" autocomplete="off" class="form-control" name="coupon_code" id="coupon_code" placeholder="e.g. SUMMER25" value="<?php echo isset($_POST['coupon_code']) ? htmlspecialchars($_POST['coupon_code']) : ''; ?>" required style="text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">
									<span class="input-group-btn">
										<button class="btn btn-default" type="button" onclick="generateCode()" title="Auto-generate a random promo code">
											<i class="fa fa-magic"></i> Auto Generate
										</button>
									</span>
								</div>
							</div>
						</div>

						<!-- Discount Type -->
						<div class="form-group">
							<label for="coupon_type" class="col-sm-3 control-label">Discount Type <span>*</span></label>
							<div class="col-sm-7">
								<select name="coupon_type" id="coupon_type" class="form-control">
									<option value="percentage">Percentage Discount (%)</option>
									<option value="fixed">Fixed Amount Discount ($)</option>
								</select>
							</div>
						</div>

						<!-- Discount Value -->
						<div class="form-group">
							<label for="coupon_value" class="col-sm-3 control-label">Discount Value <span>*</span></label>
							<div class="col-sm-7">
								<input type="number" step="0.01" class="form-control" name="coupon_value" id="coupon_value" placeholder="e.g. 15 for 15% or $15" value="<?php echo isset($_POST['coupon_value']) ? htmlspecialchars($_POST['coupon_value']) : ''; ?>" required>
							</div>
						</div>

						<!-- Minimum Order Spend -->
						<div class="form-group">
							<label for="min_order_amount" class="col-sm-3 control-label">Min Spend ($)</label>
							<div class="col-sm-7">
								<input type="number" step="0.01" class="form-control" name="min_order_amount" id="min_order_amount" placeholder="0.00 for no minimum" value="<?php echo isset($_POST['min_order_amount']) ? htmlspecialchars($_POST['min_order_amount']) : '0.00'; ?>">
								<small class="text-muted">Minimum cart subtotal required before this discount applies.</small>
							</div>
						</div>

						<!-- Start Date -->
						<div class="form-group">
							<label for="start_date" class="col-sm-3 control-label">Start Date</label>
							<div class="col-sm-7">
								<input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d'); ?>">
							</div>
						</div>

						<!-- Expire Date -->
						<div class="form-group">
							<label for="expire_date" class="col-sm-3 control-label">Expiry Date</label>
							<div class="col-sm-7">
								<input type="date" class="form-control" name="expire_date" id="expire_date" value="<?php echo isset($_POST['expire_date']) ? htmlspecialchars($_POST['expire_date']) : date('Y-m-d', strtotime('+30 days')); ?>">
								<small class="text-muted">Leave empty or far future for indefinite coupons.</small>
							</div>
						</div>

						<!-- Usage Limit -->
						<div class="form-group">
							<label for="usage_limit" class="col-sm-3 control-label">Usage Limit</label>
							<div class="col-sm-7">
								<input type="number" class="form-control" name="usage_limit" id="usage_limit" placeholder="0 for unlimited uses" value="<?php echo isset($_POST['usage_limit']) ? htmlspecialchars($_POST['usage_limit']) : '0'; ?>">
								<small class="text-muted">Max total redemptions permitted across all shoppers.</small>
							</div>
						</div>

						<!-- Status -->
						<div class="form-group">
							<label for="status" class="col-sm-3 control-label">Status</label>
							<div class="col-sm-7">
								<select name="status" id="status" class="form-control">
									<option value="1">Active / Enabled</option>
									<option value="0">Inactive / Disabled</option>
								</select>
							</div>
						</div>

						<div class="form-group" style="margin-top: 30px;">
							<label class="col-sm-3 control-label"></label>
							<div class="col-sm-7">
								<button type="submit" class="btn btn-success" name="form1" style="font-weight: 700; padding: 10px 24px;">
									<i class="fa fa-check"></i> Create Coupon
								</button>
								<a href="coupon.php" class="btn btn-default" style="margin-left: 10px;">Cancel</a>
							</div>
						</div>

					</div>
				</div>

			</form>

		</div>
	</div>
</section>

<script>
function generateCode() {
	var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	var code = 'SAVE';
	for (var i = 0; i < 6; i++) {
		code += chars.charAt(Math.floor(Math.random() * chars.length));
	}
	document.getElementById('coupon_code').value = code;
}
</script>

<?php require_once('footer.php'); ?>

