<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Canteen Portal';
$currentPage = $currentPage ?? '';
$user = currentUser($mysqli);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png" />
    <style>
        /* Header and Navigation Styles */
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo-title-container {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .logo-container {
            width: 40px;
            height: 40px;
            margin-right: 12px;
        }

        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .site-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: #2c3e50;
        }

        /* Navigation */
        .main-nav ul {
            display: flex;
            gap: 5px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .main-nav a {
            display: block;
            padding: 12px 18px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #333;
            white-space: nowrap;
        }
        
        .main-nav a:hover, 
        .main-nav a.active {
            background: #f0f0f0;
            transform: translateY(-2px);
        }
        
        /* Auth Section */
        .auth-section {
            display: flex;
            align-items: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        
        .profile-link,
        .logout-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            margin-left: 8px;
            font-size: 0.95rem;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .profile-link {
            background: #f0f0f0;
            color: #333;
        }
        
        .logout-btn {
            background: #ff5e00;
            color: white !important;
        }
        
        .profile-link:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }
        
        .logout-btn:hover {
            background: #ff8c00;
            transform: translateY(-2px);
        }
        
        .auth-buttons {
            display: flex;
            gap: 10px;
        }
        
        .signin-btn, 
        .signup-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }
        
        .signin-btn {
            background: #f0f0f0;
            color: #333;
        }
        
        .signup-btn {
            background: #fc9309;
            color: white;
        }
        
        .signin-btn:hover, 
        .signup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .admin-badge {
            background: #28a745;
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 6px;
            vertical-align: middle;
            display: inline-block;
            line-height: 1.2;
        }
        
        .signout-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            margin-left: 8px;
            background: #dc3545;
            color: white !important;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            height: 36px;
        }
        
        .signout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        .signout-btn i {
            margin-right: 6px;
            font-size: 0.9em;
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .header-content {
                padding: 0 15px;
            }
            
            .main-nav a {
                padding: 10px 14px;
                font-size: 0.95rem;
            }
            
        }

        @media (max-width: 992px) {
            .site-title {
                font-size: 1.3rem;
            }
            
            .main-nav {
                display: none;
            }
            
            .menu-toggle {
                display: block !important;
            }
        }
    </style>
</head>
<body>
<header class="top-nav">
    <div class="header-content">
        <div class="logo-title-container">
            <div class="logo-container">
                <img src="/canteen_portal/assets/img/logo.png" alt="Anne's Canteen Logo" class="site-logo" onerror="this.style.display='none';">
            </div>
            <h1 class="site-title">Anne's Canteen</h1>
        </div>
        
        <nav class="main-nav">
            <ul>
                <?php if ($user && ($user['role'] ?? 'customer') === 'admin'): ?>
                    <li><a href="admin_orders.php" class="<?= $currentPage === 'admin-orders' ? 'active' : '' ?>">View Orders</a></li>
                    <li><a href="admin_menu.php" class="<?= $currentPage === 'admin-menu' ? 'active' : '' ?>">Menu Management</a></li>
                    <li><a href="admin_prepare.php" class="<?= $currentPage === 'admin-prepare' ? 'active' : '' ?>">Prepare</a></li>
                    <li><a href="admin_serve.php" class="<?= $currentPage === 'admin-serve' ? 'active' : '' ?>">Serve</a></li>
                <?php else: ?>
                    <li><a href="index.php" class="<?= $currentPage === 'home' ? 'active' : '' ?>">Home</a></li>
                    <li><a href="menu.php" class="<?= $currentPage === 'menu' ? 'active' : '' ?>">Menu</a></li>
                    <li><a href="cart.php" class="<?= $currentPage === 'cart' ? 'active' : '' ?>">Cart</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="auth-section">
            <?php if (isLoggedIn()): ?>
                <div class="user-profile">
                    <a href="profile.php" class="profile-link">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="logout.php" class="signout-btn">
                        <i class="fas fa-sign-out-alt"></i> Sign Out
                    </a>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="signin.php" class="signin-btn">Sign In</a>
                    <a href="signup.php" class="signup-btn">Sign Up</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <button class="menu-toggle" aria-label="Open navigation" style="display: none;">
        <i class="fas fa-bars"></i>
    </button>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            mainNav.style.display = mainNav.style.display === 'none' || !mainNav.style.display ? 'block' : 'none';
        });
        
        // Handle window resize
        function handleResize() {
            if (window.innerWidth > 992) {
                mainNav.style.display = '';
                menuToggle.style.display = 'none';
            } else {
                menuToggle.style.display = 'flex';
                mainNav.style.display = 'none';
            }
        }
        
        // Initial check
        handleResize();
        
        // Add event listener for window resize
        window.addEventListener('resize', handleResize);
    }
});
</script>
<main>
