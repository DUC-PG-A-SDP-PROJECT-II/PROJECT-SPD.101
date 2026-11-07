<?php require_once('header.php'); ?>

<?php
// Check if the customer is logged in or not
if(!isset($_SESSION['customer'])) {
    header('location: '.BASE_URL.'login.php');
    exit;
} else {
    // If customer is logged in, but admin made them inactive, force logout
    $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=? AND cust_status=?");
    $statement->execute(array($_SESSION['customer']['cust_id'], 0));
    $total = $statement->rowCount();
    if($total) {
        header('location: '.BASE_URL.'logout.php');
        exit;
    }
}

// Fetch the latest customer details from the database
$statement = $pdo->prepare("SELECT c.*, co.country_name 
                            FROM tbl_customer c 
                            LEFT JOIN tbl_country co ON c.cust_country = co.country_id 
                            WHERE c.cust_id = ?");
$statement->execute(array($_SESSION['customer']['cust_id']));
$customer = $statement->fetch(PDO::FETCH_ASSOC);

if(!$customer) {
    header('location: '.BASE_URL.'logout.php');
    exit;
}
$_SESSION['customer'] = $customer; // Synchronize session with database

// Country names for billing and shipping
$cust_b_country_name = '';
if(!empty($customer['cust_b_country'])) {
    $stmt_b = $pdo->prepare("SELECT country_name FROM tbl_country WHERE country_id = ?");
    $stmt_b->execute(array($customer['cust_b_country']));
    $row_b = $stmt_b->fetch(PDO::FETCH_ASSOC);
    if($row_b) {
        $cust_b_country_name = $row_b['country_name'];
    }
}

$cust_s_country_name = '';
if(!empty($customer['cust_s_country'])) {
    $stmt_s = $pdo->prepare("SELECT country_name FROM tbl_country WHERE country_id = ?");
    $stmt_s->execute(array($customer['cust_s_country']));
    $row_s = $stmt_s->fetch(PDO::FETCH_ASSOC);
    if($row_s) {
        $cust_s_country_name = $row_s['country_name'];
    }
}
?>

<div class="page">
    <div class="container">
        <div class="row">            
            <div class="col-md-12"> 
                <?php require_once('customer-sidebar.php'); ?>
            </div>
            
            <div class="col-md-12">
                <div class="user-content">

                    <?php if(isset($_SESSION['registration_success'])): ?>
                        <div class="alert alert-success alert-dismissible" role="alert" style="font-size: 15px; border-left: 5px solid #3c763d; margin-bottom: 25px;">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <i class="fa fa-check-circle fa-lg" style="margin-right: 8px;"></i>
                            <strong>Registration Completed Successfully!</strong> <?php echo $_SESSION['registration_success']; unset($_SESSION['registration_success']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Banner Header -->
                    <div style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; margin-bottom: 30px; box-shadow: 0 4px 16px -2px rgba(15,23,42,0.05);">
                        <div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
                            <div class="col-sm-2 text-center" style="margin-bottom: 15px;">
                                <?php if(!empty($customer['cust_photo']) && file_exists('assets/uploads/'.$customer['cust_photo'])): ?>
                                    <img src="assets/uploads/<?php echo htmlspecialchars($customer['cust_photo']); ?>" alt="Profile Photo" style="width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #2563eb; box-shadow: 0 6px 16px rgba(37,99,235,0.25);">
                                <?php else: ?>
                                    <div style="width: 90px; height: 90px; line-height: 90px; border-radius: 50%; background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #2563eb; font-size: 40px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display:flex; align-items:center; justify-content:center;">
                                        <i class="fa fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-sm-7" style="margin-bottom: 15px;">
                                <h2 style="margin-top: 0; margin-bottom: 8px; font-weight: 800; font-size: 24px; color: #0f172a;">
                                    <?php echo htmlspecialchars($customer['cust_name']); ?>
                                </h2>
                                <p style="margin-bottom: 8px; color: #475569; font-size: 14px; font-weight: 500;">
                                    <i class="fa fa-envelope" style="margin-right: 6px; color: #2563eb;"></i> <?php echo htmlspecialchars($customer['cust_email']); ?>
                                    &nbsp;&nbsp;|&nbsp;&nbsp;
                                    <i class="fa fa-phone" style="margin-right: 6px; color: #2563eb;"></i> <?php echo htmlspecialchars($customer['cust_phone']); ?>
                                </p>
                                <p style="margin-bottom: 0; color: #64748b; font-size: 13px;">
                                    <span class="label label-success" style="font-size: 12px; padding: 5px 10px; font-weight: 600; border-radius: 6px; background-color: #10b981;">
                                        <i class="fa fa-check-circle"></i> Active Customer
                                    </span>
                                    <?php if(!empty($customer['cust_datetime'])): ?>
                                        <span style="margin-left: 15px;">
                                            <i class="fa fa-calendar" style="margin-right: 4px; color: #94a3b8;"></i> 
                                            Registered: <?php echo htmlspecialchars($customer['cust_datetime']); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-sm-3 text-right" style="margin-bottom: 15px;">
                                <a href="customer-profile-update.php" class="btn" style="background:#2563eb; color:#fff; font-weight:700; border-radius:10px; padding:10px 18px; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                                    <i class="fa fa-pencil"></i> <?php echo LANG_VALUE_117; ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Information Sections -->
                    <div class="row">
                        <!-- Primary Registration Information -->
                        <div class="col-md-6">
                            <div class="panel panel-default" style="border-radius: 4px; border: 1px solid #e1e1e1;">
                                <div class="panel-heading" style="background: #f7f7f7; font-weight: bold; font-size: 16px; border-bottom: 1px solid #e1e1e1;">
                                    <i class="fa fa-id-card-o" style="color: #e4144d; margin-right: 8px;"></i> Customer Profile Details
                                </div>
                                <div class="panel-body" style="padding: 0;">
                                    <table class="table table-striped table-hover bill-address" style="margin-bottom: 0;">
                                        <tr>
                                            <td style="width: 38%; font-weight: 600; color: #444;">Profile Photo</td>
                                            <td>
                                                <?php if(!empty($customer['cust_photo']) && file_exists('assets/uploads/'.$customer['cust_photo'])): ?>
                                                    <img src="assets/uploads/<?php echo htmlspecialchars($customer['cust_photo']); ?>" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd; vertical-align: middle; margin-right: 10px;">
                                                    <a href="customer-profile-update.php" class="btn btn-xs btn-default"><i class="fa fa-camera"></i> Change</a>
                                                <?php else: ?>
                                                    <span class="text-muted"><em>No photo uploaded</em></span> &nbsp;
                                                    <a href="customer-profile-update.php" class="btn btn-xs btn-primary"><i class="fa fa-upload"></i> Upload Photo</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 38%; font-weight: 600; color: #444;"><?php echo LANG_VALUE_102; ?></td>
                                            <td><?php echo htmlspecialchars($customer['cust_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_103; ?></td>
                                            <td>
                                                <?php 
                                                echo !empty($customer['cust_cname']) 
                                                    ? htmlspecialchars($customer['cust_cname']) 
                                                    : '<span class="text-muted"><em>Not specified</em></span>'; 
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_94; ?></td>
                                            <td><?php echo htmlspecialchars($customer['cust_email']); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_104; ?></td>
                                            <td><?php echo htmlspecialchars($customer['cust_phone']); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_106; ?></td>
                                            <td><?php echo !empty($customer['country_name']) ? htmlspecialchars($customer['country_name']) : '<span class="text-muted"><em>Not specified</em></span>'; ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_105; ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($customer['cust_address'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_107; ?></td>
                                            <td><?php echo htmlspecialchars($customer['cust_city']); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_108; ?></td>
                                            <td><?php echo htmlspecialchars($customer['cust_state']); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_109; ?></td>
                                            <td><?php echo htmlspecialchars($customer['cust_zip']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="panel-footer text-right" style="background: #fafafa; border-top: 1px solid #eee;">
                                    <a href="customer-profile-update.php" class="btn btn-xs btn-primary">
                                        <i class="fa fa-pencil"></i> Edit Personal Info
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Billing & Shipping Information -->
                        <div class="col-md-6">
                            <!-- Billing Address Summary -->
                            <div class="panel panel-default" style="border-radius: 4px; border: 1px solid #e1e1e1; margin-bottom: 20px;">
                                <div class="panel-heading" style="background: #f7f7f7; font-weight: bold; font-size: 16px; border-bottom: 1px solid #e1e1e1;">
                                    <i class="fa fa-credit-card" style="color: #e4144d; margin-right: 8px;"></i> Billing Address
                                </div>
                                <div class="panel-body" style="padding: 0;">
                                    <?php if(!empty($customer['cust_b_name']) || !empty($customer['cust_b_address'])): ?>
                                        <table class="table table-striped table-hover bill-address" style="margin-bottom: 0;">
                                            <tr>
                                                <td style="width: 38%; font-weight: 600; color: #444;"><?php echo LANG_VALUE_102; ?></td>
                                                <td><?php echo htmlspecialchars($customer['cust_b_name']); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_103; ?></td>
                                                <td><?php echo !empty($customer['cust_b_cname']) ? htmlspecialchars($customer['cust_b_cname']) : '<span class="text-muted"><em>Not specified</em></span>'; ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_104; ?></td>
                                                <td><?php echo htmlspecialchars($customer['cust_b_phone']); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_106; ?></td>
                                                <td><?php echo !empty($cust_b_country_name) ? htmlspecialchars($cust_b_country_name) : '<span class="text-muted"><em>Not specified</em></span>'; ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_105; ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($customer['cust_b_address'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #444;">City, State, Zip</td>
                                                <td><?php echo htmlspecialchars($customer['cust_b_city'] . ', ' . $customer['cust_b_state'] . ' ' . $customer['cust_b_zip']); ?></td>
                                            </tr>
                                        </table>
                                    <?php else: ?>
                                        <div style="padding: 20px; text-align: center; color: #777;">
                                            <i class="fa fa-info-circle fa-2x" style="color: #bbb; margin-bottom: 10px; display: block;"></i>
                                            <p style="margin-bottom: 10px;">Billing address has not been provided yet.</p>
                                            <a href="customer-billing-shipping-update.php" class="btn btn-sm btn-danger">
                                                <i class="fa fa-plus"></i> Set Billing Address
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Shipping Address Summary -->
                            <div class="panel panel-default" style="border-radius: 4px; border: 1px solid #e1e1e1;">
                                <div class="panel-heading" style="background: #f7f7f7; font-weight: bold; font-size: 16px; border-bottom: 1px solid #e1e1e1;">
                                    <i class="fa fa-truck" style="color: #e4144d; margin-right: 8px;"></i> Shipping Address
                                </div>
                                <div class="panel-body" style="padding: 0;">
                                    <?php if(!empty($customer['cust_s_name']) || !empty($customer['cust_s_address'])): ?>
                                        <table class="table table-striped table-hover bill-address" style="margin-bottom: 0;">
                                            <tr>
                                                <td style="width: 38%; font-weight: 600; color: #444;"><?php echo LANG_VALUE_102; ?></td>
                                                <td><?php echo htmlspecialchars($customer['cust_s_name']); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_103; ?></td>
                                                <td><?php echo !empty($customer['cust_s_cname']) ? htmlspecialchars($customer['cust_s_cname']) : '<span class="text-muted"><em>Not specified</em></span>'; ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_104; ?></td>
                                                <td><?php echo htmlspecialchars($customer['cust_s_phone']); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_106; ?></td>
                                                <td><?php echo !empty($cust_s_country_name) ? htmlspecialchars($cust_s_country_name) : '<span class="text-muted"><em>Not specified</em></span>'; ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #444;"><?php echo LANG_VALUE_105; ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($customer['cust_s_address'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #444;">City, State, Zip</td>
                                                <td><?php echo htmlspecialchars($customer['cust_s_city'] . ', ' . $customer['cust_s_state'] . ' ' . $customer['cust_s_zip']); ?></td>
                                            </tr>
                                        </table>
                                    <?php else: ?>
                                        <div style="padding: 20px; text-align: center; color: #777;">
                                            <i class="fa fa-info-circle fa-2x" style="color: #bbb; margin-bottom: 10px; display: block;"></i>
                                            <p style="margin-bottom: 10px;">Shipping address has not been provided yet.</p>
                                            <a href="customer-billing-shipping-update.php" class="btn btn-sm btn-danger">
                                                <i class="fa fa-plus"></i> Set Shipping Address
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Navigation Links -->
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;">
                        <a href="customer-profile-update.php" class="btn btn-default" style="margin: 3px;">
                            <i class="fa fa-pencil"></i> <?php echo LANG_VALUE_117; ?>
                        </a>
                        <a href="customer-billing-shipping-update.php" class="btn btn-default" style="margin: 3px;">
                            <i class="fa fa-truck"></i> <?php echo LANG_VALUE_88; ?>
                        </a>
                        <a href="customer-password-update.php" class="btn btn-default" style="margin: 3px;">
                            <i class="fa fa-key"></i> <?php echo LANG_VALUE_99; ?>
                        </a>
                        <a href="customer-order.php" class="btn btn-default" style="margin: 3px;">
                            <i class="fa fa-list"></i> <?php echo LANG_VALUE_24; ?>
                        </a>
                    </div>

                </div>                
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>

