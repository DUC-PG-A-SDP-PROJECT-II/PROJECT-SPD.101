<?php require_once('header.php'); ?>

<?php
// Check if the customer is logged in or not
if(!isset($_SESSION['customer'])) {
    header('location: '.BASE_URL.'logout.php');
    exit;
} else {
    // If customer is logged in, but admin make him inactive, then force logout this user.
    $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=? AND cust_status=?");
    $statement->execute(array($_SESSION['customer']['cust_id'],0));
    $total = $statement->rowCount();
    if($total) {
        header('location: '.BASE_URL.'logout.php');
        exit;
    }
}

// Fetch current customer details for invoices
$stmt_cust = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=?");
$stmt_cust->execute([$_SESSION['customer']['cust_id']]);
$customer_data = $stmt_cust->fetch(PDO::FETCH_ASSOC);

// Store info
$stmt_set = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$stmt_set->execute();
$settings_data = $stmt_set->fetch(PDO::FETCH_ASSOC);
?>

<div class="page" style="padding-top: 30px; padding-bottom: 60px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <?php require_once('customer-sidebar.php'); ?>
            </div>
            <div class="col-md-12" style="margin-top: 20px;">
                <div class="user-content">

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h2 style="font-size: 24px; font-weight: 700; margin: 0 0 5px; color: #111;">
                                <i class="fa fa-shopping-bag" style="color: #febd69;"></i> Your Orders & Tracking
                            </h2>
                            <p style="color: #666; margin: 0; font-size: 13px;">Review past orders, track real-time delivery status, and print invoices.</p>
                        </div>
                        <a href="index.php" class="btn btn-warning" style="background:#ffd814; border-color:#fcd200; color:#111; font-weight:600; border-radius: 20px; padding: 7px 18px;">
                            <i class="fa fa-search"></i> Continue Shopping
                        </a>
                    </div>

                    <?php
                    // Pagination
                    $statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE customer_email=? ORDER BY id DESC");
                    $statement->execute(array($_SESSION['customer']['cust_email']));
                    $total_orders = $statement->rowCount();

                    $targetpage = BASE_URL.'customer-order.php';
                    $limit = 6;
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    if ($page < 1) $page = 1;
                    $total_pages = ceil($total_orders / $limit);
                    if ($total_pages < 1) $total_pages = 1;
                    if ($page > $total_pages) $page = $total_pages;
                    $start = ($page - 1) * $limit;

                    $statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE customer_email=? ORDER BY id DESC LIMIT $start, $limit");
                    $statement->execute(array($_SESSION['customer']['cust_email']));
                    $orders = $statement->fetchAll(PDO::FETCH_ASSOC);

                    if ($total_orders == 0):
                    ?>
                        <div class="catalog-empty-state">
                            <div class="catalog-empty-icon"><i class="fa fa-cube"></i></div>
                            <h3>No Orders Placed Yet</h3>
                            <p>You haven't placed any orders with us yet. Start exploring our catalog to find amazing deals!</p>
                            <a href="index.php" class="btn-browse-all">
                                <i class="fa fa-shopping-cart"></i> Start Shopping
                            </a>
                        </div>
                    <?php
                    else:
                        foreach ($orders as $row):
                            $payment_id = $row['payment_id'];
                            $payment_status = $row['payment_status'];
                            $shipping_status = $row['shipping_status'];
                            $paid_amount = $row['paid_amount'];
                            $payment_date = $row['payment_date'];
                            $txnid = $row['txnid'];
                            $payment_method = $row['payment_method'];

                            // Determine status & tracker steps
                            $is_paid = ($payment_status === 'Completed');
                            $is_shipped = ($shipping_status === 'Completed');
                            $is_delivered = ($shipping_status === 'Completed');

                            // Fetch items in this order
                            $stmt_items = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
                            $stmt_items->execute([$payment_id]);
                            $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                        <div class="modern-order-card">
                            <!-- Order Card Top Bar -->
                            <div class="order-card-header">
                                <div class="order-header-left">
                                    <div class="order-header-item">
                                        <span class="order-header-label">Order Placed</span>
                                        <span class="order-header-val"><?php echo date('M d, Y', strtotime($payment_date)); ?></span>
                                    </div>
                                    <div class="order-header-item">
                                        <span class="order-header-label">Total</span>
                                        <span class="order-header-val"><?php echo format_price($paid_amount); ?></span>
                                    </div>
                                    <div class="order-header-item">
                                        <span class="order-header-label">Ship To</span>
                                        <span class="order-header-val"><?php echo htmlspecialchars($row['customer_name']); ?></span>
                                    </div>
                                </div>

                                <div class="order-header-right">
                                    <div style="font-size: 11px; color: #565959; margin-bottom: 4px;">
                                        ORDER # <strong style="color:#111;"><?php echo htmlspecialchars($payment_id); ?></strong>
                                    </div>
                                    <button type="button" class="btn-view-invoice" data-toggle="modal" data-target="#invoiceModal<?php echo $row['id']; ?>">
                                        <i class="fa fa-file-text-o"></i> View Invoice
                                    </button>
                                </div>
                            </div>

                            <!-- Order Card Body -->
                            <div class="order-card-body">
                                
                                <!-- Delivery Status Headline -->
                                <div class="order-status-banner">
                                    <div>
                                        <?php if ($is_delivered): ?>
                                            <div class="order-delivery-headline">
                                                <i class="fa fa-check-circle" style="color: #28a745; font-size: 18px;"></i> Delivered / Completed
                                            </div>
                                        <?php elseif ($is_paid): ?>
                                            <div class="order-delivery-headline">
                                                <i class="fa fa-truck" style="color: #007185; font-size: 18px;"></i> Payment Confirmed &bull; Preparing Shipment
                                            </div>
                                        <?php else: ?>
                                            <div class="order-delivery-headline pending">
                                                <i class="fa fa-clock-o" style="font-size: 18px;"></i> Order Received &bull; Awaiting Payment Confirmation
                                            </div>
                                        <?php endif; ?>
                                        <span style="font-size: 12px; color: #666; margin-top: 3px; display: block;">
                                            Payment via <strong><?php echo htmlspecialchars($payment_method); ?></strong>
                                            <?php if (!empty($txnid)): ?>
                                                &bull; Txn: <code><?php echo htmlspecialchars($txnid); ?></code>
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <div class="order-status-badges">
                                        <span class="order-badge <?php echo $is_paid ? 'badge-success' : 'badge-warning'; ?>">
                                            Payment: <?php echo $payment_status; ?>
                                        </span>
                                        <?php if(!$is_paid && $payment_method === 'Bakong KHQR'): ?>
                                            <a href="khqr-pay.php?payment_id=<?php echo urlencode($payment_id); ?>" class="order-badge" style="background:#e1251b; color:#ffffff; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                                <i class="fa fa-qrcode"></i> Pay with KHQR
                                            </a>
                                        <?php endif; ?>
                                        <span class="order-badge <?php echo $is_shipped ? 'badge-success' : 'badge-info'; ?>">
                                            Shipping: <?php echo $shipping_status; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Amazon-Style 4-Step Progress Tracker -->
                                <div class="order-tracking-steps">
                                    <!-- Step 1: Placed -->
                                    <div class="tracking-step completed">
                                        <div class="tracking-step-dot"><i class="fa fa-check"></i></div>
                                        <div class="tracking-step-label">Order Placed</div>
                                    </div>
                                    <!-- Step 2: Paid -->
                                    <div class="tracking-step <?php echo $is_paid ? 'completed' : 'active'; ?>">
                                        <div class="tracking-step-dot">
                                            <i class="fa <?php echo $is_paid ? 'fa-check' : 'fa-credit-card'; ?>"></i>
                                        </div>
                                        <div class="tracking-step-label">Payment <?php echo $is_paid ? 'Confirmed' : 'Pending'; ?></div>
                                    </div>
                                    <!-- Step 3: Shipped -->
                                    <div class="tracking-step <?php echo $is_shipped ? 'completed' : ($is_paid ? 'active' : ''); ?>">
                                        <div class="tracking-step-dot">
                                            <i class="fa <?php echo $is_shipped ? 'fa-check' : 'fa-truck'; ?>"></i>
                                        </div>
                                        <div class="tracking-step-label">Shipped</div>
                                    </div>
                                    <!-- Step 4: Delivered -->
                                    <div class="tracking-step <?php echo $is_delivered ? 'completed' : ''; ?>">
                                        <div class="tracking-step-dot">
                                            <i class="fa <?php echo $is_delivered ? 'fa-check' : 'fa-home'; ?>"></i>
                                        </div>
                                        <div class="tracking-step-label">Delivered</div>
                                    </div>
                                </div>

                                <!-- Products List in this Order -->
                                <div style="margin-top: 25px;">
                                    <?php
                                    foreach ($order_items as $item):
                                        $prod_id = (int)$item['product_id'];
                                        $p_name = $item['product_name'];
                                        $size = $item['size'];
                                        $color = $item['color'];
                                        $qty = (int)$item['quantity'];
                                        $unit_price = (float)$item['unit_price'];
                                        $item_subtotal = $qty * $unit_price;

                                        // Fetch product image
                                        $stmt_p = $pdo->prepare("SELECT p_featured_photo FROM tbl_product WHERE p_id=?");
                                        $stmt_p->execute([$prod_id]);
                                        $p_row = $stmt_p->fetch(PDO::FETCH_ASSOC);
                                        $item_photo = $p_row['p_featured_photo'] ?? '';
                                    ?>
                                        <div class="order-item-row">
                                            <div class="order-item-thumb">
                                                <?php if (!empty($item_photo)): ?>
                                                    <a href="product.php?id=<?php echo $prod_id; ?>">
                                                        <img src="assets/uploads/<?php echo htmlspecialchars($item_photo); ?>" alt="<?php echo htmlspecialchars($p_name); ?>">
                                                    </a>
                                                <?php else: ?>
                                                    <i class="fa fa-image" style="font-size: 24px; color: #ccc;"></i>
                                                <?php endif; ?>
                                            </div>

                                            <div class="order-item-details">
                                                <h4 class="order-item-title">
                                                    <a href="product.php?id=<?php echo $prod_id; ?>"><?php echo htmlspecialchars($p_name); ?></a>
                                                </h4>
                                                <div class="order-item-meta">
                                                    <?php if (!empty($size)): ?>
                                                        <span>Size: <strong><?php echo htmlspecialchars($size); ?></strong></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($color)): ?>
                                                        <span>Color: <strong><?php echo htmlspecialchars($color); ?></strong></span>
                                                    <?php endif; ?>
                                                    <span>Qty: <strong><?php echo $qty; ?></strong></span>
                                                    <span>Unit Price: <strong>$<?php echo number_format($unit_price, 2); ?></strong></span>
                                                </div>
                                                <div class="order-item-price">
                                                    Subtotal: $<?php echo number_format($item_subtotal, 2); ?>
                                                </div>
                                            </div>

                                            <div class="order-item-actions">
                                                <a href="product.php?id=<?php echo $prod_id; ?>" class="btn-buy-again">
                                                    <i class="fa fa-repeat"></i> Buy Again
                                                </a>
                                                <a href="product.php?id=<?php echo $prod_id; ?>" class="btn btn-default btn-xs" style="border-radius: 20px; font-weight: 600;">
                                                    <i class="fa fa-eye"></i> View Item
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                            </div>
                        </div>

                        <!-- Invoice Modal for this Order -->
                        <div class="modal fade" id="invoiceModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content" style="border-radius: 8px; overflow: hidden;">
                                    <div class="modal-header" style="background:#131921; color:#fff; border-bottom:none;">
                                        <button type="button" class="close" data-dismiss="modal" style="color:#fff; opacity:0.8;">&times;</button>
                                        <h4 class="modal-title" style="font-weight:700;">
                                            <i class="fa fa-file-text-o" style="color: #febd69;"></i> Official Order Invoice #<?php echo htmlspecialchars($payment_id); ?>
                                        </h4>
                                    </div>
                                    <div class="modal-body" style="padding: 25px;">
                                        
                                        <!-- Printable Area -->
                                        <div id="invoiceContent<?php echo $row['id']; ?>" class="invoice-container invoice-printable">
                                            
                                            <!-- Invoice Header -->
                                            <div class="invoice-header">
                                                <div>
                                                    <div class="invoice-logo">
                                                        <?php if (!empty($settings_data['logo'])): ?>
                                                            <img src="assets/uploads/<?php echo htmlspecialchars($settings_data['logo']); ?>" alt="Store Logo" style="max-height: 48px;">
                                                        <?php else: ?>
                                                            eCommerce Store
                                                        <?php endif; ?>
                                                    </div>
                                                    <p style="margin-top: 8px; font-size: 12px; color: #666;">
                                                        Email: <?php echo htmlspecialchars($settings_data['contact_email'] ?? ''); ?><br>
                                                        Phone: <?php echo htmlspecialchars($settings_data['contact_phone'] ?? ''); ?>
                                                    </p>
                                                </div>

                                                <div class="invoice-meta-top">
                                                    <h3>INVOICE</h3>
                                                    <div><strong>Invoice #:</strong> INV-<?php echo htmlspecialchars($payment_id); ?></div>
                                                    <div><strong>Order Date:</strong> <?php echo date('F d, Y - h:i A', strtotime($payment_date)); ?></div>
                                                    <div><strong>Payment Status:</strong> <span style="color:#28a745; font-weight:700;"><?php echo htmlspecialchars($payment_status); ?></span></div>
                                                    <div><strong>Shipping Status:</strong> <strong><?php echo htmlspecialchars($shipping_status); ?></strong></div>
                                                </div>
                                            </div>

                                            <!-- Addresses Section -->
                                            <div class="invoice-addresses">
                                                <div style="flex:1;">
                                                    <h5>Billed To:</h5>
                                                    <strong><?php echo htmlspecialchars($customer_data['cust_b_name'] ?: $customer_data['cust_name']); ?></strong><br>
                                                    <?php echo htmlspecialchars($customer_data['cust_b_address'] ?: $customer_data['cust_address']); ?><br>
                                                    <?php echo htmlspecialchars($customer_data['cust_b_city'] ?: $customer_data['cust_city']); ?>, 
                                                    <?php echo htmlspecialchars($customer_data['cust_b_state'] ?: $customer_data['cust_state']); ?> 
                                                    <?php echo htmlspecialchars($customer_data['cust_b_zip'] ?: $customer_data['cust_zip']); ?><br>
                                                    Email: <?php echo htmlspecialchars($customer_data['cust_email']); ?><br>
                                                    Phone: <?php echo htmlspecialchars($customer_data['cust_b_phone'] ?: $customer_data['cust_phone']); ?>
                                                </div>

                                                <div style="flex:1; text-align: right;">
                                                    <h5>Shipped To:</h5>
                                                    <strong><?php echo htmlspecialchars($customer_data['cust_s_name'] ?: $customer_data['cust_name']); ?></strong><br>
                                                    <?php echo htmlspecialchars($customer_data['cust_s_address'] ?: $customer_data['cust_address']); ?><br>
                                                    <?php echo htmlspecialchars($customer_data['cust_s_city'] ?: $customer_data['cust_city']); ?>, 
                                                    <?php echo htmlspecialchars($customer_data['cust_s_state'] ?: $customer_data['cust_state']); ?> 
                                                    <?php echo htmlspecialchars($customer_data['cust_s_zip'] ?: $customer_data['cust_zip']); ?><br>
                                                    Phone: <?php echo htmlspecialchars($customer_data['cust_s_phone'] ?: $customer_data['cust_phone']); ?>
                                                </div>
                                            </div>

                                            <!-- Order Items Table -->
                                            <table class="invoice-table">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Product Description</th>
                                                        <th>Attributes</th>
                                                        <th style="text-align:center;">Qty</th>
                                                        <th style="text-align:right;">Unit Price</th>
                                                        <th style="text-align:right;">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $item_idx = 0;
                                                    $calculated_subtotal = 0;
                                                    foreach ($order_items as $inv_item):
                                                        $item_idx++;
                                                        $sub = (float)$inv_item['unit_price'] * (int)$inv_item['quantity'];
                                                        $calculated_subtotal += $sub;
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $item_idx; ?></td>
                                                            <td><strong><?php echo htmlspecialchars($inv_item['product_name']); ?></strong></td>
                                                            <td>
                                                                Size: <?php echo htmlspecialchars($inv_item['size']); ?> &bull; 
                                                                Color: <?php echo htmlspecialchars($inv_item['color']); ?>
                                                            </td>
                                                            <td style="text-align:center;"><?php echo (int)$inv_item['quantity']; ?></td>
                                                            <td style="text-align:right;"><?php echo format_price((float)$inv_item['unit_price']); ?></td>
                                                            <td style="text-align:right;"><strong><?php echo format_price($sub); ?></strong></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>

                                            <!-- Summary -->
                                            <div class="invoice-summary">
                                                <table class="invoice-summary-table">
                                                    <tr>
                                                        <td>Items Subtotal:</td>
                                                        <td style="text-align:right;"><?php echo format_price($calculated_subtotal); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Payment Method:</td>
                                                        <td style="text-align:right;"><strong><?php echo htmlspecialchars($payment_method); ?></strong></td>
                                                    </tr>
                                                    <tr class="total">
                                                        <td>Grand Total:</td>
                                                        <td style="text-align:right;"><?php echo format_price($paid_amount); ?></td>
                                                    </tr>
                                                </table>
                                            </div>

                                            <div style="margin-top: 30px; padding-top: 15px; border-top: 1px dashed #ddd; font-size: 11px; color: #888; text-align: center;">
                                                Thank you for your purchase! If you have questions about your order, contact our support team.
                                            </div>

                                        </div>

                                    </div>
                                    <div class="modal-footer" style="background:#f8f9fa;">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" onclick="printInvoice('invoiceContent<?php echo $row['id']; ?>')">
                                            <i class="fa fa-print"></i> Print Invoice
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php
                        endforeach;
                    ?>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="text-center" style="margin-top: 25px;">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li><a href="<?php echo $targetpage; ?>?page=<?php echo $page - 1; ?>">&laquo; Prev</a></li>
                                    <?php else: ?>
                                        <li class="disabled"><span>&laquo; Prev</span></li>
                                    <?php endif; ?>

                                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                        <li class="<?php echo ($p == $page) ? 'active' : ''; ?>">
                                            <a href="<?php echo $targetpage; ?>?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li><a href="<?php echo $targetpage; ?>?page=<?php echo $page + 1; ?>">Next &raquo;</a></li>
                                    <?php else: ?>
                                        <li class="disabled"><span>Next &raquo;</span></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printInvoice(elementId) {
    var printContent = document.getElementById(elementId).innerHTML;
    var printWindow = window.open('', '_blank', 'height=750,width=900');
    printWindow.document.write('<!DOCTYPE html><html><head><title>Print Order Invoice</title>');
    printWindow.document.write('<link rel="stylesheet" href="assets/css/bootstrap.min.css">');
    printWindow.document.write('<link rel="stylesheet" href="assets/css/modern-ecommerce.css">');
    printWindow.document.write('<style>');
    printWindow.document.write('body { background: #fff !important; padding: 25px !important; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }');
    printWindow.document.write('.invoice-container { padding: 0 !important; border: none !important; box-shadow: none !important; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 600);
}
</script>

<?php require_once('footer.php'); ?>
