<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin($mysqli);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_orders.php');
    exit;
}

$target = $_POST['target'] ?? '';
$redirect = $_POST['redirect'] ?? 'admin_orders.php';

$scopes = [
    'all' => "DELETE FROM orders",
    'pending' => "DELETE FROM orders WHERE status = 'pending'",
    'preparing' => "DELETE FROM orders WHERE status = 'preparing'",
    'ready' => "DELETE FROM orders WHERE status = 'ready'",
    'queue' => "DELETE FROM orders WHERE status IN ('pending','preparing')",
    'active' => "DELETE FROM orders WHERE status IN ('pending','preparing','ready')",
];

$success = false;

if (isset($scopes[$target])) {
    $success = (bool) $mysqli->query($scopes[$target]);
}

$separator = strpos($redirect, '?') === false ? '?' : '&';
header('Location: ' . $redirect . $separator . 'clear=' . ($success ? 'success' : 'failed'));
exit;
