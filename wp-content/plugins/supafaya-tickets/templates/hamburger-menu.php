<?php
// Get menu location from attributes or use default
$menu_location = isset($atts['menu_location']) ? $atts['menu_location'] : 'primary';
$menu_class = isset($atts['menu_class']) ? $atts['menu_class'] : 'supafaya-menu-items';
?>

<div class="supafaya-hamburger-menu">
    <!-- Mobile Hamburger Button (visible on small screens) -->
    <button class="hamburger-toggle" aria-label="Toggle Menu">
        <span class="hamburger-box">
            <span class="hamburger-inner"></span>
        </span>
    </button>
    
    <!-- Mobile Dropdown Menu -->
    <div class="mobile-dropdown-menu">
        <div class="mobile-menu-content">
            <?php 
            if (has_nav_menu($menu_location)) {
                wp_nav_menu(array(
                    'theme_location' => $menu_location,
                    'menu_class'     => $menu_class,
                    'container'      => false,
                    'depth'          => 2,
                    'fallback_cb'    => false,
                ));
            } else {
                echo '<div class="menu-not-found">Please assign a menu to the "' . esc_html($menu_location) . '" location in WordPress menu settings.</div>';
            }
            ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerToggle = document.querySelector('.hamburger-toggle');
    const mobileDropdownMenu = document.querySelector('.mobile-dropdown-menu');
    
    // Function to toggle the menu
    function toggleMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        const hamburgerMenu = document.querySelector('.supafaya-hamburger-menu');
        hamburgerToggle.classList.toggle('is-active');
        
        if (hamburgerMenu.classList.contains('open')) {
            hamburgerMenu.classList.add('closing');
            // Remove open class after animation starts
            setTimeout(function() {
                hamburgerMenu.classList.remove('open');
                // Remove closing animation class after it completes
                setTimeout(function() {
                    hamburgerMenu.classList.remove('closing');
                }, 300);
            }, 10);
        } else {
            hamburgerMenu.classList.add('open');
            // Add opening animation class
            hamburgerMenu.classList.add('opening');
            // Remove after animation completes
            setTimeout(function() {
                hamburgerMenu.classList.remove('opening');
            }, 300);
        }
    }
    
    // Toggle menu when hamburger is clicked
    if (hamburgerToggle) {
        hamburgerToggle.addEventListener('click', toggleMenu);
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        const hamburgerMenu = document.querySelector('.supafaya-hamburger-menu');
        
        if (!e.target.closest('.supafaya-hamburger-menu') && hamburgerMenu.classList.contains('open')) {
            toggleMenu();
        }
    });
    
    // Close menu when escape key is pressed
    document.addEventListener('keydown', function(event) {
        const hamburgerMenu = document.querySelector('.supafaya-hamburger-menu');
        
        if (event.key === 'Escape' && hamburgerMenu.classList.contains('open')) {
            toggleMenu();
        }
    });
    
    // Handle submenu toggles for mobile
    const menuItems = document.querySelectorAll('.supafaya-menu-items li.menu-item-has-children');
    
    menuItems.forEach(function(item) {
        const link = item.querySelector('a');
        const submenuToggle = document.createElement('button');
        submenuToggle.classList.add('submenu-toggle');
        submenuToggle.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';
        submenuToggle.setAttribute('aria-label', 'Toggle Submenu');
        
        if (link) {
            link.parentNode.insertBefore(submenuToggle, link.nextSibling);
            
            submenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.toggle('is-active');
                item.classList.toggle('submenu-open');
            });
        }
    });
});
</script>

