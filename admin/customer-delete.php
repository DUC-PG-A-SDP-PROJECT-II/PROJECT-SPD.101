<?php require_once('header.php'); ?>

<?php
if(!isset($_REQUEST['id'])) {
	header('location: logout.php');
	exit;
} else {
	// Check the id is valid or not
	$statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=?");
	$statement->execute(array($_REQUEST['id']));
	$total = $statement->rowCount();
	if( $total == 0 ) {
		header('location: logout.php');
		exit;
	}
}
?>

<?php

	// Getting photo to delete from disk
	$statement = $pdo->prepare("SELECT cust_photo FROM tbl_customer WHERE cust_id=?");
	$statement->execute(array($_REQUEST['id']));
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);							
	foreach ($result as $row) {
		$cust_photo = $row['cust_photo'];
	}
	if(!empty($cust_photo) && file_exists('../assets/uploads/'.$cust_photo) && is_file('../assets/uploads/'.$cust_photo)) {
		@unlink('../assets/uploads/'.$cust_photo);
	}

	// Delete from tbl_customer
	$statement = $pdo->prepare("DELETE FROM tbl_customer WHERE cust_id=?");
	$statement->execute(array($_REQUEST['id']));

	// Delete from tbl_rating
	$statement = $pdo->prepare("DELETE FROM tbl_rating WHERE cust_id=?");
	$statement->execute(array($_REQUEST['id']));

	header('location: customer.php');
?>