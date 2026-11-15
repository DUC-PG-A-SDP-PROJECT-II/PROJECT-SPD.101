<?php require_once('header.php'); ?>

<?php
if(!isset($_REQUEST['id'])) {
	header('location: coupon.php');
	exit;
} else {
	// Check if coupon exists
	try {
		$statement = $pdo->prepare("SELECT * FROM tbl_coupon WHERE coupon_id=?");
		$statement->execute([$_REQUEST['id']]);
		$coupon = $statement->fetch(PDO::FETCH_ASSOC);
		if(!$coupon) {
			header('location: coupon.php');
			exit;
		}
	} catch (PDOException $e) {
		header('location: coupon.php');
		exit;
	}
}

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
		// Check duplicate code on other coupons
		try {
			$statement = $pdo->prepare("SELECT * FROM tbl_coupon WHERE coupon_code=? AND coupon_id != ?");
			$statement->execute([$coupon_code, $_REQUEST['id']]);
			if($statement->rowCount() > 0) {
				$valid = 0;
				$error_message .= "Coupon Code already exists for another promo.<br>";
			}
		} catch (PDOException $e) {
			$valid = 0;
			$error_message .= "Coupon table (tbl_coupon) does not exist in the database.<br>";
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
		try {
			$statement = $pdo->prepare("UPDATE tbl_coupon SET 
				coupon_code = ?,
				coupon_type = ?,
				coupon_value = ?,
				min_order_amount = ?,
				start_date = ?,
				expire_date = ?,
				usage_limit = ?,
				status = ?
				WHERE coupon_id = ?");
			$statement->execute([
				$coupon_code, $coupon_type, $coupon_value, $min_order_amount, $start_date, $expire_date, $usage_limit, $status, $_REQUEST['id']
			]);

			$success_message = "Coupon updated successfully!";

			// Refresh data
			$statement = $pdo->prepare("SELECT * FROM tbl_coupon WHERE coupon_id=?");
			$statement->execute([$_REQUEST['id']]);
			$coupon = $statement->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			$error_message .= "Coupon table (tbl_coupon) does not exist in the database.<br>";
		}
	}
}
?>

<section class="content-header">
	<div class="content-header-left">
		<h1 style="font-weight: 800; color: #0f172a; margin: 0;">Edit Coupon</h1>
		<p class="header-subtext">Modify promo code: <code><?php echo htmlspecialchars($coupon['coupon_code']); ?></code></p>
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
								<input type="text" autocomplete="off" class="form-control" name="coupon_code" id="coupon_code" value="<?php echo htmlspecialchars($coupon['coupon_code']); ?>" required style="text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">
							</div>
						</div>

						<!-- Discount Type -->
						<div class="form-group">
							<label for="coupon_type" class="col-sm-3 control-label">Discount Type <span>*</span></label>
							<div class="col-sm-7">
								<select name="coupon_type" id="coupon_type" class="form-control">
									<option value="percentage" <?php if($coupon['coupon_type']=='percentage') echo 'selected'; ?>>Percentage Discount (%)</option>
									<option value="fixed" <?php if($coupon['coupon_type']=='fixed') echo 'selected'; ?>>Fixed Amount Discount ($)</option>
								</select>
							</div>
						</div>

						<!-- Discount Value -->
						<div class="form-group">
							<label for="coupon_value" class="col-sm-3 control-label">Discount Value <span>*</span></label>
							<div class="col-sm-7">
								<input type="number" step="0.01" class="form-control" name="coupon_value" id="coupon_value" value="<?php echo (float)$coupon['coupon_value']; ?>" required>
							</div>
						</div>

						<!-- Minimum Order Spend -->
						<div class="form-group">
							<label for="min_order_amount" class="col-sm-3 control-label">Min Spend ($)</label>
							<div class="col-sm-7">
								<input type="number" step="0.01" class="form-control" name="min_order_amount" id="min_order_amount" value="<?php echo (float)$coupon['min_order_amount']; ?>">
								<small class="text-muted">Minimum cart subtotal required before this discount applies.</small>
							</div>
						</div>

						<!-- Start Date -->
						<div class="form-group">
							<label for="start_date" class="col-sm-3 control-label">Start Date</label>
							<div class="col-sm-7">
								<input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo $coupon['start_date']; ?>">
							</div>
						</div>

						<!-- Expire Date -->
						<div class="form-group">
							<label for="expire_date" class="col-sm-3 control-label">Expiry Date</label>
							<div class="col-sm-7">
								<input type="date" class="form-control" name="expire_date" id="expire_date" value="<?php echo $coupon['expire_date']; ?>">
							</div>
						</div>

						<!-- Usage Limit -->
						<div class="form-group">
							<label for="usage_limit" class="col-sm-3 control-label">Usage Limit</label>
							<div class="col-sm-7">
								<input type="number" class="form-control" name="usage_limit" id="usage_limit" value="<?php echo (int)$coupon['usage_limit']; ?>">
								<small class="text-muted">Redeemed so far: <strong><?php echo (int)$coupon['usage_count']; ?></strong> times.</small>
							</div>
						</div>

						<!-- Status -->
						<div class="form-group">
							<label for="status" class="col-sm-3 control-label">Status</label>
							<div class="col-sm-7">
								<select name="status" id="status" class="form-control">
									<option value="1" <?php if($coupon['status']==1) echo 'selected'; ?>>Active / Enabled</option>
									<option value="0" <?php if($coupon['status']==0) echo 'selected'; ?>>Inactive / Disabled</option>
								</select>
							</div>
						</div>

						<div class="form-group" style="margin-top: 30px;">
							<label class="col-sm-3 control-label"></label>
							<div class="col-sm-7">
								<button type="submit" class="btn btn-success" name="form1" style="font-weight: 700; padding: 10px 24px;">
									<i class="fa fa-save"></i> Save Changes
								</button>
								<a href="coupon.php" class="btn btn-default" style="margin-left: 10px;">Back</a>
							</div>
						</div>

					</div>
				</div>

			</form>

		</div>
	</div>
</section>

<?php require_once('footer.php'); ?>

