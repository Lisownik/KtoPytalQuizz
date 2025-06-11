// Mobile menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const overlay = document.querySelector('.mobile-nav-overlay');
    const mobileLinks = document.querySelectorAll('.mobile-nav a');
    const mobileLoginBtn = document.querySelector('.mobile-login-btn');

    // Zamknij po kliknięciu overlay
    if (overlay) {
        overlay.addEventListener('click', () => {
            menuToggle.checked = false;
        });
    }

    // Zamknij po kliknięciu link
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            menuToggle.checked = false;
        });
    });

    // Sign In z mobile menu
    if (mobileLoginBtn) {
        mobileLoginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            menuToggle.checked = false;
            if (typeof showLogin === 'function') {
                showLogin();
            }
        });
    }

    // ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menuToggle.checked) {
            menuToggle.checked = false;
        }
    });
});