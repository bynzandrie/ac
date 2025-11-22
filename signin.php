<?php
session_start();
$pageTitle = 'Sign in | CanteenHub';
$currentPage = 'home';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$infoMessage = '';

// Check for login required message
if (isset($_SESSION['login_required_message'])) {
    $infoMessage = $_SESSION['login_required_message'];
    unset($_SESSION['login_required_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        $stmt = $mysqli->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            
            // Handle remember me
            if (!empty($_POST['remember_me'])) {
                setRememberMeToken($mysqli, $user['id']);
            }
            
            // Redirect to intended page or profile
            $redirect = $_SESSION['redirect_after_login'] ?? 'profile.php';
            unset($_SESSION['redirect_after_login']);
            
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors[] = 'Invalid credentials. Try again or sign up.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<style>
    .auth-wrapper {
        max-width: 400px;
        margin: 2rem auto;
        padding: 1rem;
    }
    .remember-me {
        display: flex;
        align-items: center;
        margin: 1rem 0;
    }
    .remember-me input[type="checkbox"] {
        width: auto;
        margin-right: 0.5rem;
    }
</style>
<section class="auth-wrapper">
    <div class="form-card">
        <h2>Sign in</h2>
        <p>Access your saved orders and quick checkout.</p>

        <?php if ($infoMessage): ?>
            <div class="alert alert-info"><?= htmlspecialchars($infoMessage); ?></div>
        <?php endif; ?>
        
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endforeach; ?>

        <form method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" />
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required />
            </div>

            <div class="form-group remember-me">
                <input type="checkbox" id="remember_me" name="remember_me" value="1" />
                <label for="remember_me">Remember me</label>
            </div>

            <button class="btn" type="submit">Sign in</button>
            <p class="muted" style="margin-top:12px;">New here? <a href="signup.php">Create an account</a>.</p>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
