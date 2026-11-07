<?php
// api/cart-update-qty.php - Real-Time Quantity & Totals Update API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(dirname(__DIR__) . '/admin/inc/config.php');
require_once(dirname(__DIR__) . '/admin/inc/functions.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['cart_p_id']) || empty($_SESSION['cart_p_id'])) {
    echo json_encode([
        'status' => 'empty',
        'message' => 'Cart is currently empty.',
        'cart_item_count' => 0,
        'subtotal_raw' => 0,
        'subtotal_formatted' => format_price(0),
        'final_total_raw' => 0,
        'final_total_formatted' => format_price(0),
        'header_cart_total' => format_price(0)
    ]);
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : 'update_qty';

if ($action === 'update_qty') {
    $row_id = isset($_POST['row_id']) ? (int)$_POST['row_id'] : 0;
    $new_qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    // Resilient key resolution: direct key, product_id lookup, or positional index
    if ($row_id < 1 || !isset($_SESSION['cart_p_id'][$row_id])) {
        $found_key = null;
        if ($product_id > 0) {
            foreach ($_SESSION['cart_p_id'] as $k => $pid) {
                if ((int)$pid === $product_id) {
                    $found_key = $k;
                    break;
                }
            }
        }
        if ($found_key === null) {
            $keys = array_keys($_SESSION['cart_p_id']);
            if (isset($keys[$row_id - 1])) {
                $found_key = $keys[$row_id - 1];
            }
        }
        if ($found_key !== null) {
            $row_id = $found_key;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid cart item selected.'
            ]);
            exit;
        }
    }

    if ($new_qty < 1) {
        $new_qty = 1;
    }

    $actual_pid = (int)$_SESSION['cart_p_id'][$row_id];
    
    // Check available stock in database
    $st = $pdo->prepare("SELECT p_qty, p_name FROM tbl_product WHERE p_id = ?");
    $st->execute([$actual_pid]);
    $prod = $st->fetch(PDO::FETCH_ASSOC);

    $stock_max = $prod ? (int)$prod['p_qty'] : 999;
    $warning = '';

    if ($stock_max > 0 && $new_qty > $stock_max) {
        $new_qty = $stock_max;
        $warning = "Only {$stock_max} item(s) are available in stock.";
    }

    // Update quantity in session
    $_SESSION['cart_p_qty'][$row_id] = $new_qty;

    // Recalculate totals
    $cart_subtotal = 0;
    $cart_item_count = 0;
    foreach ($_SESSION['cart_p_qty'] as $k => $q) {
        $qty_k = (int)$q;
        $price_k = isset($_SESSION['cart_p_current_price'][$k]) ? (float)$_SESSION['cart_p_current_price'][$k] : 0;
        $cart_item_count += $qty_k;
        $cart_subtotal += ($price_k * $qty_k);
    }

    // Row total
    $unit_price = isset($_SESSION['cart_p_current_price'][$row_id]) ? (float)$_SESSION['cart_p_current_price'][$row_id] : 0;
    $row_total = $unit_price * $new_qty;

    // Recalculate coupon discount if present
    $discount = 0;
    $has_coupon = false;
    $coupon_code = '';
    if (isset($_SESSION['coupon']) && is_array($_SESSION['coupon'])) {
        $cpn = $_SESSION['coupon'];
        $coupon_code = $cpn['code'];
        if ($cpn['type'] === 'percentage') {
            $discount = round(($cart_subtotal * (float)$cpn['value']) / 100, 2);
        } else {
            $discount = min($cart_subtotal, (float)$cpn['value']);
        }
        $_SESSION['coupon']['discount'] = $discount;
        if ($discount > 0) {
            $has_coupon = true;
        }
    }

    $final_total = max(0, $cart_subtotal - $discount);

    // Formatted strings honoring active currency
    $row_total_formatted = format_price($row_total);
    $subtotal_formatted = format_price($cart_subtotal);
    $discount_formatted = ($discount > 0) ? '-' . format_price($discount) : '';
    $final_total_formatted = format_price($final_total);
    $final_total_usd = format_price_usd($final_total);
    $final_total_khr = format_price_khr($final_total);

    echo json_encode([
        'status' => 'success',
        'row_id' => $row_id,
        'qty' => $new_qty,
        'stock_max' => $stock_max,
        'warning' => $warning,
        'unit_price' => $unit_price,
        'row_total_raw' => $row_total,
        'row_total_formatted' => $row_total_formatted,
        'subtotal_raw' => $cart_subtotal,
        'subtotal_formatted' => $subtotal_formatted,
        'discount_raw' => $discount,
        'discount_formatted' => $discount_formatted,
        'has_coupon' => $has_coupon,
        'coupon_code' => $coupon_code,
        'final_total_raw' => $final_total,
        'final_total_formatted' => $final_total_formatted,
        'final_total_usd' => $final_total_usd,
        'final_total_khr' => $final_total_khr,
        'cart_item_count' => $cart_item_count,
        'header_cart_total' => $final_total_formatted,
        'currency' => get_current_currency()
    ]);
    exit;
}

