document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    const authSection = document.querySelector('.auth-section');
    const navLinks = document.querySelectorAll('.main-nav a');
    const userProfile = document.querySelector('.user-profile');
    
    // Mobile menu toggle
    function toggleMobileMenu() {
        if (menuToggle) {
            menuToggle.classList.toggle('active');
            if (mainNav) mainNav.classList.toggle('active');
            if (authSection) authSection.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        }
    }
    
    // Close mobile menu
    function closeMobileMenu() {
        if (window.innerWidth <= 768) {
            if (menuToggle) menuToggle.classList.remove('active');
            if (mainNav) mainNav.classList.remove('active');
            if (authSection) authSection.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    }
    
    // Toggle user profile on mobile
    function toggleUserProfile() {
        if (window.innerWidth <= 768 && userProfile) {
            userProfile.classList.toggle('expanded');
        }
    }
    
    // Button hover and active effects
    function setupButtonEffects() {
        // Auth buttons (Sign In / Sign Up)
        const authButtons = document.querySelectorAll('.signin-btn, .signup-btn, .logout-btn, .profile-link');
        
        authButtons.forEach(button => {
            // Add ripple effect
            button.addEventListener('click', function(e) {
                // Remove any existing ripple
                const existingRipple = this.querySelector('.ripple');
                if (existingRipple) {
                    existingRipple.remove();
                }
                
                // Create ripple element
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                this.appendChild(ripple);
                
                // Get click position
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                // Position and animate ripple
                ripple.style.width = ripple.style.height = `${size}px`;
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                
                // Remove ripple after animation
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
            
            // Add active state on click
            button.addEventListener('mousedown', function() {
                this.classList.add('active');
            });
            
            button.addEventListener('mouseup', function() {
                this.classList.remove('active');
            });
            
            button.addEventListener('mouseleave', function() {
                this.classList.remove('active');
            });
            
            // Add focus styles for keyboard navigation
            button.addEventListener('focus', function() {
                this.classList.add('focused');
            });
            
            button.addEventListener('blur', function() {
                this.classList.remove('focused');
            });
            
            // Add hover effect for desktop
            if (window.innerWidth > 768) {
                button.addEventListener('mouseenter', function() {
                    this.classList.add('hover');
                });
                
                button.addEventListener('mouseleave', function() {
                    this.classList.remove('hover');
                });
            }
        });
    }
    
    // Initialize button effects
    setupButtonEffects();
    
    // Event Listeners
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleMobileMenu);
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        const isClickInside = e.target.closest('.header-content');
        if (!isClickInside) {
            closeMobileMenu();
        }
    });
    
    // Handle navigation link clicks
    navLinks.forEach(link => {
        link.addEventListener('click', closeMobileMenu);
        
        // Add active class to current page
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const linkHref = link.getAttribute('href');
        
        if (linkHref === currentPage || 
            (currentPage === '' && linkHref === 'index.php')) {
            link.classList.add('active');
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80, // Adjust for fixed header
                    behavior: 'smooth'
                });
            }
        });
    });

    // Toggle user dropdown on mobile
    if (userProfile) {
        const welcomeMsg = userProfile.querySelector('.welcome-msg');
        if (welcomeMsg && window.innerWidth <= 768) {
            welcomeMsg.addEventListener('click', toggleUserProfile);
        }
    }
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        document.body.classList.add('resize-animation-stopper');
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            document.body.classList.remove('resize-animation-stopper');
            
            // Reset mobile menu state on larger screens
            if (window.innerWidth > 768) {
                closeMobileMenu();
                userProfile.classList.remove('expanded');
            }
        }, 250);
    });
});

// Handle window resize
let resizeTimer;
window.addEventListener('resize', function() {
    document.body.classList.add('resize-animation-stopper');
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        document.body.classList.remove('resize-animation-stopper');
    }, 400);
});
// Add this code to your existing main.js
document.addEventListener('DOMContentLoaded', function() {
    // Check if we need to show the login modal
    const loginRequiredModal = document.getElementById('loginRequiredModal');
    if (loginRequiredModal) {
        // Show modal if it has the 'show' class
        if (loginRequiredModal.classList.contains('show')) {
            const modal = new bootstrap.Modal(loginRequiredModal);
            modal.show();
        }
    }

    // Add click handler for all add to cart buttons
    document.querySelectorAll('form[action*="menu.php"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            // If not logged in, prevent form submission and show login modal
            if (!document.body.classList.contains('logged-in')) {
                e.preventDefault();
                
                // Store form data in sessionStorage
                const formData = new FormData(this);
                const formObject = {};
                formData.forEach((value, key) => {
                    formObject[key] = value;
                });
                sessionStorage.setItem('pendingCartItem', JSON.stringify(formObject));
                
                // Show login modal
                const modal = new bootstrap.Modal(document.getElementById('loginRequiredModal'));
                modal.show();
                return false;
            }
        });
    });

    // Handle login modal buttons
    const signInBtn = document.querySelector('#loginRequiredModal .btn-primary');
    if (signInBtn) {
        signInBtn.addEventListener('click', function() {
            // Store current URL for redirect after login
            sessionStorage.setItem('redirectAfterLogin', window.location.href);
        });
    }
});