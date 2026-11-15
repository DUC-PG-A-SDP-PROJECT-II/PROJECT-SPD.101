<?php require_once('header.php'); ?>

<?php
if(!isset($_REQUEST['id'])) {
	header('location: review.php');
	exit;
} else {
	$statement = $pdo->prepare("SELECT * FROM tbl_rating WHERE rt_id=?");
	$statement->execute([$_REQUEST['id']]);
	if($statement->rowCount() == 0) {
		header('location: review.php');
		exit;
	}
}

$statement = $pdo->prepare("DELETE FROM tbl_rating WHERE rt_id=?");
$statement->execute([$_REQUEST['id']]);

header('location: review.php');
exit;
?>

