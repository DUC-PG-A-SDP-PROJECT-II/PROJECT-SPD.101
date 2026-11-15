<?php require_once('header.php'); ?>

<?php
// Date range filter
$range = isset($_GET['range']) ? $_GET['range'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$today = date('Y-m-d');

if ($range === 'today') {
    $start_date = $today;
    $end_date = $today;
} elseif ($range === 'week') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = $today;
} elseif ($range === 'month') {
    $start_date = date('Y-m-01');
    $end_date = $today;
} elseif ($range === 'year') {
    $start_date = date('Y-01-01');
    $end_date = $today;
}

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

// Aggregate metrics
$stmt_agg = $pdo->prepare("SELECT 
    COUNT(DISTINCT p.id) AS total_orders,
    SUM(p.paid_amount) AS total_revenue
FROM tbl_payment p
WHERE $where_sql");
$stmt_agg->execute($params);
$agg = $stmt_agg->fetch(PDO::FETCH_ASSOC);

$total_revenue = (float)($agg['total_revenue'] ?? 0);
$total_orders = (int)($agg['total_orders'] ?? 0);
$aov = ($total_orders > 0) ? ($total_revenue / $total_orders) : 0;

// Total items/units sold in matching payments
$stmt_units = $pdo->prepare("SELECT SUM(o.quantity) AS total_units 
                             FROM tbl_order o 
                             JOIN tbl_payment p ON o.payment_id = p.payment_id 
                             WHERE $where_sql");
$stmt_units->execute($params);
$total_units = (int)($stmt_units->fetchColumn() ?? 0);

// Sales by Period / Month
$stmt_by_month = $pdo->prepare("SELECT 
    DATE_FORMAT(payment_date, '%Y-%m') AS period,
    COUNT(DISTINCT id) AS order_cnt,
    SUM(paid_amount) AS rev
FROM tbl_payment
WHERE $where_sql
GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
ORDER BY period DESC LIMIT 12");
$stmt_by_month->execute($params);
$sales_by_month = $stmt_by_month->fetchAll(PDO::FETCH_ASSOC);

$max_month_rev = 1;
foreach ($sales_by_month as $m) {
    if ((float)$m['rev'] > $max_month_rev) {
        $max_month_rev = (float)$m['rev'];
    }
}

// Top Selling Products
$stmt_top_prod = $pdo->prepare("SELECT 
    o.product_id,
    o.product_name,
    SUM(o.quantity) AS total_qty_sold,
    SUM(o.quantity * o.unit_price) AS total_sales,
    p.p_featured_photo,
    p.p_qty
FROM tbl_order o
JOIN tbl_payment p_pay ON o.payment_id = p_pay.payment_id
LEFT JOIN tbl_product p ON o.product_id = p.p_id
WHERE $where_sql
GROUP BY o.product_id, o.product_name
ORDER BY total_sales DESC
LIMIT 5");
$stmt_top_prod->execute($params);
$top_products = $stmt_top_prod->fetchAll(PDO::FETCH_ASSOC);

// Payment Methods breakdown
$stmt_pay_methods = $pdo->prepare("SELECT 
    payment_method,
    COUNT(*) as txn_count,
    SUM(paid_amount) as total_amount
FROM tbl_payment
WHERE $where_sql
GROUP BY payment_method");
$stmt_pay_methods->execute($params);
$payment_methods = $stmt_pay_methods->fetchAll(PDO::FETCH_ASSOC);

// Top Customers
$stmt_top_cust = $pdo->prepare("SELECT 
    customer_id,
    customer_name,
    customer_email,
    COUNT(*) as total_orders,
    SUM(paid_amount) as total_spent,
    MAX(payment_date) as last_order_date
FROM tbl_payment
WHERE $where_sql
GROUP BY customer_id, customer_name, customer_email
ORDER BY total_spent DESC
LIMIT 5");
$stmt_top_cust->execute($params);
$top_customers = $stmt_top_cust->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="content-header">
	<div class="content-header-left">
		<h1 style="font-weight: 800; color: #0f172a; margin: 0;">
			<i class="fa fa-bar-chart" style="color: #2563eb;"></i> Sales & Financial Reports
		</h1>
		<p class="header-subtext">Deep-dive business analytics, revenue trends, top-selling items & VIP customers.</p>
	</div>
	<div class="content-header-right">
		<a href="reports-csv.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-success btn-sm">
			<i class="fa fa-download"></i> Export to CSV
		</a>
		<button type="button" class="btn btn-default btn-sm" onclick="window.print()" style="margin-left: 6px;">
			<i class="fa fa-print"></i> Print Report
		</button>
	</div>
</section>

<section class="content">

	<!-- Date Filtering Hub -->
	<div class="box box-info" style="border-radius: 12px; margin-bottom: 22px;">
		<div class="box-body" style="padding: 16px 20px;">
			<form method="get" action="reports.php" class="form-inline" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
				<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
					<span style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Quick Presets:</span>
					<a href="reports.php?range=all" class="btn btn-xs <?php echo ($range==='all') ? 'btn-primary' : 'btn-default'; ?>" style="border-radius:20px;">All Time</a>
					<a href="reports.php?range=today" class="btn btn-xs <?php echo ($range==='today') ? 'btn-primary' : 'btn-default'; ?>" style="border-radius:20px;">Today</a>
					<a href="reports.php?range=week" class="btn btn-xs <?php echo ($range==='week') ? 'btn-primary' : 'btn-default'; ?>" style="border-radius:20px;">Last 7 Days</a>
					<a href="reports.php?range=month" class="btn btn-xs <?php echo ($range==='month') ? 'btn-primary' : 'btn-default'; ?>" style="border-radius:20px;">This Month</a>
					<a href="reports.php?range=year" class="btn btn-xs <?php echo ($range==='year') ? 'btn-primary' : 'btn-default'; ?>" style="border-radius:20px;">This Year</a>
				</div>

				<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
					<input type="date" name="start_date" class="form-control input-sm" value="<?php echo $start_date; ?>" title="Start Date">
					<span style="color:#64748b;">to</span>
					<input type="date" name="end_date" class="form-control input-sm" value="<?php echo $end_date; ?>" title="End Date">
					<button type="submit" class="btn btn-primary btn-sm" style="border-radius:6px; font-weight:600;">
						<i class="fa fa-filter"></i> Apply Filter
					</button>
				</div>
			</form>
		</div>
	</div>

	<!-- 4 Financial Summary KPI Cards -->
	<div class="row" style="margin-bottom: 20px;">
		<div class="col-lg-3 col-sm-6 col-xs-12">
			<div class="modern-kpi-card kpi-green">
				<div class="kpi-content">
					<p>Gross Completed Sales</p>
					<h3><?php echo format_price($total_revenue); ?></h3>
					<span class="kpi-subtext"><i class="fa fa-check-circle" style="color:#10b981;"></i> Net collected revenue</span>
				</div>
				<div class="kpi-icon-wrap"><i class="fa fa-dollar"></i></div>
			</div>
		</div>

		<div class="col-lg-3 col-sm-6 col-xs-12">
			<div class="modern-kpi-card kpi-blue">
				<div class="kpi-content">
					<p>Paid Orders</p>
					<h3><?php echo $total_orders; ?></h3>
					<span class="kpi-subtext"><i class="fa fa-shopping-cart" style="color:#2563eb;"></i> Completed purchases</span>
				</div>
				<div class="kpi-icon-wrap"><i class="fa fa-shopping-bag"></i></div>
			</div>
		</div>

		<div class="col-lg-3 col-sm-6 col-xs-12">
			<div class="modern-kpi-card kpi-orange">
				<div class="kpi-content">
					<p>Avg Order Value (AOV)</p>
					<h3><?php echo format_price($aov); ?></h3>
					<span class="kpi-subtext"><i class="fa fa-line-chart" style="color:#f59e0b;"></i> Average ticket size</span>
				</div>
				<div class="kpi-icon-wrap"><i class="fa fa-balance-scale"></i></div>
			</div>
		</div>

		<div class="col-lg-3 col-sm-6 col-xs-12">
			<div class="modern-kpi-card kpi-purple">
				<div class="kpi-content">
					<p>Units Sold</p>
					<h3><?php echo $total_units; ?> Items</h3>
					<span class="kpi-subtext"><i class="fa fa-cube" style="color:#8b5cf6;"></i> Products checked out</span>
				</div>
				<div class="kpi-icon-wrap"><i class="fa fa-cubes"></i></div>
			</div>
		</div>
	</div>

	<!-- Main Analytics Grid -->
	<div class="row">

		<!-- Left Column: Sales Over Time & Top Customers -->
		<div class="col-lg-7 col-md-12">
			
			<!-- Sales Over Time Trend -->
			<div class="box box-info" style="border-radius:12px; margin-bottom:24px;">
				<div class="box-header">
					<h3 class="box-title"><i class="fa fa-calendar" style="color:#2563eb;"></i> Sales by Period</h3>
				</div>
				<div class="box-body table-responsive">
					<table class="table table-hover">
						<thead>
							<tr>
								<th>Period</th>
								<th>Orders</th>
								<th>Total Revenue</th>
								<th style="width: 40%;">Performance</th>
							</tr>
						</thead>
						<tbody>
							<?php if(empty($sales_by_month)): ?>
								<tr><td colspan="4" class="text-center" style="padding:25px; color:#94a3b8;">No transactions found for this period.</td></tr>
							<?php else: ?>
								<?php foreach($sales_by_month as $sm): 
									$pct = round(((float)$sm['rev'] / $max_month_rev) * 100);
								?>
									<tr>
										<td><strong style="color:#0f172a;"><?php echo date('F Y', strtotime($sm['period'].'-01')); ?></strong></td>
										<td><span class="label label-default"><?php echo (int)$sm['order_cnt']; ?> orders</span></td>
										<td><strong style="color:#0f172a; font-size:14px;"><?php echo format_price((float)$sm['rev']); ?></strong></td>
										<td>
											<div class="progress" style="height: 10px; margin: 5px 0 0; border-radius: 6px; background-color: #f1f5f9;">
												<div class="progress-bar progress-bar-primary" role="progressbar" style="width: <?php echo $pct; ?>%; background: linear-gradient(135deg, #2563eb, #3b82f6); border-radius: 6px;"></div>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Top Spending Customers (VIPs) -->
			<div class="box box-info" style="border-radius:12px; margin-bottom:24px;">
				<div class="box-header">
					<h3 class="box-title"><i class="fa fa-trophy" style="color:#f59e0b;"></i> Top Spenders / VIP Customers</h3>
				</div>
				<div class="box-body table-responsive">
					<table class="table table-hover">
						<thead>
							<tr>
								<th>Customer</th>
								<th>Orders</th>
								<th>Total Spent</th>
								<th>Last Active</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($top_customers as $tc): ?>
								<tr>
									<td>
										<strong style="color:#0f172a;"><?php echo htmlspecialchars($tc['customer_name']); ?></strong>
										<div style="font-size:11px; color:#64748b;"><?php echo htmlspecialchars($tc['customer_email']); ?></div>
									</td>
									<td><span class="label label-default"><?php echo (int)$tc['total_orders']; ?> orders</span></td>
									<td><strong style="color:#10b981; font-size:14px;"><?php echo format_price((float)$tc['total_spent']); ?></strong></td>
									<td><span style="font-size:12px; color:#64748b;"><?php echo date('M d, Y', strtotime($tc['last_order_date'])); ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

		</div>

		<!-- Right Column: Top Products & Payment Gateways -->
		<div class="col-lg-5 col-md-12">
			
			<!-- Top Selling Products -->
			<div class="box box-info" style="border-radius:12px; margin-bottom:24px;">
				<div class="box-header">
					<h3 class="box-title"><i class="fa fa-fire" style="color:#ef4444;"></i> Top Products by Revenue</h3>
				</div>
				<div class="box-body table-responsive">
					<table class="table table-hover">
						<thead>
							<tr>
								<th>Product</th>
								<th>Sold</th>
								<th style="text-align:right;">Sales ($)</th>
							</tr>
						</thead>
						<tbody>
							<?php if(empty($top_products)): ?>
								<tr><td colspan="3" class="text-center" style="padding:25px; color:#94a3b8;">No products sold in this period.</td></tr>
							<?php else: ?>
								<?php foreach($top_products as $tp): ?>
									<tr>
										<td>
											<div style="display:flex; align-items:center; gap:10px;">
												<img src="../assets/uploads/<?php echo htmlspecialchars($tp['p_featured_photo']); ?>" 
												     alt="" 
												     width="44" height="44" 
												     style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0; flex-shrink:0;">
												<div style="min-width:0; overflow:hidden;">
													<a href="../product.php?id=<?php echo $tp['product_id']; ?>" target="_blank" style="font-weight:600; color:#0f172a; font-size:12px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
														<?php echo htmlspecialchars($tp['product_name']); ?>
													</a>
													<span style="font-size:11px; color:#64748b;">Stock remaining: <?php echo (int)$tp['p_qty']; ?></span>
												</div>
											</div>
										</td>
										<td>
											<span class="label label-default"><?php echo (int)$tp['total_qty_sold']; ?> sold</span>
										</td>
										<td style="text-align:right;">
											<strong style="color:#0f172a; font-size:13px;"><?php echo format_price((float)$tp['total_sales']); ?></strong>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Payment Method Share -->
			<div class="box box-info" style="border-radius:12px; margin-bottom:24px;">
				<div class="box-header">
					<h3 class="box-title"><i class="fa fa-credit-card" style="color:#8b5cf6;"></i> Payment Methods Share</h3>
				</div>
				<div class="box-body table-responsive">
					<table class="table table-hover">
						<thead>
							<tr>
								<th>Method</th>
								<th>Transactions</th>
								<th style="text-align:right;">Total Volume</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($payment_methods as $pm): 
								$share_pct = ($total_revenue > 0) ? round(((float)$pm['total_amount'] / $total_revenue) * 100) : 0;
							?>
								<tr>
									<td>
										<strong style="color:#0f172a;"><?php echo htmlspecialchars($pm['payment_method']); ?></strong>
										<span class="label label-info" style="margin-left:6px;"><?php echo $share_pct; ?>%</span>
									</td>
									<td><?php echo (int)$pm['txn_count']; ?> orders</td>
									<td style="text-align:right;">
										<strong style="color:#0f172a;"><?php echo format_price((float)$pm['total_amount']); ?></strong>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

		</div>

	</div>

</section>

<?php require_once('footer.php'); ?>

