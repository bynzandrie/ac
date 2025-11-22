<?php
require_once __DIR__ . '/config/db.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin exists
$email = 'admin@canteenhub.local';
$stmt = $mysqli->prepare('SELECT id, email, role, password_hash FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if ($admin) {
    echo "Admin user exists with the following details:<br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "Email: " . $admin['email'] . "<br>";
    echo "Role: " . $admin['role'] . "<br>";
    
    // Verify password
    $password = 'AdminPass123!';
    if (password_verify($password, $admin['password_hash'])) {
        echo "Password verification: SUCCESS<br>";
    } else {
        echo "Password verification: FAILED - Resetting password...<br>";
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $update = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $update->bind_param('si', $newHash, $admin['id']);
        if ($update->execute()) {
            echo "Password has been reset to 'AdminPass123!'.<br>";
        } else {
            echo "Failed to reset password: " . $mysqli->error . "<br>";
        }
        $update->close();
    }
    
    // Ensure role is admin
    if ($admin['role'] !== 'admin') {
        $update = $mysqli->prepare('UPDATE users SET role = "admin" WHERE id = ?');
        $update->bind_param('i', $admin['id']);
        if ($update->execute()) {
            echo "Role updated to 'admin'.<br>";
        } else {
            echo "Failed to update role: " . $mysqli->error . "<br>";
        }
        $update->close();
    }
} else {
    echo "Admin user not found. Creating...<br>";
    $fullName = 'Canteen Admin';
    $password = 'AdminPass123!';
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    $insert = $mysqli->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, "admin")');
    $insert->bind_param('sss', $fullName, $email, $passwordHash);
    
    if ($insert->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Email: admin@canteenhub.local<br>";
        echo "Password: AdminPass123!<br>";
    } else {
        echo "Failed to create admin user: " . $mysqli->error . "<br>";
    }
    $insert->close();
}

echo "<br>Process complete. <a href='signin.php'>Go to login page</a>";
?>
