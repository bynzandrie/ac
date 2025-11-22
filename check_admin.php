<?php
require_once __DIR__ . '/config/db.php';

// Admin credentials
$admin_email = 'admin@canteenhub.local';
$admin_password = 'AdminPass123!';

echo "<h2>Admin Login Diagnostic</h2>";

// 1. Check if admin user exists
$stmt = $mysqli->prepare('SELECT id, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $admin_email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    die("<div style='color:red;'>Error: Admin user not found in database!</div>");
}

echo "<div style='margin: 20px 0; padding: 10px; background: #e8f5e9; border-left: 4px solid #4caf50;'>";
echo "<strong>Admin User Found:</strong><br>";
echo "ID: " . htmlspecialchars($admin['id']) . "<br>";
echo "Email: " . htmlspecialchars($admin['email']) . "<br>";
echo "Role: " . htmlspecialchars($admin['role']) . "<br>";
echo "</div>";

// 2. Verify password hash
$password_correct = password_verify($admin_password, $admin['password_hash']);

echo "<div style='margin: 20px 0; padding: 10px; background: " . ($password_correct ? '#e8f5e9' : '#ffebee') . "; border-left: 4px solid " . ($password_correct ? '#4caf50' : '#f44336') . ";'>";
echo "<strong>Password Verification:</strong> " . ($password_correct ? '✅ Success' : '❌ Failed') . "<br>";

if (!$password_correct) {
    echo "<div style='margin-top: 10px;'>";
    echo "The password 'AdminPass123!' does not match the stored hash.<br>";
    echo "<form method='post' style='margin-top: 10px;'>";
    echo "<input type='hidden' name='fix_password' value='1'>";
    echo "<button type='submit' style='background: #4caf50; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;'>Fix Admin Password</button>";
    echo "</form>";
    echo "</div>";
}
echo "</div>";

// 3. Check if the user has admin role
$is_admin = ($admin['role'] === 'admin');
echo "<div style='margin: 20px 0; padding: 10px; background: " . ($is_admin ? '#e8f5e9' : '#fff3e0') . "; border-left: 4px solid " . ($is_admin ? '#4caf50' : '#ff9800') . ";'>";
echo "<strong>Admin Role:</strong> " . ($is_admin ? '✅ User has admin role' : '⚠️ User does NOT have admin role') . "<br>";

if (!$is_admin) {
    echo "<div style='margin-top: 10px;'>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='fix_role' value='1'>";
    echo "<button type='submit' style='background: #ff9800; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;'>Grant Admin Role</button>";
    echo "</form>";
    echo "</div>";
}
echo "</div>";

// 4. Check session settings
echo "<div style='margin: 20px 0; padding: 10px; background: #e3f2fd; border-left: 4px solid #2196f3;'>";
echo "<strong>Session Status:</strong><br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . " (2 = PHP_SESSION_ACTIVE)<br>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "Session is active and working correctly.";
} else {
    echo "<span style='color: red;'>Warning: Session is not active. This could prevent login from working.</span>";
}
echo "</div>";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['fix_password'])) {
        // Update the admin password
        $new_hash = password_hash($admin_password, PASSWORD_BCRYPT);
        $stmt = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $new_hash, $admin['id']);
        if ($stmt->execute()) {
            echo "<div style='margin: 20px 0; padding: 10px; background: #e8f5e9; border-left: 4px solid #4caf50;'>";
            echo "✅ Admin password has been reset to 'AdminPass123!'";
            echo "</div>";
            // Refresh the page to show updated status
            echo "<script>setTimeout(function(){ window.location.href = 'check_admin.php'; }, 1500);</script>";
        } else {
            echo "<div style='color:red;'>Error updating password: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
    
    if (isset($_POST['fix_role'])) {
        // Grant admin role
        $stmt = $mysqli->prepare('UPDATE users SET role = "admin" WHERE id = ?');
        $stmt->bind_param('i', $admin['id']);
        if ($stmt->execute()) {
            echo "<div style='margin: 20px 0; padding: 10px; background: #e8f5e9; border-left: 4px solid #4caf50;'>";
            echo "✅ Admin role has been granted to this user.";
            echo "</div>";
            // Refresh the page to show updated status
            echo "<script>setTimeout(function(){ window.location.href = 'check_admin.php'; }, 1500);</script>";
        } else {
            echo "<div style='color:red;'>Error updating role: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

// Add link to sign in page
echo "<div style='margin-top: 30px;'>";
echo "<a href='signin.php' style='display: inline-block; background: #2196f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Sign In</a>";
echo "</div>";
?>
