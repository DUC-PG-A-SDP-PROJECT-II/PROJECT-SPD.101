<?php
ob_start();
session_start();
include("inc/config.php");
include("inc/functions.php");
include("inc/CSRF_Protect.php");
$csrf = new CSRF_Protect();
$error_message='';

if(isset($_POST['form1'])) {
        
    if(empty($_POST['email']) || empty($_POST['password'])) {
        $error_message = 'Email and/or Password can not be empty<br>';
    } else {
		
		$email = strip_tags($_POST['email']);
		$password = strip_tags($_POST['password']);

    	$statement = $pdo->prepare("SELECT * FROM tbl_user WHERE email=? AND status=?");
    	$statement->execute(array($email,'Active'));
    	$total = $statement->rowCount();    
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);    
        if($total==0) {
            $error_message .= 'Email Address does not match<br>';
        } else {       
            foreach($result as $row) { 
                $row_password = $row['password'];
            }
        
            if( $row_password != md5($password) ) {
                $error_message .= 'Password does not match<br>';
            } else {       
            
				$_SESSION['user'] = $row;
                header("location: index.php");
            }
        }
    }

    
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Login</title>

	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/font-awesome.min.css">
	<link rel="stylesheet" href="css/ionicons.min.css">
	<link rel="stylesheet" href="css/datepicker3.css">
	<link rel="stylesheet" href="css/all.css">
	<link rel="stylesheet" href="css/select2.min.css">
	<link rel="stylesheet" href="css/dataTables.bootstrap.css">
	<link rel="stylesheet" href="css/AdminLTE.min.css">
	<link rel="stylesheet" href="css/_all-skins.min.css">

	<link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="css/modern-admin.css?v=<?php echo time(); ?>">
</head>

<body class="admin-login-body">

<div class="admin-login-wrapper">
	<div class="admin-login-card">
		<div class="admin-login-header">
			<div class="admin-login-badge-pill">
				<span class="pulse-dot"></span>
				<span>Secure Admin Console</span>
			</div>
			<div class="admin-login-icon-box">
				<i class="fa fa-shield"></i>
			</div>
			<h1 class="admin-login-title">Control Panel</h1>
			<p class="admin-login-sub">Sign in with authorized administrator credentials</p>
		</div>

		<?php if(!empty($error_message)): ?>
			<div class="admin-login-alert" role="alert">
				<i class="fa fa-exclamation-triangle" style="font-size:16px;margin-top:2px;color:#dc2626;"></i>
				<div style="flex:1;"><?php echo $error_message; ?></div>
			</div>
		<?php endif; ?>

		<form action="" method="post" autocomplete="off">
			<?php $csrf->echoInputField(); ?>

			<div class="admin-input-group">
				<label class="admin-input-label" for="admin_email">Email Address</label>
				<div class="admin-input-wrap">
					<i class="fa fa-envelope admin-input-icon"></i>
					<input type="email" id="admin_email" name="email" class="admin-input-control" placeholder="admin@example.com" value="<?php if(isset($_POST['email'])){echo htmlspecialchars($_POST['email']);} ?>" required autofocus>
				</div>
			</div>

			<div class="admin-input-group">
				<label class="admin-input-label" for="admin_password">Password</label>
				<div class="admin-input-wrap">
					<i class="fa fa-lock admin-input-icon"></i>
					<input type="password" id="admin_password" name="password" class="admin-input-control" placeholder="Enter password" required>
					<button type="button" class="admin-pw-toggle" onclick="toggleAdminPassword()" title="Show/Hide Password">
						<i class="fa fa-eye" id="admin_pw_icon"></i>
					</button>
				</div>
			</div>

			<button type="submit" class="admin-login-btn" name="form1">
				<i class="fa fa-sign-in"></i>
				<span>Sign In to Dashboard</span>
				<i class="fa fa-arrow-right" style="font-size:13px;margin-left:4px;"></i>
			</button>
		</form>

		<div class="admin-login-footer">
			<div class="admin-security-note">
				<i class="fa fa-lock"></i>
				<span>256-Bit TLS Encrypted Session &bull; Access Monitored</span>
			</div>
			<a href="../index.php" class="admin-store-link">
				<i class="fa fa-arrow-left"></i> Return to Storefront
			</a>
		</div>
	</div>
</div>

<script>
function toggleAdminPassword() {
	var pw = document.getElementById('admin_password');
	var icon = document.getElementById('admin_pw_icon');
	if (!pw || !icon) return;
	if (pw.type === 'password') {
		pw.type = 'text';
		icon.classList.remove('fa-eye');
		icon.classList.add('fa-eye-slash');
	} else {
		pw.type = 'password';
		icon.classList.remove('fa-eye-slash');
		icon.classList.add('fa-eye');
	}
}
</script>


<script src="js/jquery-2.2.3.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<script src="js/select2.full.min.js"></script>
<script src="js/jquery.inputmask.js"></script>
<script src="js/jquery.inputmask.date.extensions.js"></script>
<script src="js/jquery.inputmask.extensions.js"></script>
<script src="js/moment.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="js/icheck.min.js"></script>
<script src="js/fastclick.js"></script>
<script src="js/jquery.sparkline.min.js"></script>
<script src="js/jquery.slimscroll.min.js"></script>
<script src="js/app.min.js"></script>
<script src="js/demo.js"></script>

</body>
</html>