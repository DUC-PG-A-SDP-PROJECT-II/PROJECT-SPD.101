<?php require_once('header.php'); ?>
<!-- fetching row banner login -->
<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_login = $row['banner_login'];
}
?>
<!-- login form -->
<?php
if(isset($_POST['form1'])) {
        
    if(empty($_POST['cust_email']) || empty($_POST['cust_password'])) {
        $error_message = LANG_VALUE_132.'<br>';
    } else {
        
        $cust_email = strip_tags($_POST['cust_email']);
        $cust_password = strip_tags($_POST['cust_password']);

        $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email=?");
        $statement->execute(array($cust_email));
        $total = $statement->rowCount();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach($result as $row) {
            $cust_status = $row['cust_status'];
            $row_password = $row['cust_password'];
        }

        if($total==0) {
            $error_message .= LANG_VALUE_133.'<br>';
        } else {
            //using MD5 form
            if( $row_password != md5($cust_password) ) {
                $error_message .= LANG_VALUE_139.'<br>';
            } else {
                if($cust_status == 0) {
                    $error_message .= LANG_VALUE_148.'<br>';
                } else {
                    $_SESSION['customer'] = $row;
                    header("location: ".BASE_URL."dashboard.php");
                }
            }
            
        }
    }
}
?>

<div class="auth-page-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
                
                <div class="auth-card">
                    
                    <div class="auth-header">
                        <div class="auth-icon-circle">
                            <i class="fa fa-user"></i>
                        </div>
                        <h2 class="auth-title"><?php echo LANG_VALUE_10; ?></h2>
                        <p class="auth-subtitle">Sign in to your customer account to manage orders, wishlist, and profile.</p>
                    </div>

                    <?php
                    if($error_message != '') {
                        echo '<div class="alert alert-danger" style="border-radius:12px; font-size:13px; margin-bottom:20px; display:flex; align-items:center; gap:10px;"><i class="fa fa-exclamation-circle fa-lg"></i> <div>'.$error_message.'</div></div>';
                    }
                    if($success_message != '') {
                        echo '<div class="alert alert-success" style="border-radius:12px; font-size:13px; margin-bottom:20px; display:flex; align-items:center; gap:10px;"><i class="fa fa-check-circle fa-lg"></i> <div>'.$success_message.'</div></div>';
                    }
                    ?>

                    <form action="" method="post" id="loginForm">
                        <?php $csrf->echoInputField(); ?>

                        <div class="auth-field-group">
                            <label class="auth-label"><?php echo LANG_VALUE_94; ?> <span class="required">*</span></label>
                            <div class="auth-input-wrap">
                                <i class="fa fa-envelope-o auth-input-icon"></i>
                                <input type="email" class="auth-input" name="cust_email" placeholder="name@example.com" value="<?php if(isset($_POST['cust_email'])){echo htmlspecialchars($_POST['cust_email']);} ?>" required autofocus>
                            </div>
                        </div>

                        <div class="auth-field-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:7px;">
                                <label class="auth-label" style="margin-bottom:0;"><?php echo LANG_VALUE_96; ?> <span class="required">*</span></label>
                                <a href="forget-password.php" style="font-size:12px; font-weight:700; color:#2563eb; text-decoration:none;" tabindex="-1">
                                    <?php echo LANG_VALUE_97; ?>?
                                </a>
                            </div>
                            <div class="auth-input-wrap">
                                <i class="fa fa-lock auth-input-icon"></i>
                                <input type="password" class="auth-input" id="cust_password" name="cust_password" placeholder="Enter your password" required>
                                <button type="button" class="auth-pw-toggle" onclick="toggleAuthPw('cust_password', this)" title="Show/Hide Password" tabindex="-1">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="auth-submit-btn" name="form1">
                            <span><?php echo LANG_VALUE_4; ?></span>
                            <i class="fa fa-arrow-right"></i>
                        </button>

                        <div class="auth-footer-divider">
                            <span>New customer?</span>
                        </div>

                        <a href="registration.php" class="auth-alt-link">
                            <i class="fa fa-user-plus" style="margin-right:6px; color:#2563eb;"></i>
                            Create a New Account
                        </a>

                        <div class="auth-trust-strip">
                            <span><i class="fa fa-shield"></i> 256-bit SSL Secure</span>
                            <span><i class="fa fa-qrcode"></i> Bakong KHQR</span>
                            <span><i class="fa fa-truck"></i> Fast Delivery</span>
                        </div>
                    </form>

                </div>

            </div>
        </div>
    </div>
</div>

<script>
function toggleAuthPw(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa fa-eye';
    }
}
</script>

<?php require_once('footer.php'); ?>