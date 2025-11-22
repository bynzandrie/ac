<?php
$pageTitle = 'Anne\'s Canteen | Fresh & Fast';
$currentPage = 'home';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-content">
        <p class="badge">Anne's Canteen</p>
        <h1>Fresh meals and drinks, ready when you are.</h1>
        <p>Browse our delicious menu, add items to your cart, and manage your profile in one clean dashboard.</p>
        <div class="hero-actions">
            <a class="btn" href="menu.php">Explore Menu</a>
        </div>
    </div>
    
</section>
<section class="features">
    <article>
        <h3>Menu</h3>
        <p>Floating cards showcase snacks, hot meals, and signature drinks.</p>
    </article>
    <article>
        <h3>Cart & Checkout</h3>
        <p>Review items, adjust quantities, and confirm payment.</p>
    </article>
    <article>
        <h3>Profile</h3>
        <p>Track your previous orders and edit personal info.</p>
    </article>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
