<?php
require_once __DIR__ . '/includes/functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

// Require user to be logged in
requireLogin();
$currentUser = currentUser($mysqli);
$userId = (int)($_POST['user_id'] ?? 0);

// Verify the user is only deleting their own orders
if ($userId !== (int)$currentUser['id']) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'You can only delete your own orders.'
    ];
    header('Location: profile.php');
    exit;
}

// Start transaction
$mysqli->begin_transaction();

try {
    // First delete all order items for this user's orders
    $stmt = $mysqli->prepare("
        DELETE oi FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ?
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    // Then delete the orders themselves
    $stmt = $mysqli->prepare("DELETE FROM orders WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    // Commit the transaction
    $mysqli->commit();
    
    // Redirect back with success message
    header('Location: profile.php?cleared=1');
    exit;
} catch (Exception $e) {
    // Rollback on error
    $mysqli->rollback();
    
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'An error occurred while deleting your orders. Please try again.'
    ];
    header('Location: profile.php');
    exit;
}