<style>
/* Base variables for light mode */
:root {
    --supafaya-primary: #FF7900;
    --supafaya-primary-hover: #E66800;
    --supafaya-text: #333;
    --supafaya-text-secondary: #666;
    --supafaya-bg-light: #ffffff;
    --supafaya-bg-hover: #f5f5f5;
    --supafaya-border: rgba(0, 0, 0, 0.1);
    --supafaya-avatar-bg: #e0e0e0;
    --supafaya-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    --supafaya-transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    :root {
        --supafaya-text: #e0e0e0;
        --supafaya-text-secondary: #b0b0b0;
        --supafaya-bg-light: #222222;
        --supafaya-bg-hover: #333333;
        --supafaya-border: rgba(255, 255, 255, 0.1);
        --supafaya-avatar-bg: #444444;
        --supafaya-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
}

/* Force the primary color for the hamburger menu to be orange */
.supafaya-hamburger-menu {
    --supafaya-primary: #FF7900 !important;
    --supafaya-primary-hover: #E66800 !important;
}

.supafaya-hamburger-menu {
    position: relative;
    display: inline-block;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* Hamburger Button */
.hamburger-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    background: transparent;
    border: none;
    cursor: pointer;
    outline: none;
    transition: all var(--supafaya-transition);
    z-index: 1001;
    position: relative;
}

.hamburger-box {
    position: relative;
    display: inline-block;
    width: 24px;
    height: 20px;
}

.hamburger-inner,
.hamburger-inner::before,
.hamburger-inner::after {
    position: absolute;
    width: 24px;
    height: 2px;
    border-radius: 2px;
    background-color: var(--supafaya-primary);
    transition: transform 0.3s ease, background-color 0.3s ease;
}

.hamburger-inner {
    top: 50%;
    transform: translateY(-50%);
}

.hamburger-inner::before,
.hamburger-inner::after {
    content: '';
    display: block;
}

.hamburger-inner::before {
    top: -8px;
}

.hamburger-inner::after {
    bottom: -8px;
}

/* Hamburger Animation */
.hamburger-toggle.is-active .hamburger-inner {
    background-color: transparent;
}

.hamburger-toggle.is-active .hamburger-inner::before {
    transform: rotate(45deg);
    top: 0;
    background-color: var(--supafaya-primary);
}

.hamburger-toggle.is-active .hamburger-inner::after {
    transform: rotate(-45deg);
    bottom: 0;
    top: 0;
    background-color: var(--supafaya-primary);
}

/* Mobile Dropdown Menu */
.mobile-dropdown-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background-color: var(--supafaya-bg-light);
    min-width: 260px;
    max-width: 90vw;
    max-height: calc(90vh - 100px);
    box-shadow: var(--supafaya-shadow);
    border-radius: 12px;
    overflow: hidden;
    z-index: 1000;
    display: none;
    border: 1px solid var(--supafaya-border);
    transform-origin: top right;
    transition: all var(--supafaya-transition);
    opacity: 0;
    transform: scale(0.95);
}

.supafaya-hamburger-menu.open .mobile-dropdown-menu {
    display: block;
    opacity: 1;
    transform: scale(1);
}

.supafaya-hamburger-menu.opening .mobile-dropdown-menu {
    animation: dropdownOpen 0.3s forwards;
}

.supafaya-hamburger-menu.closing .mobile-dropdown-menu {
    animation: dropdownClose 0.3s forwards;
}

@keyframes dropdownOpen {
    0% { opacity: 0; transform: scale(0.95); }
    100% { opacity: 1; transform: scale(1); }
}

@keyframes dropdownClose {
    0% { opacity: 1; transform: scale(1); }
    100% { opacity: 0; transform: scale(0.95); }
}

/* Menu Content */
.mobile-menu-content {
    overflow-y: auto;
    max-height: 70vh;
    padding: 8px 0;
}

/* Menu Items */
.supafaya-menu-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.supafaya-menu-items li {
    position: relative;
}

.supafaya-menu-items a {
    display: block;
    padding: 12px 16px;
    color: var(--supafaya-text);
    text-decoration: none;
    font-size: 15px;
    transition: all var(--supafaya-transition);
    border-left: 3px solid transparent;
}

.supafaya-menu-items a:hover,
.supafaya-menu-items .current-menu-item > a {
    background-color: var(--supafaya-bg-hover);
    border-left-color: var(--supafaya-primary);
    color: var(--supafaya-primary);
}

