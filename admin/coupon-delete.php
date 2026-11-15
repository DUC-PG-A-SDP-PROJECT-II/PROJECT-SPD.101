<?php require_once('header.php'); ?>

<?php
if(!isset($_REQUEST['id'])) {
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
		$statement = $pdo->prepare("DELETE FROM tbl_coupon WHERE coupon_id=?");
		$statement->execute([$_REQUEST['id']]);
	} catch (PDOException $e) {
		// table doesn't exist
	}
}

header('location: coupon.php');
exit;
?>

