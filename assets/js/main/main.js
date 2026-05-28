/**
 * ============================================================
 * TUPV RMS — Public Portal Global Scripts
 * assets/js/main/main.js
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", () => {
    // 1. Initialize Global Navigation & Scroll Behaviors
    initNavigation();

    // 2. Initialize Scroll Fade-Up Animations
    initScrollAnimations();
});

/**
 * Navigation Initialization
 * Handles mobile sidebar toggles and scroll styling triggers for the header navbar.
 */
function initNavigation() {
    const toggle = document.getElementById('navToggle');
    const nav    = document.getElementById('mainNav');
    const header = document.getElementById('main-header');

    // Mobile Hamburger Menu Toggle
    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            nav.classList.toggle('open');
            toggle.classList.toggle('active');
        });
    }

    // Header Scroll Effect (Shrinks/darkens on scroll)
    if (header) {
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 50);
        });
    }
}

/**
 * Scroll Animations
 * Initializes revealing animations with staggering delays for sibling elements 
 * to create a cascading "fade-up" effect as the user scrolls down the page.
 */
function initScrollAnimations() {
    const fadeElements = document.querySelectorAll('.fade-up');
    if (!fadeElements.length) return;

    const animationObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const target = entry.target;
                
                // Calculate delay based on element's position among its siblings
                const siblings = Array.from(target.parentElement.children);
                const elementIndex = siblings.indexOf(target);
                
                target.style.transitionDelay = (elementIndex * 0.10) + 's';
                target.classList.add('visible');
                
                // Stop observing once the animation has triggered
                animationObserver.unobserve(target);
            }
        });
    }, { threshold: 0.12 });

    // Attach the observer to all designated elements
    fadeElements.forEach(element => animationObserver.observe(element));
}