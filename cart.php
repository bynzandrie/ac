<?php
$pageTitle = 'Finalize Order | CanteenHub';
$currentPage = 'cart';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$alert = null;
$user = currentUser($mysqli);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $itemId => $qty) {
            updateCartItem((int) $itemId, max(0, (int) $qty));
        }
        $alert = ['type' => 'success', 'message' => 'Cart updated'];
    }
    elseif (isset($_POST['clear_cart'])) {
        clearCart();
        $alert = ['type' => 'success', 'message' => 'Cart cleared'];
    }
    elseif (isset($_POST['place_order'])) {
        $orderType = ($_POST['order_type'] ?? 'immediate') === 'preorder' ? 'preorder' : 'immediate';
        $scheduleRaw = trim($_POST['scheduled_for'] ?? '');
        $schedule = $scheduleRaw !== '' ? $scheduleRaw : null;

        if ($orderType === 'preorder' && !$schedule) {
            $alert = ['type' => 'danger', 'message' => 'Please select a pickup date/time for your pre-order.'];
        } else {
            $orderId = placeOrder($orderType, $schedule, $mysqli);
            
            if ($orderId) {
                clearCart();
                header('Location: profile.php?order_placed=1');
                exit;
            } else {
                $alert = ['type' => 'danger', 'message' => 'Could not place order. Please try again.'];
            }
        }
    }
}

$totals = calculateCartTotals();
require_once __DIR__ . '/includes/header.php';
?>
<section>
    <div class="section-header">
        <h2>Finalize Your Order</h2>
        <p>Review your items and complete your purchase.</p>
    </div>

    <?php if ($alert): ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type']); ?>"><?= htmlspecialchars($alert['message']); ?></div>
    <?php endif; ?>

    <?php if (empty($totals['items'])): ?>
        <p>Your cart is empty. Explore the <a class="link" href="menu.php">menu</a>.</p>
    <?php else: ?>
        <form method="post" class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th width="100">Qty</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($totals['items'] as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['name']); ?></strong>
                                <p class="muted">₱<?= number_format($item['price'], 2); ?></p>
                            </td>
                            <td>
                                <input type="number" name="quantities[<?= (int) $item['id']; ?>]" min="0" value="<?= (int) $item['quantity']; ?>" />
                            </td>
                            <td>₱<?= number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="cart-actions">
                <div class="form-group">
                    <label>Order Type</label>
                    <div class="radio-group" style="display: flex; gap: 16px; margin: 8px 0;">
                        <label class="radio-label">
                            <input type="radio" name="order_type" value="immediate" checked onchange="toggleScheduleField(false)">
                            <span>Immediate</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="order_type" value="preorder" onchange="toggleScheduleField(true)">
                            <span>Pre-order</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" id="schedule-field" style="display: none; margin: 16px 0;">
                    <label for="scheduled_for">Pickup Date/Time</label>
                    <input type="datetime-local" id="scheduled_for" name="scheduled_for" 
                           min="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>" 
                           value="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
                    <small class="muted">Select when you'd like to pick up your order</small>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 20px;">
                    <button class="btn" type="submit" name="update_cart">Update Cart</button>
                    <button class="btn btn-outline" type="submit" name="clear_cart">Clear Cart</button>
                    <button class="btn btn-primary" type="submit" name="place_order" style="margin-left: auto;">
                        Place Order
                    </button>
                </div>
            </div>
        </form>

        <div class="form-card" style="margin-top: 24px;">
            <h3>Summary</h3>
            <p>Subtotal: <strong>₱<?= number_format($totals['subtotal'], 2); ?></strong></p>
        </div>
    <?php endif; ?>
</section>
<script>
function toggleScheduleField(show) {
    const scheduleField = document.getElementById('schedule-field');
    const scheduleInput = document.getElementById('scheduled_for');
    scheduleField.style.display = show ? 'block' : 'none';
    if (show) {
        scheduleInput.required = true;
    } else {
        scheduleInput.required = false;
    }
}

// Auto-hide alert after 3 seconds
document.addEventListener('DOMContentLoaded', () => {
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 3000);
    }
});
</script>

<style>
.radio-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.radio-label input[type="radio"] {
    margin: 0;
}

.btn-primary {
    background-color: var(--primary);
    color: white;
    border: 1px solid var(--primary);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
