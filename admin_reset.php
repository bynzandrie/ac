<?php
// admin_reset.php
require_once __DIR__ . '/config/db.php';

$email = 'admin@canteenhub.local';
$password = 'AdminPass123!';
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// Update or insert admin user
$stmt = $mysqli->prepare('
    INSERT INTO users (email, full_name, password_hash, role) 
    VALUES (?, "Canteen Admin", ?, "admin")
    ON DUPLICATE KEY UPDATE 
        password_hash = VALUES(password_hash),
        role = "admin"
');
$stmt->bind_param('ss', $email, $passwordHash);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Admin account has been reset successfully!<br>";
    echo "Email: $email<br>";
    echo "Password: $password<br>";
} else {
    echo "Failed to reset admin account. Error: " . $mysqli->error . "<br>";
}

$stmt->close();
$mysqli->close();
?>

<a href="signin.php">Go to Sign In</a>
