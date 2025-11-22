<?php
$pageTitle = 'Admin | Prepare Orders';
$currentPage = 'admin-prepare';
require_once __DIR__ . '/includes/functions.php';
requireAdmin($mysqli);

$pendingOrders = fetchOrdersByStatus($mysqli, ['pending']);
$preparingOrders = fetchOrdersByStatus($mysqli, ['preparing']);
$status = $_GET['status'] ?? null;
require_once __DIR__ . '/includes/header.php';
?>
<section>
    <div class="section-header">
        <h2>Prepare Orders</h2>
        <p>Review new orders and mark them as preparing when the kitchen starts working on them.</p>
    </div>
    <div class="admin-toolbar" style="margin-bottom:16px;">
        <form method="post" action="admin_clear_orders.php" onsubmit="return confirm('Delete all pending and preparing orders?');">
            <input type="hidden" name="target" value="queue" />
            <input type="hidden" name="redirect" value="admin_prepare.php" />
            <button type="submit" class="btn btn-outline">Clear</button>
        </form>
    </div>

    <?php if ($status === 'updated'): ?>
        <div class="alert alert-success">Order has been marked as preparing.</div>
    <?php elseif ($status === 'failed'): ?>
        <div class="alert alert-danger">Could not update that order. Please try again.</div>
    <?php endif; ?>

    <div class="table-card">
        <table class="listing-table pending-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Type</th>
                    <th>Scheduled Time</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pendingOrders)): ?>
                <tr><td colspan="6">No pending orders at the moment.</td></tr>
            <?php else: ?>
                <?php foreach ($pendingOrders as $order): ?>
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
                        <td>
                            <?php if ($order['order_type'] === 'preorder' && !empty($order['scheduled_for'])): ?>
                                <?= date('M d, Y h:i A', strtotime($order['scheduled_for'])); ?>
                            <?php else: ?>
                                ASAP
                            <?php endif; ?>
                        </td>
                        <td>₱<?= number_format((float) $order['total_amount'], 2); ?></td>
                        <td>
                            <div class="btn-group">
                                <form method="post" action="admin_order_action.php" style="display:inline-block;">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>" />
                                    <input type="hidden" name="action" value="prepare" />
                                    <input type="hidden" name="redirect" value="admin_prepare.php" />
                                    <button type="submit" class="btn btn-sm">
                                        <i class="fas fa-utensils"></i> Start
                                    </button>
                                </form>
                                <form method="post" action="admin_order_action.php" style="display:inline-block;margin-left:4px;">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>" />
                                    <input type="hidden" name="action" value="done" />
                                    <input type="hidden" name="redirect" value="admin_prepare.php" />
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Done
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-card" style="margin-top:24px;">
        <h3>Currently Preparing</h3>
        <table class="listing-table preparing-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Type</th>
                    <th>Scheduled Time</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($preparingOrders)): ?>
                <tr><td colspan="6">No orders are being prepared right now.</td></tr>
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
                        <td>
                            <?php if ($order['order_type'] === 'preorder' && !empty($order['scheduled_for'])): ?>
                                <?= date('M d, Y h:i A', strtotime($order['scheduled_for'])); ?>
                            <?php else: ?>
                                ASAP
                            <?php endif; ?>
                        </td>
                        <td>₱<?= number_format((float) $order['total_amount'], 2); ?></td>
                        <td>
                            <form method="post" action="admin_order_action.php">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>" />
                                <input type="hidden" name="action" value="serve" />
                                <input type="hidden" name="redirect" value="admin_prepare.php" />
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Mark as Ready
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const message = 'Listing cleared.';
    document.querySelectorAll('[data-clear-target]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const selector = btn.dataset.clearTarget;
            if (!selector) {
                return;
            }
            document.querySelectorAll(selector).forEach((table) => {
                const columns = table.querySelector('thead tr')?.children.length || 1;
                table.querySelectorAll('tbody').forEach((tbody) => {
                    tbody.innerHTML = `<tr><td colspan="${columns}">${message}</td></tr>`;
                });
            });
        });
    });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
