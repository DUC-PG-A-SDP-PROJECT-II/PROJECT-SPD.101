<?php require_once('header.php'); ?>

<?php
if( !isset($_REQUEST['id']) || !isset($_REQUEST['task']) ) {
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

$task = ($_REQUEST['task'] == 1) ? 1 : 0;
$statement = $pdo->prepare("UPDATE tbl_rating SET status=? WHERE rt_id=?");
$statement->execute([$task, $_REQUEST['id']]);

header('location: review.php');
exit;
?>

