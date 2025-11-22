<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/../config/db.php';

// Verify database connection
if (!isset($mysqli) || !($mysqli instanceof mysqli) || $mysqli->connect_error) {
    die("Database connection failed: " . ($mysqli->connect_error ?? "Database connection not properly initialized"));
}

// Now it's safe to call ensureDefaultAdmin
ensureDefaultAdmin($mysqli);

// Check for remember me cookie and log in if valid
if (!isLoggedIn() && !empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $mysqli->prepare('SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($userToken = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $userToken['user_id'];
        // Refresh the token for security
        setRememberMeToken($mysqli, $userToken['user_id']);
    } else {
        // Invalid token, clear the cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    $stmt->close();
}

// Rest of your functions...
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function ensureDefaultAdmin(mysqli $mysqli): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $fullName = 'Canteen Admin';
    $defaultEmail = 'admin@canteenhub.local';
    $defaultPassword = 'AdminPass123!';

    $stmt = $mysqli->prepare('SELECT id, role FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $defaultEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if ($existing) {
        if (($existing['role'] ?? 'customer') !== 'admin') {
            $update = $mysqli->prepare('UPDATE users SET role = "admin" WHERE id = ?');
            $update->bind_param('i', $existing['id']);
            $update->execute();
            $update->close();
        }
    } else {
        $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);
        $insert = $mysqli->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, "admin")');
        $insert->bind_param('sss', $fullName, $defaultEmail, $passwordHash);
        $insert->execute();
        $insert->close();
    }

    $ensured = true;
}

ensureDefaultAdmin($mysqli);

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(mysqli $mysqli): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    static $cachedUser = null;
    if ($cachedUser !== null) {
        return $cachedUser;
    }

    $stmt = $mysqli->prepare('SELECT id, full_name, email, role, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $cachedUser = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $cachedUser;
}

function requireLogin(string $message = 'Please sign in to continue'): void
{
    if (!isLoggedIn()) {
        $_SESSION['login_required_message'] = $message;
        header('Location: signin.php');
        exit;
    }
}

/**
 * Set remember me token for a user
 */
