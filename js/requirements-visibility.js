document.addEventListener('DOMContentLoaded', () => {
  const passwordInput = document.getElementById('rpassword');
  const requirements = document.getElementById('passwordRequirements');

  passwordInput.addEventListener('focus', () => {
    requirements.classList.add('active');
  });

  passwordInput.addEventListener('blur', () => {
    if (!passwordInput.value) {
      requirements.classList.remove('active');
    }
  });
});