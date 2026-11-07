<?php
// Wishlist API
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once('admin/inc/config.php');

if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'count';
$product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : (isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0);

if ($action === 'count') {
    echo json_encode([
        'success' => true,
        'count'   => count($_SESSION['wishlist'])
    ]);
    exit;
}

if ($product_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID'
    ]);
    exit;
}

// Fetch product name to provide a friendly message
$stmt = $pdo->prepare("SELECT p_name FROM tbl_product WHERE p_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found'
    ]);
    exit;
}

$product_name = $product['p_name'];

if ($action === 'add') {
    if (!in_array($product_id, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = $product_id;
    }
    echo json_encode([
        'success'     => true,
        'in_wishlist' => true,
        'count'       => count($_SESSION['wishlist']),
        'message'     => 'Added "' . $product_name . '" to your Wishlist!'
    ]);
    exit;
}

if ($action === 'remove') {
    if (($key = array_search($product_id, $_SESSION['wishlist'])) !== false) {
        unset($_SESSION['wishlist'][$key]);
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
    }
    echo json_encode([
        'success'     => true,
        'in_wishlist' => false,
        'count'       => count($_SESSION['wishlist']),
        'message'     => 'Removed "' . $product_name . '" from your Wishlist.'
    ]);
    exit;
}

if ($action === 'toggle') {
    if (in_array($product_id, $_SESSION['wishlist'])) {
        if (($key = array_search($product_id, $_SESSION['wishlist'])) !== false) {
            unset($_SESSION['wishlist'][$key]);
            $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
        }
        $in_wishlist = false;
        $message = 'Removed from Wishlist';
    } else {
        $_SESSION['wishlist'][] = $product_id;
        $in_wishlist = true;
        $message = 'Added "' . $product_name . '" to your Wishlist!';
    }
    echo json_encode([
        'success'     => true,
        'in_wishlist' => $in_wishlist,
        'count'       => count($_SESSION['wishlist']),
        'message'     => $message
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
