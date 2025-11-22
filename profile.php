<?php
$pageTitle = 'Profile | CanteenHub';
$currentPage = 'profile';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser($mysqli);
$orders = fetchOrdersForUser($mysqli, $user['id']);
$notifications = getUserNotifications($mysqli, $user['id']);

// Mark notifications as read when viewing profile
if (!empty($notifications)) {
    markNotificationsRead($mysqli, $user['id']);
}

require_once __DIR__ . '/includes/header.php';
?>
<section>
    <div class="section-header">
        <h2>Profile</h2>
        <p>Review your details, notifications, and recent orders.</p>
    </div>

    <?php if (!empty($notifications)): ?>
    <div class="notifications-container" style="margin-bottom: 24px;">
        <h3>Notifications</h3>
        <div class="notifications-list">
            <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
                <div class="notification-item" style="
                    background: #f8f9fa;
                    border-left: 4px solid #ff7b54;
                    padding: 12px 16px;
                    margin-bottom: 8px;
                    border-radius: 4px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;">
                    <div style="flex: 1; margin-right: 10px;">
                        <?= htmlspecialchars($notification['message']) ?>
                    </div>
                    <div style="white-space: nowrap;">
                        <small class="text-muted"><?= date('M d, h:i A', strtotime($notification['created_at'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($notifications) > 5): ?>
                <div class="text-center" style="margin-top: 8px;">
                    <a href="#" class="btn btn-sm btn-outline">View All Notifications</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="admin-toolbar" style="margin-bottom:16px;">
        <form method="post" action="clear_user_orders.php" onsubmit="return confirm('Are you sure you want to delete all your orders? This cannot be undone.');">
            <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">
            <button type="submit" class="btn btn-outline">Clear orders</button>
        </form>
    </div>

    <div class="profile-card">
        <h3><?= htmlspecialchars($user['full_name']); ?></h3>
        <p><?= htmlspecialchars($user['email']); ?></p>
        <p>Member since <?= date('M d, Y', strtotime($user['created_at'])); ?></p>
    </div>

    <div class="table-card">
        <table class="profile-orders listing-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Type</th>
                    <th>Scheduled</th>
                    <th>Status</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $orderCounter = 1;
                foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= $orderCounter++; ?></td>
                        <td><?= ucfirst($order['order_type']); ?></td>
                        <td><?= $order['scheduled_for'] ? date('M d, H:i', strtotime($order['scheduled_for'])) : 'N/A'; ?></td>
                        <td><?= ucfirst($order['status']); ?></td>
                        <td>₱<?= number_format((float) $order['total_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <strong>Items:</strong>
                            <ul>
                                <?php foreach ($order['items'] as $item): ?>
                                    <li><?= htmlspecialchars($item['name']); ?> × <?= (int) $item['quantity']; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php if (isset($_GET['cleared']) && $_GET['cleared'] === '1'): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const message = 'Your orders have been deleted.';
        document.querySelectorAll('.profile-orders tbody').forEach((tbody) => {
            const columns = document.querySelector('.profile-orders thead tr')?.children.length || 1;
            tbody.innerHTML = `<tr><td colspan="${columns}">${message}</td></tr>`;
        });
    });
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
