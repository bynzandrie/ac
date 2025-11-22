<?php
$pageTitle = 'Admin | Serve Orders';
$currentPage = 'admin-serve';
require_once __DIR__ . '/includes/functions.php';
requireAdmin($mysqli);

$preparingOrders = fetchOrdersByStatus($mysqli, ['preparing']);
$readyOrders = fetchOrdersByStatus($mysqli, ['ready']);
$status = $_GET['status'] ?? null;
require_once __DIR__ . '/includes/header.php';
?>
<section>
    <div class="section-header">
        <h2>Serve Orders</h2>
        <p>Mark prepared meals as ready to notify customers for pickup.</p>
    </div>
    <div class="admin-toolbar" style="margin-bottom:16px;">
        <a class="btn btn-outline" href="admin_serve.php">Clear</a>
    </div>

    <?php if ($status === 'updated'): ?>
        <div class="alert alert-success">Order status updated.</div>
    <?php elseif ($status === 'failed'): ?>
        <div class="alert alert-danger">Could not update that order. Please try again.</div>
    <?php endif; ?>

    <div class="table-card">
        <h3>Currently Preparing</h3>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Type</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($preparingOrders)): ?>
                <tr><td colspan="6">No orders are being prepared yet.</td></tr>
            <?php else: ?>
                <?php foreach ($preparingOrders as $order): ?>
                    <?php $items = fetchOrderItems($mysqli, $order['id']); ?>
                    <tr>
                        <td>#<?= (int) $order['id']; ?></td>
                        <td><?= htmlspecialchars($order['full_name']); ?></td>
                        <td>
                            <?php foreach ($items as $item): ?>
                                <div><?= htmlspecialchars($item['name']); ?> × <?= (int) $item['quantity']; ?></div>
                            <?php endforeach; ?>
                        </td>
                        <td><?= ucfirst($order['order_type']); ?></td>
                        <td>₱<?= number_format((float) $order['total_amount'], 2); ?></td>
                        <td>
                            <form method="post" action="admin_order_action.php">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>" />
                                <input type="hidden" name="action" value="serve" />
                                <input type="hidden" name="redirect" value="admin_serve.php" />
                                <button type="submit" class="btn">Mark ready for pickup</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-card" style="margin-top:24px;">
        <h3>Ready for Pickup</h3>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Type</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($readyOrders)): ?>
                <tr><td colspan="6">No ready orders awaiting pickup.</td></tr>
            <?php else: ?>
                <?php foreach ($readyOrders as $order): ?>
                    <?php $items = fetchOrderItems($mysqli, $order['id']); ?>
                    <tr>
                        <td>#<?= (int) $order['id']; ?></td>
                        <td><?= htmlspecialchars($order['full_name']); ?></td>
                        <td>
                            <?php foreach ($items as $item): ?>
                                <div><?= htmlspecialchars($item['name']); ?> × <?= (int) $item['quantity']; ?></div>
                            <?php endforeach; ?>
                        </td>
                        <td><?= ucfirst($order['order_type']); ?></td>
                        <td>₱<?= number_format((float) $order['total_amount'], 2); ?></td>
                        <td>
                            <form method="post" action="admin_order_action.php">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>" />
                                <input type="hidden" name="action" value="complete" />
                                <input type="hidden" name="redirect" value="admin_serve.php" />
                                <button type="submit" class="btn btn-outline">Mark completed</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
