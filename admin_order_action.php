<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin($mysqli);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_orders.php');
    exit;
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$action = $_POST['action'] ?? '';
$redirect = $_POST['redirect'] ?? 'admin_orders.php';
$separator = strpos($redirect, '?') === false ? '?' : '&';

$success = false;
$statusMap = [
    'prepare' => 'preparing',
    'serve' => 'ready',
    'complete' => 'completed',
    'cancel' => 'cancelled'
];

if ($orderId > 0) {
    if ($action === 'done') {
        // Mark as ready (done preparing)
        $success = updateOrderStatus($mysqli, $orderId, 'ready');
    } elseif (isset($statusMap[$action])) {
        // Handle other status changes
        $success = updateOrderStatus($mysqli, $orderId, $statusMap[$action]);
    }
}

// Add status parameter for success/failure feedback
$statusParam = $success ? 'updated' : 'failed';

// Add order_id parameter to maintain context
$redirectWithParams = $redirect . $separator . 'status=' . $statusParam . '&order_id=' . $orderId;

header('Location: ' . $redirectWithParams);
exit;
