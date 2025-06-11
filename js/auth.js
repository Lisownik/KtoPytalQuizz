document.addEventListener('DOMContentLoaded', function() {
    const authBackdrop = document.getElementById('auth-modal-backdrop');
    const loginForm = document.getElementById('log_in');
    const registerForm = document.getElementById('register');
    const toggleLink = document.getElementById('toggle-link');
    const openLoginLink = document.getElementById('open-login');

    let showingLogin = true;

    function showLogin() {
        loginForm.style.display = 'flex';
        registerForm.style.display = 'none';
        toggleLink.textContent = "Don't have an account? Sign up";
        authBackdrop.style.display = 'flex';
        showingLogin = true;
    }

    function showRegister() {
        loginForm.style.display = 'none';
        registerForm.style.display = 'flex';
        toggleLink.textContent = "Already have an account? Log in";
        authBackdrop.style.display = 'flex';
        showingLogin = false;
    }

    if (openLoginLink) {
        openLoginLink.addEventListener('click', e => {
            e.preventDefault();
            showLogin();
        });
    }

    toggleLink.addEventListener('click', e => {
        e.preventDefault();
        if (showingLogin) {
            showRegister();
        } else {
            showLogin();
        }
    });

    authBackdrop.addEventListener('click', e => {
        if (e.target === authBackdrop) {
            authBackdrop.style.display = 'none';
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && authBackdrop.style.display === 'flex') {
            authBackdrop.style.display = 'none';
        }
    });

    window.showLogin = showLogin;
    window.showRegister = showRegister;
});