<?php
require_once __DIR__ . '/config/db.php';

// Test database connection
if ($mysqli->ping()) {
    echo "✅ Database connection successful!<br>";
    
    // Test if tables exist
    $tables = ['users', 'menu_items', 'orders', 'order_items', 'notifications'];
    $allTablesExist = true;
    
    foreach ($tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' is missing<br>";
            $allTablesExist = false;
        }
    }
    
    if ($allTablesExist) {
        echo "<br>✅ All required tables exist in the database.";
        
        // Test admin user
        $result = $mysqli->query("SELECT * FROM users WHERE email = 'admin@canteenhub.local'");
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            echo "<br>✅ Admin user exists (ID: " . $admin['id'] . ")";
        } else {
            echo "<br>❌ Admin user not found";
        }
    }
} else {
    echo "❌ Database connection failed: " . $mysqli->connect_error;
}

$mysqli->close();
?>
