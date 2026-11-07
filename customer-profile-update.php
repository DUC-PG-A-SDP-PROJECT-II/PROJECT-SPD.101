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

<?php
// Fetch the latest customer details from the database
$statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=?");
$statement->execute(array($_SESSION['customer']['cust_id']));
$current_cust = $statement->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['form1'])) {

    $valid = 1;

    if(empty($_POST['cust_name'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_123."<br>";
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

    $final_photo_name = $current_cust['cust_photo'] ?? '';

    // Handle photo upload
    if(isset($_FILES['cust_photo']) && !empty($_FILES['cust_photo']['name'])) {
        $photo_name = $_FILES['cust_photo']['name'];
        $photo_tmp = $_FILES['cust_photo']['tmp_name'];
        $ext = strtolower(pathinfo($photo_name, PATHINFO_EXTENSION));
        $valid_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if(!in_array($ext, $valid_ext)) {
            $valid = 0;
            $error_message .= 'Profile photo must be a JPG, JPEG, PNG, GIF, or WEBP file.<br>';
        } else {
            // Delete previous photo from disk if exists
            if(!empty($current_cust['cust_photo']) && file_exists('assets/uploads/'.$current_cust['cust_photo']) && is_file('assets/uploads/'.$current_cust['cust_photo'])) {
                @unlink('assets/uploads/'.$current_cust['cust_photo']);
            }
            $final_photo_name = 'customer-'.$_SESSION['customer']['cust_id'].'-'.time().'.'.$ext;
            move_uploaded_file($photo_tmp, 'assets/uploads/'.$final_photo_name);
        }
    }

    // Handle remove photo request
    if(isset($_POST['remove_photo']) && $_POST['remove_photo'] == 1) {
        if(!empty($current_cust['cust_photo']) && file_exists('assets/uploads/'.$current_cust['cust_photo']) && is_file('assets/uploads/'.$current_cust['cust_photo'])) {
            @unlink('assets/uploads/'.$current_cust['cust_photo']);
        }
        $final_photo_name = '';
    }

    if($valid == 1) {

        // update data into the database
        $statement = $pdo->prepare("UPDATE tbl_customer SET cust_name=?, cust_cname=?, cust_phone=?, cust_country=?, cust_address=?, cust_city=?, cust_state=?, cust_zip=?, cust_photo=? WHERE cust_id=?");
        $statement->execute(array(
                    strip_tags($_POST['cust_name']),
                    strip_tags($_POST['cust_cname']),
                    strip_tags($_POST['cust_phone']),
                    strip_tags($_POST['cust_country']),
                    strip_tags($_POST['cust_address']),
                    strip_tags($_POST['cust_city']),
                    strip_tags($_POST['cust_state']),
                    strip_tags($_POST['cust_zip']),
                    $final_photo_name,
                    $_SESSION['customer']['cust_id']
                ));  
       
        $success_message = LANG_VALUE_130;

        $_SESSION['customer']['cust_name'] = $_POST['cust_name'];
        $_SESSION['customer']['cust_cname'] = $_POST['cust_cname'];
        $_SESSION['customer']['cust_phone'] = $_POST['cust_phone'];
        $_SESSION['customer']['cust_country'] = $_POST['cust_country'];
        $_SESSION['customer']['cust_address'] = $_POST['cust_address'];
        $_SESSION['customer']['cust_city'] = $_POST['cust_city'];
        $_SESSION['customer']['cust_state'] = $_POST['cust_state'];
        $_SESSION['customer']['cust_zip'] = $_POST['cust_zip'];
        $_SESSION['customer']['cust_photo'] = $final_photo_name;

        // Refresh current_cust
        $current_cust['cust_photo'] = $final_photo_name;
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
                    <h3>
                        <?php echo LANG_VALUE_117; ?>
                    </h3>
                    <?php
                    if($error_message != '') {
                        echo "<div class='error' style='padding: 10px;background:#f1f1f1;margin-bottom:20px;'>".$error_message."</div>";
                    }
                    if($success_message != '') {
                        echo "<div class='success' style='padding: 10px;background:#f1f1f1;margin-bottom:20px;'>".$success_message."</div>";
                    }
                    ?>
                    <form action="" method="post" enctype="multipart/form-data">
                        <?php $csrf->echoInputField(); ?>
                        <div class="row">
                            <!-- Profile Photo Card -->
                            <div class="col-md-12 form-group" style="background: #fdfdfd; border: 1px solid #e1e1e1; border-radius: 6px; padding: 20px; margin-bottom: 25px;">
                                <label style="font-size: 15px; font-weight: bold; margin-bottom: 12px; display: block;">
                                    <i class="fa fa-camera" style="color: #e4144d; margin-right: 6px;"></i> Profile Photo
                                </label>
                                <div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
                                    <div class="col-sm-3 text-center" style="margin-bottom: 10px;">
                                        <?php if(!empty($current_cust['cust_photo']) && file_exists('assets/uploads/'.$current_cust['cust_photo'])): ?>
                                            <img src="assets/uploads/<?php echo htmlspecialchars($current_cust['cust_photo']); ?>" alt="Profile" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #e4144d; box-shadow: 0 2px 5px rgba(0,0,0,0.15);">
                                        <?php else: ?>
                                            <div style="width: 100px; height: 100px; line-height: 100px; border-radius: 50%; background: #eee; color: #999; font-size: 45px; margin: 0 auto; border: 2px dashed #ccc;">
                                                <i class="fa fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-sm-9">
                                        <p style="margin-bottom: 8px; font-size: 13px; color: #666;">Upload a new profile picture (JPG, JPEG, PNG, GIF, or WEBP):</p>
                                        <input type="file" name="cust_photo" class="form-control" accept="image/png, image/jpeg, image/jpg, image/gif, image/webp" style="margin-bottom: 8px;">
                                        <?php if(!empty($current_cust['cust_photo']) && file_exists('assets/uploads/'.$current_cust['cust_photo'])): ?>
                                            <label style="font-weight: normal; font-size: 13px; color: #d9534f; cursor: pointer; margin-top: 5px;">
                                                <input type="checkbox" name="remove_photo" value="1"> Remove current photo
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for=""><?php echo LANG_VALUE_102; ?> *</label>
                                <input type="text" class="form-control" name="cust_name" value="<?php echo $_SESSION['customer']['cust_name']; ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for=""><?php echo LANG_VALUE_103; ?></label>
                                <input type="text" class="form-control" name="cust_cname" value="<?php echo $_SESSION['customer']['cust_cname']; ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for=""><?php echo LANG_VALUE_94; ?> *</label>
                                <input type="text" class="form-control" name="" value="<?php echo $_SESSION['customer']['cust_email']; ?>" disabled>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for=""><?php echo LANG_VALUE_104; ?> *</label>
                                <input type="text" class="form-control" name="cust_phone" value="<?php echo $_SESSION['customer']['cust_phone']; ?>">
                            </div>
                            <div class="col-md-12 form-group">
                                <label for=""><?php echo LANG_VALUE_105; ?> *</label>
                                <textarea name="cust_address" class="form-control" cols="30" rows="10" style="height:70px;"><?php echo $_SESSION['customer']['cust_address']; ?></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for=""><?php echo LANG_VALUE_106; ?> *</label>
                                <select name="cust_country" class="form-control">
                                <?php
                                $statement = $pdo->prepare("SELECT * FROM tbl_country ORDER BY country_name ASC");
                                $statement->execute();
                                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($result as $row) {
                                    ?>
                                    <option value="<?php echo $row['country_id']; ?>" <?php if($row['country_id'] == $_SESSION['customer']['cust_country']) {echo 'selected';} ?>><?php echo $row['country_name']; ?></option>
                                    <?php
                                }
                                ?>
                                </select>                                    
                            </div>
                            
                            <div class="col-md-6 form-group">
                                <label for=""><?php echo LANG_VALUE_107; ?> *</label>
                                <input type="text" class="form-control" name="cust_city" value="<?php echo $_SESSION['customer']['cust_city']; ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for=""><?php echo LANG_VALUE_108; ?> *</label>
                                <input type="text" class="form-control" name="cust_state" value="<?php echo $_SESSION['customer']['cust_state']; ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for=""><?php echo LANG_VALUE_109; ?> *</label>
                                <input type="text" class="form-control" name="cust_zip" value="<?php echo $_SESSION['customer']['cust_zip']; ?>">
                            </div>
                        </div>
                        <input type="submit" class="btn btn-primary" value="<?php echo LANG_VALUE_5; ?>" name="form1">
                    </form>
                </div>                
            </div>
        </div>
    </div>
</div>


<?php require_once('footer.php'); ?>