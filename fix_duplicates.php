<?php
require_once __DIR__ . '/config/db.php';

// Find duplicate menu items
echo "Finding duplicate menu items...<br>";
$query = "SELECT name, COUNT(*) as count FROM menu_items GROUP BY name HAVING count > 1";
$result = $mysqli->query($query);

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " duplicate menu items:<br>";
    
    while ($row = $result->fetch_assoc()) {
        echo "- " . htmlspecialchars($row['name']) . " (" . $row['count'] . " entries)<br>";
        
        // Get all duplicate items for this name
        $items = $mysqli->query("SELECT * FROM menu_items WHERE name = " . $mysqli->real_escape_string($row['name']));
        $first = true;
        $ids_to_keep = [];
        
        while ($item = $items->fetch_assoc()) {
            if ($first) {
                echo "  Keeping ID: " . $item['id'] . "<br>";
                $ids_to_keep[] = $item['id'];
                $first = false;
            } else {
                echo "  Will remove ID: " . $item['id'] . "<br>";
                
                // Delete the duplicate
                $delete = $mysqli->prepare("DELETE FROM menu_items WHERE id = ?");
                $delete->bind_param('i', $item['id']);
                if ($delete->execute()) {
                    echo "  - Removed duplicate ID: " . $item['id'] . "<br>";
                } else {
                    echo "  - Failed to remove ID " . $item['id'] . ": " . $mysqli->error . "<br>";
                }
                $delete->close();
            }
        }
    }
    
    echo "<br>Cleanup complete!<br>";
} else {
    echo "No duplicate menu items found.<br>";
}

echo "<br><a href='menu.php'>Back to Menu</a>";
?>
