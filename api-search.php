<?php
ob_start();
session_start();
// Live Search Suggestion API
header('Content-Type: application/json; charset=utf-8');
require_once('admin/inc/config.php');
require_once('admin/inc/functions.php');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$search_term = '%' . $query . '%';

if ($category_id > 0) {
    // Search with top category filter
    $sql = "SELECT p.p_id, p.p_name, p.p_featured_photo, p.p_current_price, p.p_old_price, p.p_qty,
                   e.ecat_name, m.mcat_name, t.tcat_name
            FROM tbl_product p
            LEFT JOIN tbl_end_category e ON p.ecat_id = e.ecat_id
            LEFT JOIN tbl_mid_category m ON e.mcat_id = m.mcat_id
            LEFT JOIN tbl_top_category t ON m.tcat_id = t.tcat_id
            WHERE p.p_is_active = 1 
              AND (p.p_name LIKE :term1 OR p.p_short_description LIKE :term2 OR e.ecat_name LIKE :term3)
              AND t.tcat_id = :cat_id
            ORDER BY p.p_id DESC
            LIMIT 6";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':term1'  => $search_term,
        ':term2'  => $search_term,
        ':term3'  => $search_term,
        ':cat_id' => $category_id
    ]);
} else {
    // Search across all products
    $sql = "SELECT p.p_id, p.p_name, p.p_featured_photo, p.p_current_price, p.p_old_price, p.p_qty,
                   e.ecat_name, m.mcat_name, t.tcat_name
            FROM tbl_product p
            LEFT JOIN tbl_end_category e ON p.ecat_id = e.ecat_id
            LEFT JOIN tbl_mid_category m ON e.mcat_id = m.mcat_id
            LEFT JOIN tbl_top_category t ON m.tcat_id = t.tcat_id
            WHERE p.p_is_active = 1 
              AND (p.p_name LIKE :term1 OR p.p_short_description LIKE :term2 OR e.ecat_name LIKE :term3)
            ORDER BY p.p_id DESC
            LIMIT 6";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':term1' => $search_term,
        ':term2' => $search_term,
        ':term3' => $search_term
    ]);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$results = [];

foreach ($rows as $row) {
    $results[] = [
        'id'            => $row['p_id'],
        'title'         => $row['p_name'],
        'photo'         => $row['p_featured_photo'],
        'price'         => format_price($row['p_current_price']),
        'old_price'     => !empty($row['p_old_price']) ? format_price($row['p_old_price']) : null,
        'category'      => $row['ecat_name'] ?? ($row['mcat_name'] ?? 'Product'),
        'in_stock'      => ($row['p_qty'] > 0),
        'url'           => 'product.php?id=' . $row['p_id']
    ];
}

echo json_encode(['results' => $results]);

