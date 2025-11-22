<?php
require_once __DIR__ . '/config/db.php';

// Disable foreign key checks
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

// Clear existing data from related tables
$tables = ['order_items', 'menu_items'];
foreach ($tables as $table) {
    $mysqli->query("TRUNCATE TABLE `$table`");
    echo "Cleared table: $table<br>";
}

// Re-enable foreign key checks
$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

// Define the 4 menu items
$menuItems = [
    [
        'name' => 'Chicken Teriyaki Bowl',
        'description' => 'Grilled chicken glazed with teriyaki sauce, served with steamed rice and vegetables.',
        'price' => 12.99,
        'category' => 'main',
        'is_available' => 1
    ],
    [
        'name' => 'Veggie Wrap',
        'description' => 'Fresh vegetables and hummus wrapped in a whole wheat tortilla.',
        'price' => 8.99,
        'category' => 'main',
        'is_available' => 1
    ],
    [
        'name' => 'Caesar Salad',
        'description' => 'Crisp romaine lettuce with Caesar dressing, croutons, and parmesan cheese.',
        'price' => 7.99,
        'category' => 'salad',
        'is_available' => 1
    ],
    [
        'name' => 'Chocolate Brownie',
        'description' => 'Warm chocolate brownie served with vanilla ice cream.',
        'price' => 5.99,
        'category' => 'dessert',
        'is_available' => 1
    ]
];

// Insert the menu items
$stmt = $mysqli->prepare("INSERT INTO menu_items (name, description, price, category, is_available) VALUES (?, ?, ?, ?, ?)");

foreach ($menuItems as $item) {
    $stmt->bind_param(
        'ssdsi',
        $item['name'],
        $item['description'],
        $item['price'],
        $item['category'],
        $item['is_available']
    );
    
    if ($stmt->execute()) {
        echo "Added: " . htmlspecialchars($item['name']) . "<br>";
    } else {
        echo "Error adding " . htmlspecialchars($item['name']) . ": " . $mysqli->error . "<br>";
    }
}

$stmt->close();

echo "<br>Menu has been updated with 4 items. <a href='menu.php'>View Menu</a>";
?>s