const form = document.getElementById('registration-form');

if (form) {
    const password = form.elements.namedItem('password');
    const confirmPassword = form.elements.namedItem('confirmPassword');
    const errorMessage = document.getElementById('registration-error');
    const submitButton = form.querySelector('button[type="submit"]');
    let loading = false;

    password.addEventListener('input', () => {
        password.setCustomValidity('');
        confirmPassword.setCustomValidity('');
    });

    confirmPassword.addEventListener('input', () => confirmPassword.setCustomValidity(''));

    const post = async (url, data) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            credentials: 'include',
            signal: AbortSignal.timeout(10000),
            body: JSON.stringify(data),
        });
        const result = await response.json();
        if (!response.ok) {
            let message = result.error;

            if (!message)
                message = result.message;

            if (!message && response.status === 401) {
                message = 'Invalid email or password';
            }

            if (!message)
                message = 'An error occurred. Please try again.';

            throw new Error(message);
        }
        return result;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (loading)
            return;

        errorMessage.hidden = true;
        errorMessage.textContent = '';

        if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/.test(password.value)) {
            password.setCustomValidity(
                'Password must be at least 8 characters and include lowercase, uppercase, number, and special character'
            );
            password.reportValidity();
            return;
        }

        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
            confirmPassword.reportValidity();
            return;
        }

        if (!form.reportValidity())
            return;

        const data = {
            email: form.elements.namedItem('email').value.trim(),
            password: password.value,
            confirmPassword: confirmPassword.value,
            username: form.elements.namedItem('username').value,
            agreeTerms: true,
        };

        loading = true;
        const controls = form.elements;

        for (const control of controls)
            control.disabled = true;

        submitButton.textContent = 'Registering...';

        try {
            await post(form.action, data);

            const login = await post(form.dataset.loginUrl, {
                email: data.email,
                password: data.password,
            });

            if (!login.token)
                throw new Error('Login failed: No token received');

            localStorage.setItem('authToken', login.token);

            if (login.refresh_token)
                localStorage.setItem('refreshToken', login.refresh_token);

            window.location.assign(form.dataset.successUrl);
        } catch (error) {
            errorMessage.textContent = error.message || 'An error occurred during registration. Please try again.';
            errorMessage.hidden = false;
        } finally {
            loading = false;

            for (const control of controls)
                control.disabled = false;

            submitButton.textContent = 'Register';
        }
    });
}
