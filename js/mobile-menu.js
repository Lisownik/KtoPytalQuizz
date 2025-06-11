document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const overlay = document.querySelector('.mobile-nav-overlay');
    const mobileLinks = document.querySelectorAll('.mobile-nav a');
    const mobileLoginBtn = document.querySelector('.mobile-login-btn');

    if (overlay) {
        overlay.addEventListener('click', () => {
            menuToggle.checked = false;
        });
    }

    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            menuToggle.checked = false;
        });
    });

    if (mobileLoginBtn) {
        mobileLoginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            menuToggle.checked = false;
            if (typeof window.showLogin === 'function') {
                window.showLogin();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menuToggle.checked) {
            menuToggle.checked = false;
        }
    });
});