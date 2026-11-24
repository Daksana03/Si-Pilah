document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn?.querySelector('.btn-text');
    const btnLoader = submitBtn?.querySelector('.btn-loader');

    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');

    // 1. Toggle Password Visibility
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', () => {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

            // Swap SVG icon between eye and eye-off
            if (isPassword) {
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        });
    }

    // 2. Clear inline errors on user typing
    emailInput?.addEventListener('input', () => {
        emailInput.classList.remove('is-invalid');
        emailError.textContent = '';
    });

    passwordInput?.addEventListener('input', () => {
        passwordInput.classList.remove('is-invalid');
        passwordError.textContent = '';
    });

    // 3. Client-side validation & Loading State
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            let isValid = true;
            const emailVal = emailInput.value.trim();
            const passwordVal = passwordInput.value;

            // Validate Email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailVal) {
                emailError.textContent = 'Alamat email wajib diisi.';
                emailInput.classList.add('is-invalid');
                isValid = false;
            } else if (!emailRegex.test(emailVal)) {
                emailError.textContent = 'Format alamat email tidak valid.';
                emailInput.classList.add('is-invalid');
                isValid = false;
            }

            // Validate Password
            if (!passwordVal) {
                passwordError.textContent = 'Kata sandi wajib diisi.';
                passwordInput.classList.add('is-invalid');
                isValid = false;
            } else if (passwordVal.length < 6) {
                passwordError.textContent = 'Kata sandi minimal 6 karakter.';
                passwordInput.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                return;
            }

            // Show loading state to prevent double submission
            submitBtn.disabled = true;
            if (btnText) btnText.hidden = true;
            if (btnLoader) btnLoader.hidden = false;
        });
    }
});