<?php
ob_start();
session_start();
require_once(__DIR__ . '/inc/config.php');

if (!isset($_SESSION['user'])) {
    header('location: login.php');
    exit;
}

if( !isset($_REQUEST['id']) || !isset($_REQUEST['task']) ) {
	header('location: logout.php');
	exit;
} else {
	// Check the id is valid or not
	$statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE id=?");
	$statement->execute(array($_REQUEST['id']));
	$pay = $statement->fetch(PDO::FETCH_ASSOC);
	if( !$pay ) {
		header('location: logout.php');
		exit;
	}
}

$task = $_REQUEST['task'];
$payment_id = $pay['payment_id'];
$old_status = $pay['payment_status'];

try {
    $pdo->beginTransaction();

    if ($task === 'Completed' && $old_status !== 'Completed') {
        // Update payment status
        $statement = $pdo->prepare("UPDATE tbl_payment SET payment_status=? WHERE id=?");
        $statement->execute(array('Completed', $_REQUEST['id']));

        // Decrement stock in tbl_product
        $statement_items = $pdo->prepare("SELECT product_id, quantity FROM tbl_order WHERE payment_id=?");
        $statement_items->execute(array($payment_id));
        $ordered_items = $statement_items->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ordered_items as $item) {
            $pid = (int)$item['product_id'];
            $qty = (int)$item['quantity'];
            $statement_stk = $pdo->prepare("UPDATE tbl_product SET p_qty = GREATEST(0, p_qty - ?) WHERE p_id = ?");
            $statement_stk->execute(array($qty, $pid));
        }

        // Update tbl_bakong_transaction if exists
        $statement_b = $pdo->prepare("UPDATE tbl_bakong_transaction SET payment_status='PAID', updated_at=NOW() WHERE payment_id=?");
        $statement_b->execute(array($payment_id));

    } elseif ($task === 'Pending' && $old_status === 'Completed') {
        // Return stock back
        $statement_items = $pdo->prepare("SELECT product_id, quantity FROM tbl_order WHERE payment_id=?");
        $statement_items->execute(array($payment_id));
        $ordered_items = $statement_items->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ordered_items as $item) {
            $pid = (int)$item['product_id'];
            $qty = (int)$item['quantity'];
            $statement_stk = $pdo->prepare("UPDATE tbl_product SET p_qty = p_qty + ? WHERE p_id = ?");
            $statement_stk->execute(array($qty, $pid));
        }

        $statement = $pdo->prepare("UPDATE tbl_payment SET payment_status=? WHERE id=?");
        $statement->execute(array('Pending', $_REQUEST['id']));

        $statement_b = $pdo->prepare("UPDATE tbl_bakong_transaction SET payment_status='PENDING', updated_at=NOW() WHERE payment_id=?");
        $statement_b->execute(array($payment_id));
    } else {
        $statement = $pdo->prepare("UPDATE tbl_payment SET payment_status=? WHERE id=?");
        $statement->execute(array($task, $_REQUEST['id']));
    }

    $pdo->commit();
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

header('location: order.php');
exit;
