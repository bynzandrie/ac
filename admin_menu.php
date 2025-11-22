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
$pageTitle = 'Menu Management | Admin';
$currentPage = 'admin';
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Fetch all menu items
$menuItems = [];
$stmt = $mysqli->prepare("SELECT * FROM menu_items ORDER BY category, name");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $menuItems[] = $row;
    }
}
$stmt->close();

// Handle form submission for adding/editing menu items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category = $_POST['category'] ?? 'Food';
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $image_url = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/img/food/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Create a clean filename based on the menu item name
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $name));
        $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = $cleanName . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $image_url = $targetPath;
            
            // Update menu_images.php
            $menuImagesFile = __DIR__ . '/includes/menu_images.php';
            if (file_exists($menuImagesFile)) {
                $menuImages = include $menuImagesFile;
                if (!is_array($menuImages)) {
                    $menuImages = [];
                }
                
                // Add or update the menu item in the images array
                $menuImages[$name] = $fileName;
                
                // Generate the new file content
                $content = "<?php\n// Map of menu item names to their corresponding image filenames\nreturn [\n";
                foreach ($menuImages as $itemName => $imageFile) {
                    $content .= "    '" . addslashes($itemName) . "' => '" . addslashes($imageFile) . "',\n";
                }
                $content .= "];\n?>";
                
                // Write the updated content back to menu_images.php
                file_put_contents($menuImagesFile, $content);
            }
        }

    if ($id > 0) {
        // Update existing item
        if (!empty($image_url)) {
            $stmt = $mysqli->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, is_available = ?, image_url = ? WHERE id = ?");
            $stmt->bind_param('ssdsssi', $name, $description, $price, $category, $is_available, $image_url, $id);
        } else {
            $stmt = $mysqli->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, is_available = ? WHERE id = ?");
            $stmt->bind_param('ssdssi', $name, $description, $price, $category, $is_available, $id);
        }
    } else {
        // Insert new item
        $stmt = $mysqli->prepare("INSERT INTO menu_items (name, description, price, category, is_available, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssdsss', $name, $description, $price, $category, $is_available, $image_url);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Menu item ' . ($id > 0 ? 'updated' : 'added') . ' successfully!';
        header('Location: admin_menu.php');
        exit;
    } else {
        $message = 'Error: ' . $stmt->error;
    }
    $stmt->close();
}
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Menu item deleted successfully!';
    } else {
        $_SESSION['message'] = 'Error deleting menu item: ' . $stmt->error;
    }
    $stmt->close();
    header('Location: admin_menu.php');
    exit;
}
// Include header
require_once __DIR__ . '/includes/header.php';
?>
<div class="container mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-utensils me-2"></i>Menu Management</h1>
        <button type="button" class="btn btn-primary" id="addNewItemBtn">
            <i class="fas fa-plus me-2"></i>Add New Item
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Menu Items Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($menuItems)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No menu items found. Add your first item!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($menuItems as $item): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= htmlspecialchars(substr($item['description'] ?? '', 0, 50)) ?>...</td>
                                    <td><?= htmlspecialchars($item['category']) ?></td>
                                    <td>₱<?= number_format($item['price'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $item['is_available'] ? 'success' : 'danger' ?>">
                                            <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?action=delete&id=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Item Modal -->
<div class="modal fade" id="menuItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="menuItemForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="menuItemId" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Item Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="Food">Food</option>
                                <option value="Drink">Drink</option>
                                <option value="Dessert">Dessert</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input class="form-control" type="file" id="image" name="image" accept="image/*">
                        <div id="imagePreview" class="mt-2"></div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_available" name="is_available" checked>
                        <label class="form-check-label" for="is_available">Available</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>

<style>
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.table img {
    border-radius: 4px;
}

#imagePreview img {
    max-width: 200px;
    max-height: 150px;
    border-radius: 4px;
    margin-top: 10px;
}
</style>
<script>
// Function to handle edit button click
function handleEditClick(e) {
    if (e.target.closest('.edit-item')) {
        e.preventDefault();
        const button = e.target.closest('.edit-item');
        try {
            const item = JSON.parse(button.getAttribute('data-item'));
            
            // Set form values
            const form = document.getElementById('menuItemForm');
            if (form) form.reset();
            
            document.getElementById('menuItemId').value = item.id || '0';
            document.getElementById('name').value = item.name || '';
            document.getElementById('description').value = item.description || '';
            document.getElementById('price').value = parseFloat(item.price || 0).toFixed(2);
            document.getElementById('category').value = item.category || 'Food';
            document.getElementById('is_available').checked = parseInt(item.is_available || 0) === 1;
            
            // Handle image preview
            const imagePreview = document.getElementById('imagePreview');
            if (imagePreview) {
                imagePreview.innerHTML = '';
                if (item.image_url) {
                    const img = document.createElement('img');
                    img.src = item.image_url;
                    img.style.maxWidth = '100%';
                    img.style.maxHeight = '150px';
                    img.className = 'img-thumbnail';
                    imagePreview.appendChild(img);
                }
            }
            
            // Update modal title and show
            const modalTitle = document.getElementById('modalTitle');
            if (modalTitle) modalTitle.textContent = 'Edit Menu Item';
            
            const modal = new bootstrap.Modal(document.getElementById('menuItemModal'));
            modal.show();
            
        } catch (error) {
            console.error('Error parsing item data:', error);
            alert('Error loading item data. Please try again.');
        }
    }
}

// Initialize when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal
    const menuItemModal = new bootstrap.Modal(document.getElementById('menuItemModal'));
    const imageInput = document.getElementById('image');
    
    // Handle image preview
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const imagePreview = document.getElementById('imagePreview');
            
            if (file && imagePreview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail" style="max-width: 100%; max-height: 150px;">
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Add event listener for edit buttons
    document.addEventListener('click', handleEditClick);

    // Handle Add New Item button click
    const addButton = document.getElementById('addNewItemBtn');
    if (addButton) {
        addButton.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('menuItemForm');
            if (form) {
                form.reset();
                document.getElementById('menuItemId').value = '0';
                const imagePreview = document.getElementById('imagePreview');
                if (imagePreview) imagePreview.innerHTML = '';
                const modalTitle = document.getElementById('modalTitle');
                if (modalTitle) modalTitle.textContent = 'Add New Menu Item';
                menuItemModal.show();
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
