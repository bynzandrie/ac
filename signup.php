<?php
$pageTitle = 'Sign up | CanteenHub';
$currentPage = 'home';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Email already registered. Try signing in.';
        }
        $stmt->close();
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $mysqli->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $fullName, $email, $passwordHash);
        if ($stmt->execute()) {
            $success = 'Account created! You can now sign in.';
        } else {
            $errors[] = 'Could not create account. Please try again later.';
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrapper">
    <div class="form-card">
        <h2>Create account</h2>
        <p>Manage orders, pre-orders, and cart in one student dashboard.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="full_name">Full name</label>
                <input id="full_name" type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? ''); ?>" />
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" />
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required />
            </div>

            <div class="form-group">
                <label for="confirm">Confirm password</label>
                <input id="confirm" type="password" name="confirm_password" required />
            </div>

            <button class="btn" type="submit">Sign up</button>
            <p class="muted" style="margin-top:12px;">Already registered? <a href="signin.php">Sign in</a>.</p>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
