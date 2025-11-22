<?php
$pageTitle = 'Admin Dashboard | CanteenHub';
$currentPage = 'admin';
require_once __DIR__ . '/includes/functions.php';
requireAdmin($mysqli);

$stats = fetchAdminStats($mysqli);
require_once __DIR__ . '/includes/header.php';
?>
<section>
    <div class="section-header">
        <h2>Admin Dashboard</h2>
        <p>Overview of today’s performance and recent activity.</p>
    </div>

    <div class="dashboard-grid">
        <div class="stat-card">
            <h4>Total Users</h4>
            <div class="value"><?= number_format($stats['total_users']); ?></div>
            <small>Students registered</small>
        </div>
        <div class="stat-card">
            <h4>Total Orders</h4>
            <div class="value"><?= number_format($stats['total_orders']); ?></div>
            <small>All-time</small>
        </div>
        <div class="stat-card">
            <h4>Pending Orders</h4>
            <div class="value"><?= number_format($stats['pending_orders']); ?></div>
            <small>Awaiting approval</small>
        </div>
        <div class="stat-card">
            <h4>Revenue Today</h4>
            <div class="value">₱<?= number_format($stats['today_revenue'], 2); ?></div>
            <small>Based on confirmed sales</small>
        </div>
    </div>

    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="admin_menu.php" class="btn btn-primary">
                <i class="fas fa-utensils"></i> Manage Menu
            </a>
            <a href="admin_orders.php" class="btn btn-secondary">
                <i class="fas fa-shopping-cart"></i> View Orders
            </a>
            <a href="admin_clear_orders.php" class="btn btn-danger" onclick="return confirm('Are you sure you want to clear all orders?')">
                <i class="fas fa-trash"></i> Clear All Orders
            </a>
        </div>
    </div>

    <div class="grid-2">
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Order #</th>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stats['latest_orders'])): ?>
                        <tr><td colspan="4">No orders yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stats['latest_orders'] as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['full_name']); ?></td>
                                <td>#<?= (int) $order['id']; ?></td>
                                <td><?= ucfirst($order['status']); ?></td>
                                <td>₱<?= number_format((float) $order['total_amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Top Menu Items</th>
                        <th>Qty Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stats['top_items'])): ?>
                        <tr><td colspan="2">No sales yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stats['top_items'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']); ?></td>
                                <td><?= number_format($item['total_qty']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
