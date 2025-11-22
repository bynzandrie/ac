<?php
$pageTitle = 'Menu | CanteenHub';
$currentPage = 'menu';
require_once __DIR__ . '/includes/functions.php';

// Include menu images configuration
$menuImages = [];
if (file_exists(__DIR__ . '/includes/menu_images.php')) {
    $menuImages = include __DIR__ . '/includes/menu_images.php';
}

// Set the base path for food images (relative to web root)
$imageBasePath = 'assets/img/food/';

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_item_id'])) {
    $menuItemId = (int) $_POST['menu_item_id'];
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    addToCart($menuItemId, $quantity, $mysqli);
    $alert = ['type' => 'success', 'message' => 'Added to cart! Review your basket anytime.'];
}

$menuItems = fetchMenuItems($mysqli);

// Remove duplicate menu items by name
$uniqueMenuItems = [];
foreach ($menuItems as $item) {
    $uniqueMenuItems[$item['name']] = $item;
}
$menuItems = array_values($uniqueMenuItems);

require_once __DIR__ . '/includes/header.php';
?>
<section>
    <div class="section-header">
        <h2>Menu</h2>
        <p>Floating icons highlight our signature picks.</p>
    </div>

    <?php if ($alert): ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type']); ?>"><?= htmlspecialchars($alert['message']); ?></div>
    <?php endif; ?>

    <div class="menu-grid">
        <?php foreach ($menuItems as $item): ?>
            <article class="menu-card">
                <div class="menu-item-image">
                    <?php
                    // First try to use the image URL from the database
                    if (!empty($item['image_url']) && file_exists($item['image_url'])) {
                        $imagePath = $item['image_url'];
                        $imageExists = true;
                    } 
                    // Fall back to the menu_images.php mapping
                    else {
                        $imageFile = $menuImages[$item['name']] ?? 'default.jpg';
                        $imagePath = $imageBasePath . $imageFile;
                        $imageFullPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($imagePath, PHP_URL_PATH);
                        $imageExists = file_exists($imageFullPath);
                    }
                    ?>
                    <?php if ($imageExists): ?>
                        <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($item['name']); ?>" loading="lazy" />
                    <?php else: ?>
                        <div class="image-placeholder">No image available</div>
                    <?php endif; ?>
                </div>
                <div class="menu-item-content">
                    <h3><?= htmlspecialchars($item['name']); ?></h3>
                    <p><?= htmlspecialchars($item['description']); ?></p>
                    <div class="price">₱<?= number_format((float) $item['price'], 2); ?></div>
                </div>
                <form method="post">
                    <input type="hidden" name="menu_item_id" value="<?= (int) $item['id']; ?>" />
                    <label class="visually-hidden" for="qty-<?= (int) $item['id']; ?>">Quantity</label>
                    <input id="qty-<?= (int) $item['id']; ?>" type="number" name="quantity" min="1" value="1" />
                    <button class="btn" type="submit">Add to cart</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
