<?php
// Database credentials
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'canteen_portal';

// Create connection without selecting a database
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// SQL to create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(120) NOT NULL,
        email VARCHAR(120) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('customer','admin') DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category ENUM('Food','Drink') DEFAULT 'Food',
        image_url VARCHAR(255),
        is_available TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_type ENUM('immediate','preorder') DEFAULT 'immediate',
        scheduled_for DATETIME NULL,
        status ENUM('pending','preparing','ready','completed','cancelled') DEFAULT 'pending',
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price_each DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )"
];

// Execute table creation queries
foreach ($tables as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created successfully or already exists<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// Add admin user if not exists
$admin_email = 'admin@canteenhub.local';
$admin_password = password_hash('AdminPass123!', PASSWORD_BCRYPT);
$admin_name = 'Canteen Admin';

$check_admin = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_admin->bind_param('s', $admin_email);
$check_admin->execute();
$result = $check_admin->get_result();

if ($result->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    $stmt->bind_param('sss', $admin_name, $admin_email, $admin_password);
    
    if ($stmt->execute()) {
        echo "✅ Admin user created successfully!<br>";
        echo "Email: $admin_email<br>";
        echo "Password: AdminPass123!<br>";
    } else {
        echo "❌ Error creating admin user: " . $conn->error . "<br>";
    }
    $stmt->close();
} else {
    echo "✅ Admin user already exists<br>";
}

$check_admin->close();
$conn->close();

echo "<br>✅ Database setup complete! <a href='test_db.php'>Test database connection</a>";
?>
