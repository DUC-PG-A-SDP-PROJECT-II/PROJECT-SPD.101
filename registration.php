<?php require_once('header.php'); ?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_registration = $row['banner_registration'];
}
?>

<?php
if (isset($_POST['form1'])) {

    $valid = 1;

    if(empty($_POST['cust_name'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_123."<br>";
    }

    if(empty($_POST['cust_email'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_131."<br>";
    } else {
        if (filter_var($_POST['cust_email'], FILTER_VALIDATE_EMAIL) === false) {
            $valid = 0;
            $error_message .= LANG_VALUE_134."<br>";
        } else {
            $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email=?");
            $statement->execute(array($_POST['cust_email']));
            $total = $statement->rowCount();                            
            if($total) {
                $valid = 0;
                $error_message .= LANG_VALUE_147."<br>";
            }
        }
    }

    if(empty($_POST['cust_phone'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_124."<br>";
    }

    if(empty($_POST['cust_address'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_125."<br>";
    }

    if(empty($_POST['cust_country'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_126."<br>";
    }

    if(empty($_POST['cust_city'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_127."<br>";
    }

    if(empty($_POST['cust_state'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_128."<br>";
    }

    if(empty($_POST['cust_zip'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_129."<br>";
    }

    if( empty($_POST['cust_password']) || empty($_POST['cust_re_password']) ) {
        $valid = 0;
        $error_message .= LANG_VALUE_138."<br>";
    }

    if( !empty($_POST['cust_password']) && !empty($_POST['cust_re_password']) ) {
        if($_POST['cust_password'] != $_POST['cust_re_password']) {
            $valid = 0;
            $error_message .= LANG_VALUE_139."<br>";
        }
    }

    // Check if a temporary photo was already uploaded in a previous validation attempt
    $cust_photo = '';
    if (!empty($_POST['temp_cust_photo']) && file_exists('assets/uploads/'.$_POST['temp_cust_photo']) && is_file('assets/uploads/'.$_POST['temp_cust_photo'])) {
        $cust_photo = strip_tags($_POST['temp_cust_photo']);
    } elseif (!empty($_SESSION['temp_reg_photo']) && file_exists('assets/uploads/'.$_SESSION['temp_reg_photo']) && is_file('assets/uploads/'.$_SESSION['temp_reg_photo'])) {
        $cust_photo = $_SESSION['temp_reg_photo'];
    }

    if(isset($_FILES['cust_photo']) && !empty($_FILES['cust_photo']['name'])) {
        $photo_path = $_FILES['cust_photo']['name'];
        $photo_tmp = $_FILES['cust_photo']['tmp_name'];
        $ext = strtolower(pathinfo($photo_path, PATHINFO_EXTENSION));
        $valid_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if(!in_array($ext, $valid_ext)) {
            $valid = 0;
            $error_message .= 'Profile photo must be a JPG, JPEG, PNG, GIF, or WEBP file.<br>';
        } else {
            // Delete previous temp photo if exists
            if(!empty($cust_photo) && file_exists('assets/uploads/'.$cust_photo) && is_file('assets/uploads/'.$cust_photo)) {
                @unlink('assets/uploads/'.$cust_photo);
            }
            $new_photo = 'customer-'.time().'-'.rand(1000,9999).'.'.$ext;
            if(move_uploaded_file($photo_tmp, 'assets/uploads/'.$new_photo)) {
                $cust_photo = $new_photo;
                $_SESSION['temp_reg_photo'] = $cust_photo;
            }
        }
    }

    if($valid == 1) {

        $token = md5(time());
        $cust_datetime = date('Y-m-d h:i:s');
        $cust_timestamp = time();

        // saving into the database
        $statement = $pdo->prepare("INSERT INTO tbl_customer (
                                        cust_name,
                                        cust_cname,
                                        cust_email,
                                        cust_phone,
                                        cust_country,
                                        cust_address,
                                        cust_city,
                                        cust_state,
                                        cust_zip,
                                        cust_photo,
                                        cust_b_name,
                                        cust_b_cname,
                                        cust_b_phone,
                                        cust_b_country,
                                        cust_b_address,
                                        cust_b_city,
                                        cust_b_state,
                                        cust_b_zip,
                                        cust_s_name,
                                        cust_s_cname,
                                        cust_s_phone,
                                        cust_s_country,
                                        cust_s_address,
                                        cust_s_city,
                                        cust_s_state,
                                        cust_s_zip,
                                        cust_password,
                                        cust_token,
                                        cust_datetime,
                                        cust_timestamp,
                                        cust_status
                                    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $statement->execute(array(
                                        strip_tags($_POST['cust_name']),
                                        strip_tags($_POST['cust_cname']),
                                        strip_tags($_POST['cust_email']),
                                        strip_tags($_POST['cust_phone']),
                                        strip_tags($_POST['cust_country']),
                                        strip_tags($_POST['cust_address']),
                                        strip_tags($_POST['cust_city']),
                                        strip_tags($_POST['cust_state']),
                                        strip_tags($_POST['cust_zip']),
                                        $cust_photo,
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        '',
                                        md5($_POST['cust_password']),
                                        $token,
                                        $cust_datetime,
                                        $cust_timestamp,
                                        1
                                    ));

        // Send email for confirmation of the account (if mail server is configured)
        $to = $_POST['cust_email'];
        
        $subject = LANG_VALUE_150;
        $verify_link = BASE_URL.'verify.php?email='.$to.'&token='.$token;
        $message = '
'.LANG_VALUE_151.'<br><br>

<a href="'.$verify_link.'">'.$verify_link.'</a>';

        $headers = "From: noreply@" . BASE_URL . "\r\n" .
                   "Reply-To: noreply@" . BASE_URL . "\r\n" .
                   "X-Mailer: PHP/" . phpversion() . "\r\n" . 
                   "MIME-Version: 1.0\r\n" . 
                   "Content-Type: text/html; charset=ISO-8859-1\r\n";
        
        // Sending Email (suppressed with @ to prevent warning if local SMTP server is not running)
        @mail($to, $subject, $message, $headers);

        unset($_POST['cust_name']);
        unset($_POST['cust_cname']);
        unset($_POST['cust_email']);
        unset($_POST['cust_phone']);
        unset($_POST['cust_address']);
        unset($_POST['cust_city']);
        unset($_POST['cust_state']);
        unset($_POST['cust_zip']);

        unset($_SESSION['temp_reg_photo']);

        // Automatically log the newly registered user in and redirect to Customer Profile page
        $cust_id = $pdo->lastInsertId();
        $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=?");
        $statement->execute(array($cust_id));
        $new_customer = $statement->fetch(PDO::FETCH_ASSOC);

        if($new_customer) {
            $_SESSION['customer'] = $new_customer;
            $_SESSION['registration_success'] = 'Welcome, ' . htmlspecialchars($new_customer['cust_name']) . '! Your account has been registered successfully. Here is your profile information:';
            header('location: '.BASE_URL.'customer-profile.php');
            exit;
        }

        $success_message = 'Your registration is completed. You can now <a href="customer-profile.php" style="color:#167ac6;font-weight:bold;">View your profile</a> or <a href="login.php" style="color:#167ac6;font-weight:bold;">login</a>.';
    }
}
?>

<div class="auth-page-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-md-10 col-md-offset-1 col-lg-8 col-lg-offset-2">
                
                <div class="auth-card">
                    
                    <div class="auth-header">
                        <div class="auth-icon-circle">
                            <i class="fa fa-user-plus"></i>
                        </div>
                        <h2 class="auth-title"><?php echo LANG_VALUE_16; ?></h2>
                        <p class="auth-subtitle">Create your customer account to enjoy fast checkout with Bakong KHQR, live order tracking, and custom wishlist.</p>
                    </div>

                    <form action="" method="post" enctype="multipart/form-data" id="registrationForm">
                        <?php $csrf->echoInputField(); ?>
                        <?php
                        $current_temp_photo = '';
                        if (!empty($cust_photo) && file_exists('assets/uploads/'.$cust_photo) && is_file('assets/uploads/'.$cust_photo)) {
                            $current_temp_photo = $cust_photo;
                        } elseif (!empty($_POST['temp_cust_photo']) && file_exists('assets/uploads/'.$_POST['temp_cust_photo']) && is_file('assets/uploads/'.$_POST['temp_cust_photo'])) {
                            $current_temp_photo = strip_tags($_POST['temp_cust_photo']);
                        } elseif (!empty($_SESSION['temp_reg_photo']) && file_exists('assets/uploads/'.$_SESSION['temp_reg_photo']) && is_file('assets/uploads/'.$_SESSION['temp_reg_photo'])) {
                            $current_temp_photo = $_SESSION['temp_reg_photo'];
                        }
                        ?>
                        <input type="hidden" name="temp_cust_photo" id="temp_cust_photo" value="<?php echo htmlspecialchars($current_temp_photo); ?>">

                        <?php if($error_message != ''): ?>
                            <div class="auth-alert auth-alert-danger" role="alert">
                                <i class="fa fa-exclamation-circle" style="font-size:18px;margin-top:2px;"></i>
                                <div style="flex:1;"><?php echo $error_message; ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if($success_message != ''): ?>
                            <div class="auth-alert auth-alert-success" role="alert">
                                <i class="fa fa-check-circle" style="font-size:18px;margin-top:2px;"></i>
                                <div style="flex:1;"><?php echo $success_message; ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Avatar Upload Card -->
                        <div class="reg-avatar-section">
                            <div class="reg-avatar-wrap">
                                <?php if(!empty($current_temp_photo)): ?>
                                    <img src="<?php echo BASE_URL; ?>assets/uploads/<?php echo htmlspecialchars($current_temp_photo); ?>" alt="Profile Preview" id="avatarPreviewImg" class="reg-avatar-img">
                                    <div id="avatarFallbackIcon" class="reg-avatar-placeholder" style="display:none;"><i class="fa fa-user"></i></div>
                                <?php else: ?>
                                    <img src="" alt="Profile Preview" id="avatarPreviewImg" class="reg-avatar-img" style="display:none;">
                                    <div id="avatarFallbackIcon" class="reg-avatar-placeholder"><i class="fa fa-user"></i></div>
                                <?php endif; ?>
                                <label for="cust_photo_input" class="reg-avatar-badge-btn" title="Choose Photo">
                                    <i class="fa fa-camera"></i>
                                </label>
                            </div>
                            <div class="reg-avatar-info">
                                <div class="reg-avatar-title">Profile Photo <span style="font-weight:normal;color:#94a3b8;font-size:12px;">(Optional)</span></div>
                                <div class="reg-avatar-desc">Upload your profile image (JPG, PNG, WEBP). It will be saved directly to your account.</div>
                                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
                                    <label for="cust_photo_input" class="reg-avatar-pick-btn">
                                        <i class="fa fa-cloud-upload"></i> <?php echo !empty($current_temp_photo) ? 'Change Photo' : 'Select Photo'; ?>
                                    </label>
                                    <span id="cust_photo_filename" style="font-size:12px;color:#64748b;"><?php echo !empty($current_temp_photo) ? htmlspecialchars($current_temp_photo) : 'No file chosen'; ?></span>
                                </div>
                                <input type="file" id="cust_photo_input" name="cust_photo" accept="image/png, image/jpeg, image/jpg, image/gif, image/webp" style="display:none;" onchange="handleAvatarPreview(this);">
                            </div>
                        </div>

                        <!-- Section 1: Account Information -->
                        <div class="reg-section-divider">
                            <i class="fa fa-user"></i> <span>1. Account Information</span>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="auth-label" for="cust_name"><?php echo LANG_VALUE_102; ?> *</label>
                                <div class="auth-input-wrap">
                                    <i class="fa fa-user auth-input-icon"></i>
                                    <input type="text" id="cust_name" class="auth-input" name="cust_name" placeholder="John Doe" value="<?php if(isset($_POST['cust_name'])){echo htmlspecialchars($_POST['cust_name']);} ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="auth-label" for="cust_cname"><?php echo LANG_VALUE_103; ?></label>
                                <div class="auth-input-wrap">
                                    <i class="fa fa-building auth-input-icon"></i>
                                    <input type="text" id="cust_cname" class="auth-input" name="cust_cname" placeholder="Company or Organization (Optional)" value="<?php if(isset($_POST['cust_cname'])){echo htmlspecialchars($_POST['cust_cname']);} ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="auth-label" for="cust_email"><?php echo LANG_VALUE_94; ?> *</label>
                                <div class="auth-input-wrap">
                                    <i class="fa fa-envelope auth-input-icon"></i>
                                    <input type="email" id="cust_email" class="auth-input" name="cust_email" placeholder="name@example.com" value="<?php if(isset($_POST['cust_email'])){echo htmlspecialchars($_POST['cust_email']);} ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="auth-label" for="cust_phone"><?php echo LANG_VALUE_104; ?> *</label>
                                <div class="auth-input-wrap">
                                    <i class="fa fa-phone auth-input-icon"></i>
                                    <input type="text" id="cust_phone" class="auth-input" name="cust_phone" placeholder="+855 12 345 678" value="<?php if(isset($_POST['cust_phone'])){echo htmlspecialchars($_POST['cust_phone']);} ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="auth-label" for="cust_password"><?php echo LANG_VALUE_96; ?> *</label>
                                <div class="auth-input-wrap">
                                    <i class="fa fa-lock auth-input-icon"></i>
                                    <input type="password" id="cust_password" class="auth-input" name="cust_password" placeholder="Create strong password" required>
                                    <button type="button" class="auth-pw-toggle" onclick="togglePasswordVisibility('cust_password', this)" title="Show/Hide Password">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="auth-label" for="cust_re_password"><?php echo LANG_VALUE_98; ?> *</label>
                                <div class="auth-input-wrap">
                                    <i class="fa fa-lock auth-input-icon"></i>
                                    <input type="password" id="cust_re_password" class="auth-input" name="cust_re_password" placeholder="Confirm your password" required>
                                    <button type="button" class="auth-pw-toggle" onclick="togglePasswordVisibility('cust_re_password', this)" title="Show/Hide Password">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Address Information -->
                        <div class="reg-section-divider">
                            <i class="fa fa-map-marker"></i> <span>2. Shipping & Address Details</span>
                        </div>

                        <div class="form-group">
                            <label class="auth-label" for="cust_address"><?php echo LANG_VALUE_105; ?> *</label>
                            <div class="auth-input-wrap">
                                <i class="fa fa-home auth-input-icon" style="top:18px;"></i>
                                <textarea id="cust_address" name="cust_address" class="auth-input" rows="2" placeholder="Street name, house/building number, apartment" style="padding-left:42px;height:72px;resize:vertical;" required><?php if(isset($_POST['cust_address'])){echo htmlspecialchars($_POST['cust_address']);} ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="auth-label" for="cust_country"><?php echo LANG_VALUE_106; ?> *</label>
                                <div class="auth-input-wrap">
                                    <i class="fa fa-globe auth-input-icon"></i>
                                    <select id="cust_country" name="cust_country" class="auth-input select2" required style="padding-left:42px;">
                                        <option value="">Select country</option>
                                        <?php
                                        $selected_country = isset($_POST['cust_country']) ? $_POST['cust_country'] : '';
                                        $statement = $pdo->prepare("SELECT * FROM tbl_country ORDER BY country_name ASC");
                                        $statement->execute();
                                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
                                        foreach ($result as $row) {
                                            $sel = ($selected_country == $row['country_id']) ? 'selected' : '';
                                            echo '<option value="'.$row['country_id'].'" '.$sel.'>'.$row['country_name'].'</option>';
                                        }
                                        ?>    
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="auth-label" for="cust_city"><?php echo LANG_VALUE_107; ?> *</label>
                                <div class="auth-input-wrap">
                                    <i class="fa fa-building-o auth-input-icon"></i>
                                    <input type="text" id="cust_city" class="auth-input" name="cust_city" placeholder="e.g. Phnom Penh" value="<?php if(isset($_POST['cust_city'])){echo htmlspecialchars($_POST['cust_city']);} ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="auth-label" for="cust_state"><?php echo LANG_VALUE_108; ?> *</label>
                                <div class="auth-input-wrap">
                                    <i class="fa fa-map-signs auth-input-icon"></i>
                                    <input type="text" id="cust_state" class="auth-input" name="cust_state" placeholder="e.g. Phnom Penh" value="<?php if(isset($_POST['cust_state'])){echo htmlspecialchars($_POST['cust_state']);} ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="auth-label" for="cust_zip"><?php echo LANG_VALUE_109; ?> *</label>
                                <div class="auth-input-wrap">
                                    <i class="fa fa-envelope-o auth-input-icon"></i>
                                    <input type="text" id="cust_zip" class="auth-input" name="cust_zip" placeholder="e.g. 12000" value="<?php if(isset($_POST['cust_zip'])){echo htmlspecialchars($_POST['cust_zip']);} ?>" required>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:16px;">
                            <button type="submit" class="auth-submit-btn" name="form1">
                                <i class="fa fa-user-plus"></i> <?php echo LANG_VALUE_15; ?> <i class="fa fa-arrow-right" style="font-size:13px;margin-left:4px;"></i>
                            </button>
                        </div>

                        <div class="auth-footer-divider">
                            <span>Already registered?</span>
                        </div>

                        <a href="login.php" class="auth-alt-link">
                            <i class="fa fa-sign-in"></i> Sign In to Existing Account
                        </a>

                        <div class="auth-trust-strip">
                            <span><i class="fa fa-shield"></i> SSL Protected</span>
                            <span><i class="fa fa-lock"></i> Safe & Confidential</span>
                            <span><i class="fa fa-check-circle"></i> Instant Activation</span>
                        </div>
                    </form>

                    <script>
                    function handleAvatarPreview(input) {
                        if (input.files && input.files[0]) {
                            var file = input.files[0];
                            var reader = new FileReader();
                            reader.onload = function(e) {
                                var img = document.getElementById('avatarPreviewImg');
                                var fallback = document.getElementById('avatarFallbackIcon');
                                var filename = document.getElementById('cust_photo_filename');
                                img.src = e.target.result;
                                img.style.display = 'block';
                                if (fallback) fallback.style.display = 'none';
                                if (filename) filename.textContent = file.name;
                            };
                            reader.readAsDataURL(file);
                        }
                    }

                    function togglePasswordVisibility(fieldId, btn) {
                        var field = document.getElementById(fieldId);
                        if (!field) return;
                        var icon = btn.querySelector('i');
                        if (field.type === 'password') {
                            field.type = 'text';
                            if (icon) {
                                icon.classList.remove('fa-eye');
                                icon.classList.add('fa-eye-slash');
                            }
                        } else {
                            field.type = 'password';
                            if (icon) {
                                icon.classList.remove('fa-eye-slash');
                                icon.classList.add('fa-eye');
                            }
                        }
                    }
                    </script>
                </div>                
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>