function setRememberMeToken(mysqli $mysqli, int $userId): void
{
    // Generate a secure token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Store in database
    $stmt = $mysqli->prepare('
        INSERT INTO user_tokens (user_id, token, expires_at)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            token = VALUES(token),
            expires_at = VALUES(expires_at)
    ');
    $stmt->bind_param('iss', $userId, $token, $expires);
    $stmt->execute();
    $stmt->close();
    
    // Set cookie (30 days expiration)
    setcookie('remember_token', $token, [
        'expires' => time() + (30 * 24 * 3600),
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => isset($_SERVER['HTTPS'])
    ]);
}

/**
 * Clear remember me token for current user
 */
function clearRememberMeToken(mysqli $mysqli): void
{
    if (empty($_COOKIE['remember_token'])) {
        return;
    }
    
    $token = $_COOKIE['remember_token'];
    $stmt = $mysqli->prepare('DELETE FROM user_tokens WHERE token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->close();
    
    // Clear the cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

function isAdminUser(mysqli $mysqli): bool
{
    $user = currentUser($mysqli);
    return $user && ($user['role'] ?? 'customer') === 'admin';
}

function requireAdmin(mysqli $mysqli = null): void
{
    global $mysqli;
    
    // If $mysqli is not provided as parameter, try to get it from global scope
    if ($mysqli === null && !isset($mysqli)) {
        require_once __DIR__ . '/../config/db.php';
    }
    
    if (!isLoggedIn()) {
        $_SESSION['login_required_message'] = 'Please log in to access the admin panel.';
        header('Location: signin.php');
        exit;
    }
    
    if (!isAdminUser($mysqli)) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: index.php');
        exit;
    }
}

function fetchMenuItems(mysqli $mysqli, bool $includeUnavailable = false)
{
    $items = [];
    try {
        // Check database connection
        if ($mysqli->connect_error) {
            throw new Exception("Database connection failed: " . $mysqli->connect_error);
        }

        // Prepare the SQL query
        $sql = 'SELECT * FROM menu_items' . ($includeUnavailable ? '' : ' WHERE is_available = 1') . ' ORDER BY category, name';
        
        // Execute the query
        $result = $mysqli->query($sql);
        
        // Check for query errors
        if ($result === false) {
            throw new Exception("Query failed: " . $mysqli->error);
        }
        
        // Fetch all rows
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        // Log the number of items found (for debugging)
        error_log("Fetched " . count($items) . " menu items from database");
        
        return $items;
    } catch (Exception $e) {
        // Log the error
        error_log("Error in fetchMenuItems: " . $e->getMessage());
        
        // Return empty array on error
        return [];
    }
}

function getMenuItem(mysqli $mysqli, int $id): ?array
{
    $stmt = $mysqli->prepare('SELECT * FROM menu_items WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}

function saveMenuItem(mysqli $mysqli, array $data, ?array $file = null): ?int
{
    // Validate required fields
    if (empty($data['name']) || !isset($data['price']) || $data['price'] < 0) {
        return null;
    }

    $id = $data['id'] ?? null;
    $name = trim($data['name']);
    $description = trim($data['description'] ?? '');
    $price = (float)$data['price'];
    $category = in_array($data['category'] ?? '', ['Food', 'Drink']) ? $data['category'] : 'Food';
    $isAvailable = isset($data['is_available']) ? (int)(bool)$data['is_available'] : 1;

    // Handle file upload if a new image is provided
    $imageUrl = $data['current_image'] ?? '';
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/img/food/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExt, $allowedExts)) {
            $newFileName = uniqid('item_') . '.' . $fileExt;
            $targetPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Delete old image if it exists and is not the default
                if ($imageUrl && strpos($imageUrl, 'default') === false) {
                    $oldImagePath = $uploadDir . basename($imageUrl);
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }
                $imageUrl = 'assets/img/food/' . $newFileName;
            }
        }
    }

    if ($id) {
        // Update existing item
        $stmt = $mysqli->prepare('UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, is_available = ?, image_url = ? WHERE id = ?');
        $stmt->bind_param('ssdsisi', $name, $description, $price, $category, $isAvailable, $imageUrl, $id);
        $stmt->execute();
        return $stmt->affected_rows > 0 ? $id : null;
    } else {
        // Insert new item
        $stmt = $mysqli->prepare('INSERT INTO menu_items (name, description, price, category, is_available, image_url) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssdsis', $name, $description, $price, $category, $isAvailable, $imageUrl);
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        return null;
    }
}

function deleteMenuItem(mysqli $mysqli, int $id): bool
{
    // First get the image URL to delete the file
    $item = getMenuItem($mysqli, $id);
    if ($item && !empty($item['image_url']) && strpos($item['image_url'], 'default') === false) {
        $imagePath = __DIR__ . '/../' . $item['image_url'];
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }
    
    $stmt = $mysqli->prepare('DELETE FROM menu_items WHERE id = ?');
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

function addToCart(int $menuItemId, int $quantity, mysqli $mysqli): void
{
    // Check if user is logged in
    if (!isLoggedIn()) {
        $_SESSION['login_required_message'] = 'Please sign in to add items to your cart.';
        header('Location: signin.php');
        exit;
    }

    $stmt = $mysqli->prepare('SELECT id, name, price, category, image_url FROM menu_items WHERE id = ? AND is_available = 1');
    $stmt->bind_param('i', $menuItemId);
    $stmt->execute();
    $menuItem = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$menuItem) {
        return;
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$menuItemId])) {
        $_SESSION['cart'][$menuItemId]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$menuItemId] = [
            'id' => $menuItem['id'],
            'name' => $menuItem['name'],
            'price' => (float) $menuItem['price'],
            'category' => $menuItem['category'],
            'image_url' => $menuItem['image_url'],
            'quantity' => $quantity,
        ];
    }
}

function setOrderPreferences(string $orderType, ?string $schedule): void
{
    $_SESSION['order_preferences'] = [
        'order_type' => $orderType,
        'scheduled_for' => $schedule,
    ];
}

