// Elements
const authBackdrop = document.getElementById('auth-modal-backdrop');
const loginForm = document.getElementById('log_in');
const registerForm = document.getElementById('register');
const toggleLink = document.getElementById('toggle-link');
const openLoginLink = document.getElementById('open-login');
let showingLogin = true;

// Function to show modal with login form
function showLogin() {
    loginForm.style.display = 'flex';
    registerForm.style.display = 'none';
    toggleLink.textContent = "Don't have an account? Sign up";
    authBackdrop.style.display = 'flex';
    showingLogin = true;
}

// Function to show modal with register form
function showRegister() {
    loginForm.style.display = 'none';
    registerForm.style.display = 'flex';
    toggleLink.textContent = "Already have an account? Log in";
    authBackdrop.style.display = 'flex';
    showingLogin = false;
}

// Open login on clicking top nav link
openLoginLink.addEventListener('click', e => {
    e.preventDefault();
    showLogin();
});

// Toggle between login and register on clicking link below forms
toggleLink.addEventListener('click', e => {
    e.preventDefault();
    if (showingLogin) {
        showRegister();
    } else {
        showLogin();
    }
});

// Click outside modal closes it
authBackdrop.addEventListener('click', e => {
    if (e.target === authBackdrop) {
        authBackdrop.style.display = 'none';
    }
});

// Accessibility: close modal with Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && authBackdrop.style.display === 'flex') {
        authBackdrop.style.display = 'none';
    }
});