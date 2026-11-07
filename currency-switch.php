<?php
ob_start();
session_start();

$allowed_currencies = ['USD', 'KHR'];
$allowed_languages = ['EN', 'KH'];

if (isset($_REQUEST['currency']) && in_array(strtoupper($_REQUEST['currency']), $allowed_currencies)) {
    $_SESSION['currency'] = strtoupper($_REQUEST['currency']);
}

if (isset($_REQUEST['lang']) && in_array(strtoupper($_REQUEST['lang']), $allowed_languages)) {
    $_SESSION['site_lang'] = strtoupper($_REQUEST['lang']);
}

// Return JSON if requested via AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_REQUEST['ajax'])) {
    @include_once('admin/inc/config.php');
    @include_once('admin/inc/functions.php');
    $current_rate = function_exists('get_usd_to_khr_rate') ? get_usd_to_khr_rate() : 4100;
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'currency' => $_SESSION['currency'] ?? 'USD',
        'lang' => $_SESSION['site_lang'] ?? 'EN',
        'rate' => $current_rate
    ]);
    exit;
}

// Otherwise redirect back
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
// Basic host check to avoid open redirect vulnerabilities
$host = parse_url($referer, PHP_URL_HOST);
if ($host && $host !== $_SERVER['HTTP_HOST']) {
    $referer = 'index.php';
}

header('Location: ' . $referer);
exit;

