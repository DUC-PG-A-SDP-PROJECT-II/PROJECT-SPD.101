<?php require_once('header.php'); ?>

<style>
/* Dashboard Safeguard & Scoped Modern Rules */
.dashboard-wrapper {
    max-width: 1600px;
    margin: 0 auto;
}
.kpi-grid {
    display: flex;
    flex-wrap: wrap;
    margin-right: -10px;
    margin-left: -10px;
    margin-bottom: 20px;
}
.kpi-col {
    padding-left: 10px;
    padding-right: 10px;
    flex: 1 1 240px;
    margin-bottom: 15px;
}
.kpi-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 18px rgba(15,23,42,0.03);
    position: relative;
    overflow: hidden;
    height: 100%;
}
.kpi-box::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
}
.kpi-box.green::before { background: #10b981; }
.kpi-box.blue::before { background: #2563eb; }
.kpi-box.orange::before { background: #f59e0b; }
.kpi-box.purple::before { background: #8b5cf6; }

.kpi-info-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #64748b;
    margin: 0 0 5px;
}
.kpi-info-val {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin: 0 0 6px;
    letter-spacing: -0.5px;
}
.kpi-info-sub {
    font-size: 12px;
    color: #64748b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 4px;
}
.kpi-icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.kpi-box.green .kpi-icon-circle { background: #ecfdf5; color: #10b981; }
.kpi-box.blue .kpi-icon-circle { background: #eff6ff; color: #2563eb; }
.kpi-box.orange .kpi-icon-circle { background: #fffbeb; color: #f59e0b; }
.kpi-box.purple .kpi-icon-circle { background: #f5f3ff; color: #8b5cf6; }

.thumb-44 {
    width: 44px !important;
    height: 44px !important;
    min-width: 44px !important;
    min-height: 44px !important;
    max-width: 44px !important;
    max-height: 44px !important;
    border-radius: 6px !important;
    border: 1px solid #e2e8f0 !important;
    object-fit: cover !important;
    background: #f8fafc !important;
    flex-shrink: 0 !important;
}
.dash-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    margin-bottom: 24px;
    overflow: hidden;
}
.dash-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
}
.dash-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dash-card-body {
    padding: 0;
}
.dash-table {
    width: 100%;
    margin-bottom: 0;
}
.dash-table th {
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e2e8f0;
    padding: 12px 16px;
    border-top: none;
}
.dash-table td {
    padding: 12px 16px;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
    font-size: 13px;
    color: #1e293b;
}
.dash-table tbody tr:hover {
    background-color: #f8fafc;
}
.dash-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    text-decoration: none !important;
}
.dash-badge-pill.success { background: #dcfce7; color: #15803d; }
.dash-badge-pill.warning { background: #fef3c7; color: #b45309; }
.dash-badge-pill.info { background: #e0f2fe; color: #0369a1; }
.dash-badge-pill.danger { background: #fee2e2; color: #b91c1c; }
.dash-badge-pill.default { background: #f1f5f9; color: #475569; }

.quick-hub-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}
.quick-hub-link {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 8px 16px;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    text-decoration: none !important;
    transition: all 0.2s;
}
.quick-hub-link:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
    transform: translateY(-1px);
}
.quick-hub-link i {
    color: #2563eb;
}
</style>

<?php
// Compute Gross Revenue
$total_revenue = 0.0;
$count_paid_orders = 0;
try {
    $stmt_rev = $pdo->prepare("SELECT SUM(paid_amount) AS total_revenue, COUNT(*) AS count_paid FROM tbl_payment WHERE payment_status='Completed'");
    $stmt_rev->execute();
    $rev_data = $stmt_rev->fetch(PDO::FETCH_ASSOC);
    $total_revenue = (float)($rev_data['total_revenue'] ?? 0);
    $count_paid_orders = (int)($rev_data['count_paid'] ?? 0);
} catch (PDOException $e) {
    $total_revenue = 0.0;
    $count_paid_orders = 0;
}

// Total Orders
$total_orders = 0;
try {
    $stmt_ord = $pdo->prepare("SELECT COUNT(*) AS total_orders FROM tbl_payment");
    $stmt_ord->execute();
    $total_orders = (int)$stmt_ord->fetchColumn();
} catch (PDOException $e) {
    $total_orders = 0;
}

// Pending Orders
$total_order_pending = 0;
try {
    $stmt_pen = $pdo->prepare("SELECT COUNT(*) FROM tbl_payment WHERE payment_status='Pending'");
    $stmt_pen->execute();
    $total_order_pending = (int)$stmt_pen->fetchColumn();
} catch (PDOException $e) {
    $total_order_pending = 0;
}

// Completed Shipping
$total_shipping_completed = 0;
try {
    $stmt_ship = $pdo->prepare("SELECT COUNT(*) FROM tbl_payment WHERE shipping_status='Completed'");
    $stmt_ship->execute();
    $total_shipping_completed = (int)$stmt_ship->fetchColumn();
} catch (PDOException $e) {
    $total_shipping_completed = 0;
}

// Pending Shipping on Paid Orders
$total_order_complete_shipping_pending = 0;
try {
    $stmt_ship_pen = $pdo->prepare("SELECT COUNT(*) FROM tbl_payment WHERE payment_status='Completed' AND shipping_status='Pending'");
    $stmt_ship_pen->execute();
    $total_order_complete_shipping_pending = (int)$stmt_ship_pen->fetchColumn();
} catch (PDOException $e) {
    $total_order_complete_shipping_pending = 0;
}

// Products count & Low Stock count
$total_active_products = 0;
try {
    $stmt_prod = $pdo->prepare("SELECT COUNT(*) FROM tbl_product WHERE p_is_active=1");
    $stmt_prod->execute();
    $total_active_products = (int)$stmt_prod->fetchColumn();
} catch (PDOException $e) {
    $total_active_products = 0;
}

$total_low_stock = 0;
try {
    $stmt_low = $pdo->prepare("SELECT COUNT(*) FROM tbl_product WHERE p_qty <= 20");
    $stmt_low->execute();
    $total_low_stock = (int)$stmt_low->fetchColumn();
} catch (PDOException $e) {
    $total_low_stock = 0;
}

// Customers & Subscribers
$total_customers = 0;
try {
    $stmt_cust = $pdo->prepare("SELECT COUNT(*) FROM tbl_customer WHERE cust_status='1'");
    $stmt_cust->execute();
    $total_customers = (int)$stmt_cust->fetchColumn();
} catch (PDOException $e) {
    $total_customers = 0;
}

$total_subscriber = 0;
try {
    $stmt_subs = $pdo->prepare("SELECT COUNT(*) FROM tbl_subscriber WHERE subs_active='1'");
    $stmt_subs->execute();
    $total_subscriber = (int)$stmt_subs->fetchColumn();
} catch (PDOException $e) {
    $total_subscriber = 0;
}

// Categories & Shipping Rates
$total_top_category = 0;
try {
    $stmt_top = $pdo->prepare("SELECT COUNT(*) FROM tbl_top_category");
    $stmt_top->execute();
    $total_top_category = (int)$stmt_top->fetchColumn();
} catch (PDOException $e) {
    $total_top_category = 0;
}

$total_mid_category = 0;
try {
    $stmt_mid = $pdo->prepare("SELECT COUNT(*) FROM tbl_mid_category");
    $stmt_mid->execute();
    $total_mid_category = (int)$stmt_mid->fetchColumn();
} catch (PDOException $e) {
    $total_mid_category = 0;
}

$total_end_category = 0;
try {
    $stmt_end = $pdo->prepare("SELECT COUNT(*) FROM tbl_end_category");
    $stmt_end->execute();
    $total_end_category = (int)$stmt_end->fetchColumn();
} catch (PDOException $e) {
    $total_end_category = 0;
}

$available_shipping = 0;
try {
    $stmt_ship_cost = $pdo->prepare("SELECT COUNT(*) FROM tbl_shipping_cost");
    $stmt_ship_cost->execute();
    $available_shipping = (int)$stmt_ship_cost->fetchColumn();
} catch (PDOException $e) {
    $available_shipping = 0;
}

// Fetch Active Coupons (safe fallback if tbl_coupon does not exist in database)
$total_active_coupons = 0;
try {
    $stmt_cpn_cnt = $pdo->prepare("SELECT COUNT(*) FROM tbl_coupon WHERE status = 1");
    $stmt_cpn_cnt->execute();
    $total_active_coupons = (int)$stmt_cpn_cnt->fetchColumn();
} catch (PDOException $e) {
    $total_active_coupons = 0;
}

// Fetch Total Reviews
$total_reviews = 0;
try {
    $stmt_rev_cnt = $pdo->prepare("SELECT COUNT(*) FROM tbl_rating");
    $stmt_rev_cnt->execute();
    $total_reviews = (int)$stmt_rev_cnt->fetchColumn();
} catch (PDOException $e) {
    $total_reviews = 0;
}

// Fetch Low Stock Products
$low_stock_products = [];
try {
    $stmt_low_list = $pdo->prepare("SELECT p.*, ec.ecat_name 
                                    FROM tbl_product p 
                                    LEFT JOIN tbl_end_category ec ON p.ecat_id = ec.ecat_id 
                                    WHERE p.p_qty <= 20 
                                    ORDER BY p.p_qty ASC LIMIT 6");
    $stmt_low_list->execute();
    $low_stock_products = $stmt_low_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $low_stock_products = [];
}

// Fetch Recent Orders
$recent_orders = [];
try {
    $stmt_recent_ord = $pdo->prepare("SELECT * FROM tbl_payment ORDER BY id DESC LIMIT 7");
    $stmt_recent_ord->execute();
    $recent_orders = $stmt_recent_ord->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_orders = [];
}

// Fetch Top Performing / Most Viewed Products
$top_viewed_products = [];
try {
    $stmt_top_prod = $pdo->prepare("SELECT * FROM tbl_product ORDER BY p_total_view DESC LIMIT 5");
    $stmt_top_prod->execute();
    $top_viewed_products = $stmt_top_prod->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $top_viewed_products = [];
}
?>

<div class="dashboard-wrapper">

    <!-- Content Header -->
    <section class="content-header" style="padding-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">Executive eCommerce Dashboard</h1>
                <p class="header-subtext">Real-time revenue, order fulfillment, and inventory analytics.</p>
            </div>
            <div style="display:flex; gap:8px;">
                <a href="product-add.php" class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i> Add Product
                </a>
                <a href="order.php" class="btn btn-primary btn-sm">
                    <i class="fa fa-shopping-cart"></i> Manage Orders
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="content">

        <!-- Quick Action Hub -->
        <div class="quick-hub-bar">
            <a href="reports.php" class="quick-hub-link" style="color:#2563eb; font-weight:700;">
                <i class="fa fa-bar-chart" style="color:#2563eb;"></i> Sales Reports
            </a>
            <a href="coupon.php" class="quick-hub-link">
                <i class="fa fa-tag" style="color:#f59e0b;"></i> Coupons (<?php echo $total_active_coupons; ?>)
            </a>
            <a href="review.php" class="quick-hub-link">
                <i class="fa fa-star" style="color:#eab308;"></i> Reviews (<?php echo $total_reviews; ?>)
            </a>
            <a href="product-add.php" class="quick-hub-link">
                <i class="fa fa-plus-circle"></i> New Product
            </a>
            <a href="order.php" class="quick-hub-link">
                <i class="fa fa-sticky-note"></i> Orders (<?php echo $total_orders; ?>)
            </a>
            <a href="product.php" class="quick-hub-link">
                <i class="fa fa-shopping-bag"></i> Products (<?php echo $total_active_products; ?>)
            </a>
            <a href="customer.php" class="quick-hub-link">
                <i class="fa fa-users"></i> Customers (<?php echo $total_customers; ?>)
            </a>
            <a href="../index.php" target="_blank" class="quick-hub-link" style="color:#059669;">
                <i class="fa fa-external-link" style="color:#059669;"></i> Live Storefront
            </a>
        </div>

        <!-- KPI Hero Cards Grid -->
        <div class="kpi-grid">
            
            <!-- Revenue Card -->
            <div class="kpi-col">
                <div class="kpi-box green">
                    <div>
                        <div class="kpi-info-label">Gross Completed Sales (<?php echo get_current_currency(); ?>)</div>
                        <div class="kpi-info-val"><?php echo format_price($total_revenue); ?></div>
                        <div class="kpi-info-sub" style="display:flex; justify-content:space-between; align-items:center; gap:6px; flex-wrap:wrap;">
                            <span>
                                <i class="fa fa-check-circle" style="color:#10b981;"></i>
                                <?php echo $count_paid_orders; ?> paid transactions
                            </span>
                            <span style="font-size:11px; color:#64748b; font-weight:600;">
                                <?php echo get_current_currency() === 'KHR' ? '≈ $' . number_format($total_revenue, 2) . ' USD' : '≈ ' . format_price_khr($total_revenue); ?>
                            </span>
                        </div>
                    </div>
                    <div class="kpi-icon-circle">
                        <i class="fa <?php echo get_current_currency() === 'KHR' ? 'fa-money' : 'fa-dollar'; ?>"></i>
                    </div>
                </div>
            </div>

            <!-- Orders Card -->
            <div class="kpi-col">
                <div class="kpi-box blue">
                    <div>
                        <div class="kpi-info-label">Total Orders Placed</div>
                        <div class="kpi-info-val"><?php echo $total_orders; ?></div>
                        <div class="kpi-info-sub">
                            <?php if ($total_order_pending > 0): ?>
                                <span style="color:#b45309; font-weight:700;"><?php echo $total_order_pending; ?> pending</span> &bull; 
                            <?php endif; ?>
                            <span><?php echo $count_paid_orders; ?> completed</span>
                        </div>
                    </div>
                    <div class="kpi-icon-circle">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <!-- Inventory Stock Card -->
            <div class="kpi-col">
                <div class="kpi-box <?php echo ($total_low_stock > 0) ? 'orange' : 'blue'; ?>">
                    <div>
                        <div class="kpi-info-label">Active Inventory</div>
                        <div class="kpi-info-val"><?php echo $total_active_products; ?> Items</div>
                        <div class="kpi-info-sub">
                            <?php if ($total_low_stock > 0): ?>
                                <span style="color:#dc2626; font-weight:700;"><i class="fa fa-warning"></i> <?php echo $total_low_stock; ?> low stock items</span>
                            <?php else: ?>
                                <span style="color:#10b981;"><i class="fa fa-check"></i> Stock levels healthy</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="kpi-icon-circle">
                        <i class="fa fa-cube"></i>
                    </div>
                </div>
            </div>

            <!-- Customer Base Card -->
            <div class="kpi-col">
                <div class="kpi-box purple">
                    <div>
                        <div class="kpi-info-label">Customer Base</div>
                        <div class="kpi-info-val"><?php echo $total_customers; ?> Active</div>
                        <div class="kpi-info-sub">
                            <i class="fa fa-envelope-o"></i>
                            <span><?php echo $total_subscriber; ?> subscribers</span>
                        </div>
                    </div>
                    <div class="kpi-icon-circle">
                        <i class="fa fa-users"></i>
                    </div>
                </div>
            </div>

        </div>

        <!-- Main Dashboard Split: Recent Orders (Left) & Inventory / Top Products (Right) -->
        <div class="row">

            <!-- Left Column: Recent Orders -->
            <div class="col-lg-8 col-md-12">
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3 class="dash-card-title">
                            <i class="fa fa-history" style="color: #2563eb;"></i> Recent Orders & Quick Status
                        </h3>
                        <a href="order.php" class="btn btn-default btn-xs">
                            View All Orders &raquo;
                        </a>
                    </div>
                    <div class="dash-card-body table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th style="width: 110px;">Order #</th>
                                    <th>Customer</th>
                                    <th style="width: 90px;">Total</th>
                                    <th style="width: 100px;">Method</th>
                                    <th style="width: 100px;">Payment</th>
                                    <th style="width: 100px;">Shipping</th>
                                    <th style="width: 80px; text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center" style="padding: 30px; color: #94a3b8;">
                                            No customer orders placed yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_orders as $o): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: #0f172a;">#<?php echo htmlspecialchars($o['payment_id']); ?></strong>
                                                <div style="font-size: 11px; color: #64748b;"><?php echo date('M d, Y', strtotime($o['payment_date'])); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                                                <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($o['customer_email']); ?></div>
                                            </td>
                                            <td>
                                                <strong style="color: #0f172a; font-size: 14px;"><?php echo format_price($o['paid_amount']); ?></strong>
                                                <div style="font-size: 11px; color: #64748b;">
                                                    <?php echo get_current_currency() === 'KHR' ? '≈ $' . number_format((float)$o['paid_amount'], 2) : '≈ ' . format_price_khr($o['paid_amount']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="dash-badge-pill default"><?php echo htmlspecialchars($o['payment_method']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($o['payment_status'] === 'Completed'): ?>
                                                    <a href="order-change-status.php?id=<?php echo $o['id']; ?>&task=Pending" class="dash-badge-pill success" title="Click to toggle to Pending">
                                                        <i class="fa fa-check"></i> Paid
                                                    </a>
                                                <?php else: ?>
                                                    <a href="order-change-status.php?id=<?php echo $o['id']; ?>&task=Completed" class="dash-badge-pill warning" title="Click to mark as Paid">
                                                        <i class="fa fa-clock-o"></i> Pending
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($o['shipping_status'] === 'Completed'): ?>
                                                    <a href="shipping-change-status.php?id=<?php echo $o['id']; ?>&task=Pending" class="dash-badge-pill success" title="Click to toggle to Pending">
                                                        <i class="fa fa-truck"></i> Shipped
                                                    </a>
                                                <?php else: ?>
                                                    <a href="shipping-change-status.php?id=<?php echo $o['id']; ?>&task=Completed" class="dash-badge-pill info" title="Click to mark as Shipped">
                                                        <i class="fa fa-clock-o"></i> Pending
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:right;">
                                                <a href="order.php" class="btn btn-default btn-xs">
                                                    Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Low Stock Alerts & Top Products -->
            <div class="col-lg-4 col-md-12">
                
                <!-- Low Stock Alert Card -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3 class="dash-card-title">
                            <i class="fa fa-warning" style="color: #f59e0b;"></i> Low Stock Alerts
                        </h3>
                        <span class="dash-badge-pill danger"><?php echo count($low_stock_products); ?> items</span>
                    </div>
                    <div class="dash-card-body table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th style="width: 80px;">Stock</th>
                                    <th style="width: 70px; text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($low_stock_products)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center" style="padding: 25px; color:#10b981;">
                                            <i class="fa fa-check-circle" style="font-size:22px; margin-bottom:4px; display:inline-block;"></i><br>
                                            All inventory levels healthy!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($low_stock_products as $lp): ?>
                                        <tr>
                                            <td>
                                                <div style="display:flex; align-items:center; gap:10px;">
                                                    <img src="../assets/uploads/<?php echo htmlspecialchars($lp['p_featured_photo']); ?>" 
                                                         alt="<?php echo htmlspecialchars($lp['p_name']); ?>" 
                                                         width="44" height="44" 
                                                         class="thumb-44">
                                                    <div style="min-width:0; overflow:hidden;">
                                                        <a href="product-edit.php?id=<?php echo $lp['p_id']; ?>" 
                                                           style="font-weight:600; color:#0f172a; font-size:12px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                            <?php echo htmlspecialchars($lp['p_name']); ?>
                                                        </a>
                                                        <span style="font-size:11px; color:#64748b;">$<?php echo number_format((float)$lp['p_current_price'], 2); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($lp['p_qty'] <= 10): ?>
                                                    <span class="dash-badge-pill danger"><?php echo $lp['p_qty']; ?> left</span>
                                                <?php else: ?>
                                                    <span class="dash-badge-pill warning"><?php echo $lp['p_qty']; ?> left</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:right;">
                                                <a href="product-edit.php?id=<?php echo $lp['p_id']; ?>" class="btn btn-warning btn-xs">
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Most Viewed Products -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3 class="dash-card-title">
                            <i class="fa fa-line-chart" style="color: #10b981;"></i> Most Viewed Products
                        </h3>
                    </div>
                    <div class="dash-card-body table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th style="width: 70px; text-align:right;">Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_viewed_products as $tp): ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <img src="../assets/uploads/<?php echo htmlspecialchars($tp['p_featured_photo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($tp['p_name']); ?>" 
                                                     width="44" height="44" 
                                                     class="thumb-44">
                                                <div style="min-width:0; overflow:hidden;">
                                                    <a href="../product.php?id=<?php echo $tp['p_id']; ?>" target="_blank" 
                                                       style="font-weight:600; color:#0f172a; font-size:12px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                        <?php echo htmlspecialchars($tp['p_name']); ?>
                                                    </a>
                                                    <span style="font-size:11px; color:#10b981; font-weight:700;">$<?php echo number_format((float)$tp['p_current_price'], 2); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align:right;">
                                            <span class="dash-badge-pill default"><?php echo (int)$tp['p_total_view']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>

        <!-- Platform Configuration Summary -->
        <div style="margin-top: 10px; margin-bottom: 25px;">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 12px;">
                <i class="fa fa-cubes"></i> Platform Catalog & Shipping Metrics
            </div>
            <div class="row">
                <div class="col-lg-2 col-sm-4 col-xs-6">
                    <a href="coupon.php" style="text-decoration:none;">
                        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:15px; display:flex; align-items:center; gap:10px; margin-bottom:15px; transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                            <div style="width:36px; height:36px; border-radius:8px; background:#fffbeb; color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:16px;">
                                <i class="fa fa-tag"></i>
                            </div>
                            <div>
                                <div style="font-size:18px; font-weight:800; color:#0f172a; line-height:1;"><?php echo $total_active_coupons; ?></div>
                                <div style="font-size:11px; color:#64748b; margin-top:3px;">Active Coupons</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-sm-4 col-xs-6">
                    <a href="review.php" style="text-decoration:none;">
                        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:15px; display:flex; align-items:center; gap:10px; margin-bottom:15px; transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                            <div style="width:36px; height:36px; border-radius:8px; background:#fefce8; color:#eab308; display:flex; align-items:center; justify-content:center; font-size:16px;">
                                <i class="fa fa-star"></i>
                            </div>
                            <div>
                                <div style="font-size:18px; font-weight:800; color:#0f172a; line-height:1;"><?php echo $total_reviews; ?></div>
                                <div style="font-size:11px; color:#64748b; margin-top:3px;">Product Reviews</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-sm-4 col-xs-6">
                    <a href="reports.php" style="text-decoration:none;">
                        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:15px; display:flex; align-items:center; gap:10px; margin-bottom:15px; transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                            <div style="width:36px; height:36px; border-radius:8px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:16px;">
                                <i class="fa fa-bar-chart"></i>
                            </div>
                            <div>
                                <div style="font-size:18px; font-weight:800; color:#0f172a; line-height:1;"><i class="fa fa-check"></i></div>
                                <div style="font-size:11px; color:#64748b; margin-top:3px;">Sales Analytics</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-sm-4 col-xs-6">
                    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:15px; display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:16px;">
                            <i class="fa fa-sitemap"></i>
                        </div>
                        <div>
                            <div style="font-size:18px; font-weight:800; color:#0f172a; line-height:1;"><?php echo $total_top_category; ?></div>
                            <div style="font-size:11px; color:#64748b; margin-top:3px;">Top Categories</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-sm-4 col-xs-6">
                    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:15px; display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:#f0fdf4; color:#10b981; display:flex; align-items:center; justify-content:center; font-size:16px;">
                            <i class="fa fa-folder-open-o"></i>
                        </div>
                        <div>
                            <div style="font-size:18px; font-weight:800; color:#0f172a; line-height:1;"><?php echo $total_mid_category; ?></div>
                            <div style="font-size:11px; color:#64748b; margin-top:3px;">Mid Categories</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-sm-4 col-xs-6">
                    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:15px; display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:#f5f3ff; color:#8b5cf6; display:flex; align-items:center; justify-content:center; font-size:16px;">
                            <i class="fa fa-truck"></i>
                        </div>
                        <div>
                            <div style="font-size:18px; font-weight:800; color:#0f172a; line-height:1;"><?php echo $available_shipping; ?></div>
                            <div style="font-size:11px; color:#64748b; margin-top:3px;">Shipping Zones</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>

</div>

<?php require_once('footer.php'); ?>
