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
?>

<div class="page">
    <div class="container">
        <div class="row">            
            <div class="col-md-12"> 
                <?php require_once('customer-sidebar.php'); ?>
            </div>
            <div class="col-md-12">
                <div class="user-content">
                    <h3 class="text-center">
                        <?php echo LANG_VALUE_90; ?>
                    </h3>
                    <p class="text-center" style="font-size: 16px; color: #666; margin-bottom: 30px;">
                        Hello, <strong><?php echo htmlspecialchars($_SESSION['customer']['cust_name']); ?></strong>! Welcome back to your account dashboard.
                    </p>
                    <div class="row text-center" style="margin-top: 20px;">
                        <div class="col-md-3 col-sm-6" style="margin-bottom: 20px;">
                            <div style="border: 1px solid #e1e1e1; padding: 25px 15px; border-radius: 6px; background: #fafafa;">
                                <i class="fa fa-id-card-o fa-3x" style="color: #e4144d; margin-bottom: 15px;"></i>
                                <h4 style="font-weight: 600;">Customer Profile</h4>
                                <p style="color: #777; font-size: 13px;">View your registration & personal information</p>
                                <a href="customer-profile.php" class="btn btn-sm btn-danger">View Profile</a>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6" style="margin-bottom: 20px;">
                            <div style="border: 1px solid #e1e1e1; padding: 25px 15px; border-radius: 6px; background: #fafafa;">
                                <i class="fa fa-pencil fa-3x" style="color: #e4144d; margin-bottom: 15px;"></i>
                                <h4 style="font-weight: 600;">Edit Profile</h4>
                                <p style="color: #777; font-size: 13px;">Update your personal details</p>
                                <a href="customer-profile-update.php" class="btn btn-sm btn-danger">Edit Info</a>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6" style="margin-bottom: 20px;">
                            <div style="border: 1px solid #e1e1e1; padding: 25px 15px; border-radius: 6px; background: #fafafa;">
                                <i class="fa fa-truck fa-3x" style="color: #e4144d; margin-bottom: 15px;"></i>
                                <h4 style="font-weight: 600;">Billing & Shipping</h4>
                                <p style="color: #777; font-size: 13px;">Manage addresses for orders</p>
                                <a href="customer-billing-shipping-update.php" class="btn btn-sm btn-danger">Manage Addresses</a>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6" style="margin-bottom: 20px;">
                            <div style="border: 1px solid #e1e1e1; padding: 25px 15px; border-radius: 6px; background: #fafafa;">
                                <i class="fa fa-shopping-bag fa-3x" style="color: #e4144d; margin-bottom: 15px;"></i>
                                <h4 style="font-weight: 600;">My Orders</h4>
                                <p style="color: #777; font-size: 13px;">Track your past purchase history</p>
                                <a href="customer-order.php" class="btn btn-sm btn-danger">View Orders</a>
                            </div>
                        </div>
                    </div>
                </div>                
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>