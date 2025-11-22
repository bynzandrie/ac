<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

// Check admin access
requireAdmin($mysqli);

// Set page variables
$pageTitle = 'Menu Items Test';
$currentPage = 'test';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Menu Items Test</h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Database Connection Test</h5>
        </div>
        <div class="card-body">
            <?php
            // Test database connection
            echo '<div class="alert alert-' . ($mysqli->connect_error ? 'danger' : 'success') . '">';
            echo $mysqli->connect_error 
                ? '❌ Database connection failed: ' . $mysqli->connect_error 
                : '✅ Database connection successful!';
            echo '</div>';
            
            // Check if table exists
            $tableCheck = $mysqli->query("SHOW TABLES LIKE 'menu_items'");
            $tableExists = $tableCheck && $tableCheck->num_rows > 0;
            
            echo '<div class="alert alert-' . ($tableExists ? 'success' : 'danger') . '">';
            echo $tableExists 
                ? '✅ menu_items table exists.' 
                : '❌ menu_items table does not exist!';
            echo '</div>';
            
            if ($tableExists) {
                // Show table structure
                $result = $mysqli->query("DESCRIBE menu_items");
                if ($result) {
                    echo '<h5>Table Structure:</h5>';
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-bordered">';
                    echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>';
                    echo '<tbody>';
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['Field']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['Default'] ?? 'NULL') . '</td>';
                        echo '<td>' . htmlspecialchars($row['Extra']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table></div>';
                    
                    // Show record count
                    $countResult = $mysqli->query("SELECT COUNT(*) as count FROM menu_items");
                    $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
                    echo '<div class="mt-3"><strong>Total Records:</strong> ' . $count . '</div>';
                    
                    // Test fetchMenuItems function
                    echo '<div class="mt-4">';
                    echo '<h5>Testing fetchMenuItems Function</h5>';
                    if (function_exists('fetchMenuItems')) {
                        try {
                            // First, check if we can get available items
                            $availableItems = fetchMenuItems($mysqli, false);
                            $availableCount = is_array($availableItems) ? count($availableItems) : 0;
                            
                            // Then get all items including unavailable ones
                            $allItems = fetchMenuItems($mysqli, true);
                            $allCount = is_array($allItems) ? count($allItems) : 0;
                            
                            echo '<div class="alert alert-success">';
                            echo '✅ fetchMenuItems function is working.<br>';
                            echo '- Available items: ' . $availableCount . '<br>';
                            echo '- Total items (including unavailable): ' . $allCount;
                            echo '</div>';
                            
                            if (!empty($allItems)) {
                                echo '<h6>Sample Menu Items:</h6>';
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-bordered">';
                                echo '<thead><tr>';
                                echo '<th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th>';
                                echo '</tr></thead><tbody>';
                                
                                $displayCount = 0;
                                foreach ($allItems as $item) {
                                    if ($displayCount++ >= 5) break;
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($item['id'] ?? 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($item['name'] ?? 'No name') . '</td>';
                                    echo '<td>' . htmlspecialchars($item['category'] ?? 'N/A') . '</td>';
                                    echo '<td>₱' . number_format($item['price'] ?? 0, 2) . '</td>';
                                    echo '<td>' . (($item['is_available'] ?? false) ? '✅ Available' : '❌ Not Available') . '</td>';
                                    echo '</tr>';
                                }
                                echo '</tbody></table></div>';
                                
                                if ($allCount > 5) {
                                    echo '<p class="text-muted">Showing 5 of ' . $allCount . ' items. ';
                                    echo '<a href="admin_menu.php">View all in admin panel</a>.</p>';
                                }
                            } else {
                                echo '<div class="alert alert-warning">';
                                echo '⚠️ No menu items found in the database. ';
                                echo '<a href="admin_menu.php?action=add" class="alert-link">Add some menu items</a>.';
                                echo '</div>';
                            }
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">';
                            echo '❌ Error in fetchMenuItems: ' . htmlspecialchars($e->getMessage());
                            echo '<pre class="mt-2">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="alert alert-danger">';
                        echo '❌ fetchMenuItems function does not exist in functions.php. ';
                        echo 'Please check if the function is properly defined.';
                        echo '</div>';
                    }
                }
            } else {
                echo '<div class="alert alert-info">Since the table does not exist, you may need to import the database schema.</div>';
                echo '<a href="database_new.sql" class="btn btn-primary" target="_blank">View Database Schema</a>';
            }
            ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">PHP Error Log</h5>
        </div>
        <div class="card-body">
            <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">
<?php 
// Show recent error log
$logFile = ini_get('error_log');
if (file_exists($logFile)) {
    $logContent = @file_get_contents($logFile);
    if ($logContent !== false) {
        $lines = array_slice(explode("\n", $logContent), -20);
        echo htmlspecialchars(implode("\n", $lines));
    } else {
        echo 'Could not read error log file.';
    }
} else {
    echo 'Error log file not found at: ' . htmlspecialchars($logFile);
}
?>
            </pre>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>