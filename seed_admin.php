<?php
require_once __DIR__ . '/config/db.php';

$fullName = 'Canteen Admin';
$email = 'admin@canteenhub.local';
$plainPassword = 'AdminPass123!';
$passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

$stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $update = $mysqli->prepare('UPDATE users SET full_name = ?, password_hash = ?, role = "admin" WHERE email = ?');
    $update->bind_param('sss', $fullName, $passwordHash, $email);
    $update->execute();
    $update->close();
    $message = 'Existing admin credentials refreshed. You can sign in using ' . $email;
} else {
    $stmt->close();
    $insert = $mysqli->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, "admin")');
    $insert->bind_param('sss', $fullName, $email, $passwordHash);
    $insert->execute();
    $insert->close();
    $message = 'Admin account created. You can sign in using ' . $email;
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seed Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f9fafb; display:flex; min-height:100vh; align-items:center; justify-content:center; }
        .card { background:#fff; padding:32px; border-radius:16px; box-shadow:0 15px 40px rgba(15,23,42,0.12); max-width:420px; text-align:center; }
        h1 { margin-bottom:12px; }
        p { color:#374151; }
        code { background:#f3f4f6; padding:4px 6px; border-radius:4px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Admin Ready</h1>
    <p><?= htmlspecialchars($message); ?></p>
    <p>Default password: <code><?= htmlspecialchars($plainPassword); ?></code></p>
    <p>Please delete <code>seed_admin.php</code> after use.</p>
</div>
</body>
</html>