/* Submenu */
.supafaya-menu-items .sub-menu {
    display: none;
    list-style: none;
    padding: 0;
    margin: 0;
    background-color: rgba(255, 121, 0, 0.05);
}

@media (prefers-color-scheme: dark) {
    .supafaya-menu-items .sub-menu {
        background-color: rgba(255, 121, 0, 0.1);
    }
}

.supafaya-menu-items .menu-item-has-children.submenu-open > .sub-menu {
    display: block;
}

.supafaya-menu-items .sub-menu a {
    padding-left: 32px;
    font-size: 14px;
}

/* Submenu Toggle Button */
.submenu-toggle {
    position: absolute;
    right: 10px;
    top: 10px;
    background: none;
    border: none;
    color: var(--supafaya-text-secondary);
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
    transition: all var(--supafaya-transition);
    border-radius: 50%;
}

.submenu-toggle:hover {
    background-color: rgba(255, 121, 0, 0.1);
    color: var(--supafaya-primary);
}

.submenu-toggle svg {
    transition: transform var(--supafaya-transition);
}

.submenu-toggle.is-active svg {
    transform: rotate(180deg);
    stroke: var(--supafaya-primary);
}

.menu-not-found {
    padding: 20px 16px;
    color: var(--supafaya-text-secondary);
    font-size: 14px;
    text-align: center;
}

/* Desktop Horizontal Menu */
.desktop-menu {
    display: none; /* Hidden on mobile */
}

/* Horizontal menu styles */
.horizontal-menu {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    align-items: center;
}

.horizontal-menu li {
    position: relative;
}

.horizontal-menu a {
    display: block;
    padding: 10px 16px;
    color: var(--supafaya-text);
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    transition: all var(--supafaya-transition);
    position: relative;
}

.horizontal-menu a:hover,
.horizontal-menu .current-menu-item > a {
    color: var(--supafaya-primary);
}

/* Active item underline animation */
.horizontal-menu a::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: 0;
    left: 50%;
    background-color: var(--supafaya-primary);
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

.horizontal-menu a:hover::after,
.horizontal-menu .current-menu-item > a::after {
    width: 70%;
}

/* Desktop dropdown */
.horizontal-menu .sub-menu {
    position: absolute;
    top: 100%;
    left: 0;
    min-width: 200px;
    background-color: var(--supafaya-bg-light);
    box-shadow: var(--supafaya-shadow);
    border-radius: 8px;
    margin-top: 5px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 100;
    overflow: hidden;
    list-style: none;
    padding: 5px 0;
    border-top: 3px solid var(--supafaya-primary);
}

.horizontal-menu .menu-item-has-children:hover > .sub-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.horizontal-menu .sub-menu a {
    padding: 10px 15px;
    font-size: 14px;
}

.horizontal-menu .sub-menu a:hover {
    background-color: rgba(255, 121, 0, 0.05);
}

@media (prefers-color-scheme: dark) {
    .horizontal-menu .sub-menu a:hover {
        background-color: rgba(255, 121, 0, 0.1);
    }
}

.horizontal-menu .sub-menu a::after {
    display: none;
}

/* Dropdown indicator */
.horizontal-menu .menu-item-has-children > a:after {
    content: '';
    display: inline-block;
    width: 0;
    height: 0;
    margin-left: 5px;
    vertical-align: middle;
    border-top: 4px solid;
    border-right: 4px solid transparent;
    border-left: 4px solid transparent;
    opacity: 0.7;
}

/* Media Queries */
@media (min-width: 992px) {
    .hamburger-toggle {
        display: none; /* Hide hamburger on desktop */
    }
    
    .desktop-menu {
        display: block; /* Show horizontal menu on desktop */
    }
}

@media (max-width: 991px) {
    .desktop-menu {
        display: none; /* Hide horizontal menu on mobile */
    }
    
    .hamburger-toggle {
        display: flex; /* Show hamburger on mobile */
    }
}
</style> 