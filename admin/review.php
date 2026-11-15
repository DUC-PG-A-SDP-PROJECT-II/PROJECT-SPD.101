<?php require_once('header.php'); ?>

<section class="content-header">
	<div class="content-header-left">
		<h1 style="font-weight: 800; color: #0f172a; margin: 0;">
			<i class="fa fa-star" style="color: #f59e0b;"></i> Customer Reviews & Ratings
		</h1>
		<p class="header-subtext">Monitor customer feedback, star ratings, and moderate product reviews.</p>
	</div>
</section>

<section class="content">
	<div class="row">
		<div class="col-md-12">
			
			<div class="box box-info">
				<div class="box-body table-responsive">
					<table id="example1" class="table table-bordered table-hover table-striped">
						<thead>
							<tr>
								<th style="width: 35px;">#</th>
								<th style="width: 220px;">Product</th>
								<th style="width: 180px;">Customer</th>
								<th style="width: 120px;">Rating</th>
								<th>Review Comment</th>
								<th style="width: 110px;">Date</th>
								<th style="width: 90px; text-align:center;">Status</th>
								<th style="width: 80px; text-align:right;">Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$i = 0;
							$statement = $pdo->prepare("SELECT r.*, p.p_name, p.p_featured_photo, c.cust_name, c.cust_email, c.cust_photo
														FROM tbl_rating r
														JOIN tbl_product p ON r.p_id = p.p_id
														JOIN tbl_customer c ON r.cust_id = c.cust_id
														ORDER BY r.rt_id DESC");
							$statement->execute();
							$result = $statement->fetchAll(PDO::FETCH_ASSOC);							
							foreach ($result as $row) {
								$i++;
								$rating_val = (int)$row['rating'];
								$review_date = !empty($row['rating_date']) ? date('M d, Y', strtotime($row['rating_date'])) : 'Recent';
								?>
								<tr>
									<td><?php echo $i; ?></td>
									<td>
										<div style="display: flex; align-items: center; gap: 10px;">
											<img src="../assets/uploads/<?php echo htmlspecialchars($row['p_featured_photo']); ?>" 
											     alt="" 
											     width="44" height="44" 
											     style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0; flex-shrink:0;">
											<div style="min-width:0; overflow:hidden;">
												<a href="../product.php?id=<?php echo $row['p_id']; ?>" target="_blank" style="font-weight:600; color:#0f172a; font-size:12px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
													<?php echo htmlspecialchars($row['p_name']); ?>
												</a>
												<span style="font-size:11px; color:#64748b;">ID: #<?php echo $row['p_id']; ?></span>
											</div>
										</div>
									</td>
									<td>
										<strong style="color: #0f172a; font-size: 13px;"><?php echo htmlspecialchars($row['cust_name']); ?></strong>
										<div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($row['cust_email']); ?></div>
									</td>
									<td>
										<div style="color: #ffa41c; font-size: 13px;">
											<?php
											for ($s = 1; $s <= 5; $s++) {
												if ($s <= $rating_val) {
													echo '<i class="fa fa-star"></i>';
												} else {
													echo '<i class="fa fa-star-o"></i>';
												}
											}
											?>
											<span style="font-size: 11px; font-weight:700; color:#0f172a; margin-left:4px;">(<?php echo $rating_val; ?>/5)</span>
										</div>
									</td>
									<td>
										<div style="font-size: 13px; color: #334155; line-height: 1.5; max-width: 450px;">
											<?php echo nl2br(htmlspecialchars($row['comment'])); ?>
										</div>
									</td>
									<td>
										<span style="font-size: 12px; color: #64748b;"><?php echo $review_date; ?></span>
									</td>
									<td style="text-align: center;">
										<?php if (isset($row['status']) && $row['status'] == 1): ?>
											<a href="review-change-status.php?id=<?php echo $row['rt_id']; ?>&task=0" class="label label-success" title="Click to Hide from Store">
												<i class="fa fa-check"></i> Approved
											</a>
										<?php else: ?>
											<a href="review-change-status.php?id=<?php echo $row['rt_id']; ?>&task=1" class="label label-warning" title="Click to Approve">
												<i class="fa fa-eye-slash"></i> Hidden
											</a>
										<?php endif; ?>
									</td>
									<td style="text-align: right;">
										<a href="#" class="btn btn-danger btn-xs" data-href="review-delete.php?id=<?php echo $row['rt_id']; ?>" data-toggle="modal" data-target="#confirm-delete" title="Delete Review">
											<i class="fa fa-trash"></i> Delete
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
                Are you sure you want to delete this customer review?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger btn-ok">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>

