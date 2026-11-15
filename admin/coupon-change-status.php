<?php require_once('header.php'); ?>

<?php
if( !isset($_REQUEST['id']) || !isset($_REQUEST['task']) ) {
	header('location: coupon.php');
	exit;
} else {
	try {
		$statement = $pdo->prepare("SELECT * FROM tbl_coupon WHERE coupon_id=?");
		$statement->execute([$_REQUEST['id']]);
		if($statement->rowCount() == 0) {
			header('location: coupon.php');
			exit;
		}
		$task = ($_REQUEST['task'] == 1) ? 1 : 0;
		$statement = $pdo->prepare("UPDATE tbl_coupon SET status=? WHERE coupon_id=?");
		$statement->execute([$task, $_REQUEST['id']]);
	} catch (PDOException $e) {
		// table doesn't exist
	}
}

header('location: coupon.php');
exit;
?>

