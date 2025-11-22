<?php
$pageTitle = 'Admin Login | CanteenHub';
$currentPage = 'admin-login';
require_once __DIR__ . '/includes/functions.php';

$errors = [];

// Check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $mysqli->prepare('SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['user_id'];
        header('Location: admin.php');
        exit;
    }
    // Clear invalid cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($username === '' || $password === '') {
        $errors[] = 'Username and password are required.';
    } else {
        $stmt = $mysqli->prepare('SELECT id, password_hash, role FROM users WHERE username = ? AND role = "admin" LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $errors[] = 'Invalid admin credentials.';
        } else {
            $_SESSION['user_id'] = $admin['id'];
            
            // Handle remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days
                
                // Store token in database
                $stmt = $mysqli->prepare('INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?) 
                                         ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)');
                $stmt->bind_param('iss', $admin['id'], $token, $expires);
                $stmt->execute();
                $stmt->close();
                
                // Set cookie
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
            }
            
            header('Location: admin.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrapper">
    <div class="form-card">
        <h2>Admin Login</h2>
        <p>Restricted access. Please use your admin credentials.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endforeach; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Admin Username</label>
                <input id="username" type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" autocomplete="username" />
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" />
            </div>

            <div class="form-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="remember" id="remember" />
                    <span class="checkmark"></span>
                    Remember me
                </label>
            </div>

            <button class="btn" type="submit">Sign in as admin</button>
            <p class="muted" style="margin-top:12px;">Not an admin? <a href="signin.php">Student login</a>.</p>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
