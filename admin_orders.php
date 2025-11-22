<?php
$pageTitle = 'Admin | View Orders';
$currentPage = 'admin-orders';
require_once __DIR__ . '/includes/functions.php';
requireAdmin($mysqli);

$orders = fetchRecentOrders($mysqli, 25);
require_once __DIR__ . '/includes/header.php';
?>
<section>
    <div class="section-header">
        <h2>Recent Orders</h2>
        <p>All student orders in chronological order. Use this to review activity.</p>
    </div>
    <div class="admin-toolbar" style="margin-bottom:16px;">
        <form method="post" action="admin_clear_orders.php" onsubmit="return confirm('Delete all orders? This cannot be undone.');">
            <input type="hidden" name="target" value="all" />
            <input type="hidden" name="redirect" value="admin_orders.php" />
            <button type="submit" class="btn btn-outline">Clear</button>
        </form>
    </div>

    <div class="table-card">
        <table class="listing-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Scheduled For</th>
                    <th>Status</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="6">No orders found.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= (int) $order['id']; ?></td>
                        <td><?= htmlspecialchars($order['full_name']); ?></td>
                        <td><?= ucfirst($order['order_type']); ?></td>
                        <td><?= $order['scheduled_for'] ? date('M d, H:i', strtotime($order['scheduled_for'])) : 'N/A'; ?></td>
                        <td><?= ucfirst($order['status']); ?></td>
                        <td>₱<?= number_format((float) $order['total_amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
