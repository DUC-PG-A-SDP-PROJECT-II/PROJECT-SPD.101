<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="user-sidebar">
    <ul>
        <a href="dashboard.php"><button class="btn <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa fa-dashboard"></i> <?php echo LANG_VALUE_89; ?></button></a>
        <a href="customer-profile.php"><button class="btn <?php echo ($current_page == 'customer-profile.php') ? 'active' : ''; ?>"><i class="fa fa-id-card-o"></i> Customer Profile</button></a>
        <a href="customer-profile-update.php"><button class="btn <?php echo ($current_page == 'customer-profile-update.php') ? 'active' : ''; ?>"><i class="fa fa-pencil"></i> <?php echo LANG_VALUE_117; ?></button></a>
        <a href="customer-billing-shipping-update.php"><button class="btn <?php echo ($current_page == 'customer-billing-shipping-update.php') ? 'active' : ''; ?>"><i class="fa fa-map-marker"></i> <?php echo LANG_VALUE_88; ?></button></a>
        <a href="customer-password-update.php"><button class="btn <?php echo ($current_page == 'customer-password-update.php') ? 'active' : ''; ?>"><i class="fa fa-key"></i> <?php echo LANG_VALUE_99; ?></button></a>
        <a href="customer-order.php"><button class="btn <?php echo ($current_page == 'customer-order.php') ? 'active' : ''; ?>"><i class="fa fa-shopping-bag"></i> <?php echo LANG_VALUE_24; ?></button></a>
        <a href="logout.php"><button class="btn"><i class="fa fa-sign-out"></i> <?php echo LANG_VALUE_14; ?></button></a>
    </ul>
</div>