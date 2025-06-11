document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('rpassword');
    const confirmPasswordInput = document.getElementById('rpasswordconfirm');
    const registerForm = document.getElementById('registerForm');

    function validatePassword() {
        const password = passwordInput.value;
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            digit: (password.match(/\d/g) || []).length >= 3,
            special: /[!@#$%^&*]/.test(password)
        };

        updateRequirementStatus('req-length', requirements.length);
        updateRequirementStatus('req-uppercase', requirements.uppercase);
        updateRequirementStatus('req-lowercase', requirements.lowercase);
        updateRequirementStatus('req-digit', requirements.digit);
        updateRequirementStatus('req-special', requirements.special);

        checkPasswordMatch();
    }

    function updateRequirementStatus(elementId, isMet) {
        const element = document.getElementById(elementId);
        const icon = element.querySelector('.requirement-icon');

        if (isMet) {
            element.classList.remove('invalid');
            element.classList.add('valid');
            icon.textContent = '✓';
        } else {
            element.classList.remove('valid');
            element.classList.add('invalid');
            icon.textContent = '✗';
        }
    }

    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const messageElement = document.getElementById('password-match-message');

        if (password === '' && confirmPassword === '') {
            messageElement.textContent = '';
            return;
        }

        if (password === confirmPassword) {
            messageElement.textContent = 'Hasła pasują';
            messageElement.className = 'password-match-message match';
        } else {
            messageElement.textContent = 'Hasła nie pasują';
            messageElement.className = 'password-match-message mismatch';
        }
    }

    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);

    registerForm.addEventListener('submit', function(event) {
        const password = passwordInput.value;
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            digit: (password.match(/\d/g) || []).length >= 3,
            special: /[!@#$%^&*]/.test(password)
        };

        const allRequirementsMet = Object.values(requirements).every(met => met);
        const passwordMatch = passwordInput.value === confirmPasswordInput.value;

        if (!allRequirementsMet || !passwordMatch) {
            event.preventDefault();
            alert('Hasło nie spełnia wszystkich wymagań lub hasła nie pasują do siebie!');
        }
    });
});