function getOrderPreferences(): array
{
    return $_SESSION['order_preferences'] ?? [
        'order_type' => 'immediate',
        'scheduled_for' => null,
    ];
}

function clearOrderPreferences(): void
{
    unset($_SESSION['order_preferences']);
}

function getCartItems(): array
{
    return $_SESSION['cart'] ?? [];
}

function updateCartItem(int $menuItemId, int $quantity): void
{
    if (!isset($_SESSION['cart'][$menuItemId])) {
        return;
    }

    if ($quantity <= 0) {
        unset($_SESSION['cart'][$menuItemId]);
    } else {
        $_SESSION['cart'][$menuItemId]['quantity'] = $quantity;
    }
}

function clearCart(): void
{
    unset($_SESSION['cart']);
}

function calculateCartTotals(): array
{
    $items = getCartItems();
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    return [
        'items' => $items,
        'subtotal' => $subtotal,
    ];
}

function placeOrder(string $orderType, ?string $schedule, mysqli $mysqli): ?int
{
    $cart = getCartItems();
    if (empty($cart) || !isLoggedIn()) {
        return null;
    }

    $totals = calculateCartTotals();
    $scheduledFor = ($orderType === 'preorder' && $schedule) ? $schedule : null;

    $stmt = $mysqli->prepare('INSERT INTO orders (user_id, order_type, scheduled_for, total_amount) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('issd', $_SESSION['user_id'], $orderType, $scheduledFor, $totals['subtotal']);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    $itemStmt = $mysqli->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, price_each) VALUES (?, ?, ?, ?)');
    foreach ($cart as $item) {
        $itemStmt->bind_param('iiid', $orderId, $item['id'], $item['quantity'], $item['price']);
        $itemStmt->execute();
    }
    $itemStmt->close();

    clearCart();
    return $orderId;
}

function fetchOrdersForUser(mysqli $mysqli, int $userId): array
{
    $orders = [];
    $stmt = $mysqli->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $ordersResult = $stmt->get_result();

    while ($order = $ordersResult->fetch_assoc()) {
        $itemStmt = $mysqli->prepare('SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON mi.id = oi.menu_item_id WHERE oi.order_id = ?');
        $itemStmt->bind_param('i', $order['id']);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();
        $order['items'] = $itemResult->fetch_all(MYSQLI_ASSOC);
        $itemStmt->close();
        $orders[] = $order;
    }

    $stmt->close();
    return $orders;
}

function fetchOrderDetails(mysqli $mysqli, int $orderId, int $userId): ?array
{
    $stmt = $mysqli->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        return null;
    }

    $order['items'] = fetchOrderItems($mysqli, $order['id']);
    return $order;
}

function fetchOrderItems(mysqli $mysqli, int $orderId): array
{
    $items = [];
    $stmt = $mysqli->prepare('SELECT mi.name, oi.quantity FROM order_items oi JOIN menu_items mi ON mi.id = oi.menu_item_id WHERE oi.order_id = ?');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    return $items;
}

function fetchRecentOrders(mysqli $mysqli, int $limit = 20): array
{
    $limit = max(1, $limit);
    $stmt = $mysqli->prepare('SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT ?');
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $orders;
}

function fetchOrdersByStatus(mysqli $mysqli, array $statuses): array
{
    if (empty($statuses)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $types = str_repeat('s', count($statuses));
    $sql = "SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON u.id = o.user_id WHERE o.status IN ($placeholders) ORDER BY o.created_at ASC";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$statuses);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $orders;
}