if ($action === 'remove_item') {
    $row_id = isset($_POST['row_id']) ? (int)$_POST['row_id'] : 0;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    // Resolve key to remove
    $target_key = null;
    if (isset($_SESSION['cart_p_id'][$row_id])) {
        $target_key = $row_id;
    } elseif ($product_id > 0) {
        foreach ($_SESSION['cart_p_id'] as $k => $pid) {
            if ((int)$pid === $product_id) {
                $target_key = $k;
                break;
            }
        }
    } else {
        $keys = array_keys($_SESSION['cart_p_id']);
        if (isset($keys[$row_id - 1])) {
            $target_key = $keys[$row_id - 1];
        }
    }

    if ($target_key === null) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Cart item not found.'
        ]);
        exit;
    }

    // Direct unset by exact key
    unset($_SESSION['cart_p_id'][$target_key]);
    unset($_SESSION['cart_size_id'][$target_key]);
    unset($_SESSION['cart_size_name'][$target_key]);
    unset($_SESSION['cart_color_id'][$target_key]);
    unset($_SESSION['cart_color_name'][$target_key]);
    unset($_SESSION['cart_p_qty'][$target_key]);
    unset($_SESSION['cart_p_current_price'][$target_key]);
    unset($_SESSION['cart_p_name'][$target_key]);
    unset($_SESSION['cart_p_featured_photo'][$target_key]);

    // Re-index remaining items cleanly 1..N
    $new_cart_p_id = [];
    $new_cart_size_id = [];
    $new_cart_size_name = [];
    $new_cart_color_id = [];
    $new_cart_color_name = [];
    $new_cart_p_qty = [];
    $new_cart_p_current_price = [];
    $new_cart_p_name = [];
    $new_cart_p_featured_photo = [];

    $idx = 1;
    foreach ($_SESSION['cart_p_id'] as $k => $val) {
        $new_cart_p_id[$idx] = $_SESSION['cart_p_id'][$k];
        $new_cart_size_id[$idx] = $_SESSION['cart_size_id'][$k];
        $new_cart_size_name[$idx] = $_SESSION['cart_size_name'][$k];
        $new_cart_color_id[$idx] = $_SESSION['cart_color_id'][$k];
        $new_cart_color_name[$idx] = $_SESSION['cart_color_name'][$k];
        $new_cart_p_qty[$idx] = $_SESSION['cart_p_qty'][$k];
        $new_cart_p_current_price[$idx] = $_SESSION['cart_p_current_price'][$k];
        $new_cart_p_name[$idx] = $_SESSION['cart_p_name'][$k];
        $new_cart_p_featured_photo[$idx] = $_SESSION['cart_p_featured_photo'][$k];
        $idx++;
    }

    $_SESSION['cart_p_id'] = $new_cart_p_id;
    $_SESSION['cart_size_id'] = $new_cart_size_id;
    $_SESSION['cart_size_name'] = $new_cart_size_name;
    $_SESSION['cart_color_id'] = $new_cart_color_id;
    $_SESSION['cart_color_name'] = $new_cart_color_name;
    $_SESSION['cart_p_qty'] = $new_cart_p_qty;
    $_SESSION['cart_p_current_price'] = $new_cart_p_current_price;
    $_SESSION['cart_p_name'] = $new_cart_p_name;
    $_SESSION['cart_p_featured_photo'] = $new_cart_p_featured_photo;

    $is_empty = empty($_SESSION['cart_p_id']);

    if ($is_empty) {
        if (isset($_SESSION['coupon'])) {
            unset($_SESSION['coupon']);
        }
        echo json_encode([
            'status' => 'empty',
            'message' => 'Cart is now empty.',
            'cart_item_count' => 0,
            'subtotal_formatted' => format_price(0),
            'final_total_formatted' => format_price(0),
            'header_cart_total' => format_price(0)
        ]);
        exit;
    }

    // Recalculate
    $cart_subtotal = 0;
    $cart_item_count = 0;
    foreach ($_SESSION['cart_p_qty'] as $k => $q) {
        $cart_item_count += (int)$q;
        $cart_subtotal += ((float)$_SESSION['cart_p_current_price'][$k] * (int)$q);
    }

    $discount = 0;
    if (isset($_SESSION['coupon']) && is_array($_SESSION['coupon'])) {
        $cpn = $_SESSION['coupon'];
        if ($cpn['type'] === 'percentage') {
            $discount = round(($cart_subtotal * (float)$cpn['value']) / 100, 2);
        } else {
            $discount = min($cart_subtotal, (float)$cpn['value']);
        }
        $_SESSION['coupon']['discount'] = $discount;
    }

    $final_total = max(0, $cart_subtotal - $discount);

    echo json_encode([
        'status' => 'success',
        'is_empty' => false,
        'cart_item_count' => $cart_item_count,
        'subtotal_raw' => $cart_subtotal,
        'subtotal_formatted' => format_price($cart_subtotal),
        'discount_raw' => $discount,
        'discount_formatted' => ($discount > 0) ? '-' . format_price($discount) : '',
        'has_coupon' => ($discount > 0),
        'final_total_raw' => $final_total,
        'final_total_formatted' => format_price($final_total),
        'final_total_usd' => format_price_usd($final_total),
        'final_total_khr' => format_price_khr($final_total),
        'header_cart_total' => format_price($final_total)
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);

