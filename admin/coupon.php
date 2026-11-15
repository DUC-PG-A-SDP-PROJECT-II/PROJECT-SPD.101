<?php require_once('header.php'); ?>

<section class="content-header">
	<div class="content-header-left">
		<h1 style="font-weight: 800; color: #0f172a; margin: 0;">
			<i class="fa fa-tag" style="color: #2563eb;"></i> Coupons & Discounts
		</h1>
		<p class="header-subtext">Create and manage promo codes, percentage & fixed discounts for customers.</p>
	</div>
	<div class="content-header-right">
		<a href="coupon-add.php" class="btn btn-primary btn-sm">
			<i class="fa fa-plus"></i> Add New Coupon
		</a>
	</div>
</section>

<section class="content">
	<div class="row">
		<div class="col-md-12">
			<?php
			$coupon_table_exists = true;
			$result = [];
			try {
				$statement = $pdo->prepare("SELECT * FROM tbl_coupon ORDER BY coupon_id DESC");
				$statement->execute();
				$result = $statement->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $e) {
				$coupon_table_exists = false;
				$result = [];
			}
			?>
			<?php if(!$coupon_table_exists): ?>
				<div class="alert alert-info alert-dismissible" style="border-radius: 8px;">
					<h4><i class="icon fa fa-info-circle"></i> Coupon Module Notice</h4>
					The <code>tbl_coupon</code> table is not present in your existing database. No coupons are currently available.
				</div>
			<?php endif; ?>
			
			<div class="box box-info">
				<div class="box-body table-responsive">
					<table id="example1" class="table table-bordered table-hover table-striped">
						<thead>
							<tr>
								<th style="width: 40px;">#</th>
								<th style="width: 140px;">Coupon Code</th>
								<th style="width: 120px;">Discount</th>
								<th>Min Spend</th>
								<th>Validity Period</th>
								<th>Usage</th>
								<th style="width: 100px; text-align:center;">Status</th>
								<th style="width: 130px; text-align:right;">Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$i = 0;						
							foreach ($result as $row) {
								$i++;
								$is_expired = false;
								if (!empty($row['expire_date']) && $row['expire_date'] < date('Y-m-d')) {
									$is_expired = true;
								}
								?>
								<tr>
									<td><?php echo $i; ?></td>
									<td>
										<strong style="font-size: 14px; letter-spacing: 0.5px; color: #0f172a;">
											<code><?php echo htmlspecialchars($row['coupon_code']); ?></code>
										</strong>
									</td>
									<td>
										<?php if ($row['coupon_type'] === 'percentage'): ?>
											<span class="label label-success" style="font-size: 12px; padding: 4px 8px;">
												<i class="fa fa-percent"></i> <?php echo (float)$row['coupon_value']; ?>% OFF
											</span>
										<?php else: ?>
											<span class="label label-info" style="font-size: 12px; padding: 4px 8px;">
												<i class="fa fa-dollar"></i> $<?php echo number_format($row['coupon_value'], 2); ?> OFF
											</span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ($row['min_order_amount'] > 0): ?>
											$<?php echo number_format($row['min_order_amount'], 2); ?>
										<?php else: ?>
											<span style="color: #94a3b8;">No minimum</span>
										<?php endif; ?>
									</td>
									<td>
										<div style="font-size: 12px;">
											<?php if (!empty($row['start_date']) && !empty($row['expire_date'])): ?>
												<?php echo date('M d, Y', strtotime($row['start_date'])); ?> &ndash; 
												<?php echo date('M d, Y', strtotime($row['expire_date'])); ?>
											<?php elseif (!empty($row['expire_date'])): ?>
												Expires: <?php echo date('M d, Y', strtotime($row['expire_date'])); ?>
											<?php else: ?>
												<span style="color: #10b981;">Lifetime valid</span>
											<?php endif; ?>
											
											<?php if ($is_expired): ?>
												<span class="label label-danger" style="font-size: 10px; margin-left: 4px;">Expired</span>
											<?php endif; ?>
										</div>
									</td>
									<td>
										<span style="font-weight: 600; color: #0f172a;"><?php echo (int)$row['usage_count']; ?></span>
										<span style="color: #64748b; font-size: 12px;">
											/ <?php echo ($row['usage_limit'] > 0) ? $row['usage_limit'] : '&infin;'; ?>
										</span>
									</td>
									<td style="text-align: center;">
										<?php if ($row['status'] == 1 && !$is_expired): ?>
											<a href="coupon-change-status.php?id=<?php echo $row['coupon_id']; ?>&task=0" class="label label-success" title="Click to Deactivate">
												<i class="fa fa-check"></i> Active
											</a>
										<?php else: ?>
											<a href="coupon-change-status.php?id=<?php echo $row['coupon_id']; ?>&task=1" class="label label-danger" title="Click to Activate">
												<i class="fa fa-ban"></i> Inactive
											</a>
										<?php endif; ?>
									</td>
									<td style="text-align: right;">
										<a href="coupon-edit.php?id=<?php echo $row['coupon_id']; ?>" class="btn btn-primary btn-xs" title="Edit">
											<i class="fa fa-pencil"></i> Edit
										</a>
										<a href="#" class="btn btn-danger btn-xs" data-href="coupon-delete.php?id=<?php echo $row['coupon_id']; ?>" data-toggle="modal" data-target="#confirm-delete" title="Delete">
											<i class="fa fa-trash"></i>
										</a>
									</td>
								</tr>
								<?php
							}
							?>							
						</tbody>
					</table>
				</div>
			</div>

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
                Are you sure you want to delete this coupon code?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger btn-ok">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>

