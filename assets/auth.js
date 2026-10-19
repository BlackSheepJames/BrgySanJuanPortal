(() => {
    'use strict';

    const passwordRule = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s]).{8,}$/;
    const roleDirectories = {
        admin: 'systemadmin/',
        captain: 'barangaycaptain/',
        secretary: 'secretary/',
        health_worker: 'healthworker/',
        tanod: 'tanod/',
        als_teacher: 'alsteacher/',
        resident: 'barangayresidents/',
    };

    const errorBox = document.querySelector('#authError');

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function clearError() {
        if (!errorBox) return;
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function validatePassword(input) {
        if (!input) return true;
        const isValid = passwordRule.test(input.value);
        input.setCustomValidity(
            isValid
                ? ''
                : 'Use at least 8 characters, including an uppercase letter, a number, and a symbol.'
        );
        return isValid;
    }

    function validateGmail(input) {
        if (!input) return true;
        const isValid = input.value.trim().toLowerCase().endsWith('@gmail.com');
        input.setCustomValidity(isValid ? '' : 'Use a personal email address ending in @gmail.com.');
        return isValid;
    }

    function validateConfirmation(password, confirmation) {
        if (!confirmation) return true;
        const isValid = confirmation.value === password.value;
        confirmation.setCustomValidity(isValid ? '' : 'The passwords do not match.');
        return isValid;
    }

    async function postApi(action, payload) {
        const response = await fetch(`api.php?action=${encodeURIComponent(action)}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const result = await response.json().catch(() => ({ error: 'Unexpected server response.' }));

        if (!response.ok) {
            throw new Error(result.error || 'Unable to complete the request.');
        }

        return result;
    }

    function goToRoleDashboard(role) {
        window.location.assign(roleDirectories[role] || 'index.php');
    }

    const loginForm = document.querySelector('#loginForm');
    const registerForm = document.querySelector('#registerForm');

    loginForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();

        const password = loginForm.elements.password;
        if (!validatePassword(password) || !loginForm.reportValidity()) return;

        const submitButton = loginForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Signing in…';

        try {
            const values = Object.fromEntries(new FormData(loginForm));
            const result = await postApi('login', values);
            goToRoleDashboard(result.user.role);
        } catch (error) {
            showError(error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Sign in <span>→</span>';
        }
    });

    registerForm?.addEventListener('input', (event) => {
        if (event.target.matches('[data-digits-only]')) {
            event.target.value = event.target.value.replace(/\D/g, '').slice(0, 15);
        }
        if (event.target.matches('[data-password-policy]')) validatePassword(event.target);
        if (event.target.matches('[data-gmail-only]')) validateGmail(event.target);
        if (event.target.matches('[data-confirm-password]')) {
            validateConfirmation(registerForm.elements.password, event.target);
        }
        if (event.target === registerForm.elements.password) {
            validateConfirmation(event.target, registerForm.elements.confirm_password);
        }
    });

    registerForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();

        const password = registerForm.elements.password;
        const confirmation = registerForm.elements.confirm_password;
        const email = registerForm.elements.email;
        validatePassword(password);
        validateConfirmation(password, confirmation);
        validateGmail(email);

        if (!registerForm.reportValidity()) return;

        const submitButton = registerForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Creating account…';

        try {
            const formData = new FormData(registerForm);
            const payload = Object.fromEntries(formData);
            payload.sectors = formData.getAll('sectors[]');
            delete payload['sectors[]'];

            const result = await postApi('register', payload);
            goToRoleDashboard(result.user.role);
        } catch (error) {
            showError(error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Create resident account <span>→</span>';
        }
    });

    if (loginForm) {
        fetch('api.php?action=me', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then((response) => response.ok ? response.json() : null)
            .then((result) => {
                if (result?.user?.role) goToRoleDashboard(result.user.role);
            })
            .catch(() => {});
    }
})();