function createNotification(mysqli $mysqli, int $userId, int $orderId, string $message): bool
{
    $stmt = $mysqli->prepare('INSERT INTO notifications (user_id, order_id, message) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $userId, $orderId, $message);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getUserNotifications(mysqli $mysqli, int $userId): array
{
    $stmt = $mysqli->prepare('SELECT n.*, o.status as order_status FROM notifications n 
                             JOIN orders o ON n.order_id = o.id 
                             WHERE n.user_id = ? 
                             ORDER BY n.created_at DESC 
                             LIMIT 50');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notifications;
}

function markNotificationsRead(mysqli $mysqli, int $userId, ?int $notificationId = null): bool
{
    $sql = 'UPDATE notifications SET is_read = 1 WHERE user_id = ?';
    $types = 'i';
    $params = [&$userId];
    
    if ($notificationId !== null) {
        $sql .= ' AND id = ?';
        $types .= 'i';
        $params[] = &$notificationId;
    }
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function updateOrderStatus(mysqli $mysqli, int $orderId, string $targetStatus): bool
{
    $validStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
    if (!in_array($targetStatus, $validStatuses, true)) {
        return false;
    }

    $stmt = $mysqli->prepare('SELECT id, user_id, status, order_type FROM orders WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        return false;
    }

    // Don't update if status is the same
    if ($order['status'] === $targetStatus) {
        return true;
    }

    $stmt = $mysqli->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $targetStatus, $orderId);
    $success = $stmt->execute();
    $stmt->close();

    // Create notification for the user
    if ($success) {
        $messages = [
            'preparing' => 'Your order #' . $orderId . ' is now being prepared.',
            'ready' => 'Your order #' . $orderId . ' is ready for ' . ($order['order_type'] === 'preorder' ? 'pickup' : 'serving') . '!',
            'completed' => 'Your order #' . $orderId . ' has been completed. Thank you!',
            'cancelled' => 'Your order #' . $orderId . ' has been cancelled.'
        ];

        if (isset($messages[$targetStatus])) {
            createNotification($mysqli, $order['user_id'], $orderId, $messages[$targetStatus]);
        }
    }

    return $success;
}

function fetchAdminStats(mysqli $mysqli): array
{
    $stats = [
        'total_users' => 0,
        'total_orders' => 0,
        'pending_orders' => 0,
        'today_revenue' => 0,
        'top_items' => [],
        'latest_orders' => [],
    ];

    if ($result = $mysqli->query('SELECT COUNT(*) AS total FROM users')) {
        $stats['total_users'] = (int) ($result->fetch_assoc()['total'] ?? 0);
        $result->free();
    }

    if ($result = $mysqli->query('SELECT COUNT(*) AS total, SUM(total_amount) AS revenue FROM orders')) {
        $row = $result->fetch_assoc();
        $stats['total_orders'] = (int) ($row['total'] ?? 0);
        $stats['revenue'] = (float) ($row['revenue'] ?? 0);
        $result->free();
    }

    if ($result = $mysqli->query("SELECT COUNT(*) AS total FROM orders WHERE status IN ('pending','preparing')")) {
        $stats['pending_orders'] = (int) ($result->fetch_assoc()['total'] ?? 0);
        $result->free();
    }

    if ($stmt = $mysqli->prepare('SELECT SUM(total_amount) AS revenue FROM orders WHERE DATE(created_at) = CURDATE()')) {
        $stmt->execute();
        $stats['today_revenue'] = (float) ($stmt->get_result()->fetch_assoc()['revenue'] ?? 0);
        $stmt->close();
    }

    $topItemsSql = 'SELECT mi.name, SUM(oi.quantity) AS total_qty
        FROM order_items oi
        JOIN menu_items mi ON mi.id = oi.menu_item_id
        GROUP BY mi.name
        ORDER BY total_qty DESC
        LIMIT 3';
    if ($result = $mysqli->query($topItemsSql)) {
        while ($row = $result->fetch_assoc()) {
            $stats['top_items'][] = $row;
        }
        $result->free();
    }

    $latestSql = 'SELECT o.id, o.order_type, o.status, o.total_amount, o.created_at, u.full_name
        FROM orders o
        JOIN users u ON u.id = o.user_id
        ORDER BY o.created_at DESC
        LIMIT 5';
    if ($result = $mysqli->query($latestSql)) {
        while ($row = $result->fetch_assoc()) {
            $stats['latest_orders'][] = $row;
        }
        $result->free();
    }

    return $stats;
}
?>
