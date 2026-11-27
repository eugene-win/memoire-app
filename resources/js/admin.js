const adminForms = document.querySelectorAll('form[data-admin-form]');
const adminFeedback = document.querySelector('.admin-feedback');
const savedFeedback = window.sessionStorage.getItem('admin-success');

if (savedFeedback && adminFeedback) {
    adminFeedback.hidden = false;
    adminFeedback.textContent = savedFeedback;
    window.sessionStorage.removeItem('admin-success');
}

adminForms.forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const feedback = adminFeedback;
        const method = form.querySelector('[name="_method"]')?.value?.toUpperCase() || 'POST';
        const token = form.querySelector('[name="_token"]')?.value;
        const headers = { Accept: 'application/json' };
        let body = new FormData(form);

        if (token) headers['X-CSRF-TOKEN'] = token;

        if (form.hasAttribute('data-admin-json')) {
            const payload = {};
            body.forEach((value, key) => {
                if (!['_token', '_method', 'permissions[]'].includes(key)) payload[key] = value;
            });
            payload.permissions = [...form.querySelectorAll('input[name="permissions[]"]:checked')]
                .map((input) => input.value);
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(payload);
        }

        if (feedback) {
            feedback.hidden = false;
            feedback.classList.remove('is-error');
            feedback.textContent = 'Enregistrement en cours…';
        }

        try {
            const response = await fetch(form.action, {
                method: form.hasAttribute('data-admin-json') ? method : 'POST',
                headers,
                body,
                credentials: 'same-origin',
            });
            const result = await response.json();

            if (!response.ok) {
                const messages = Object.values(result.errors || {}).flat();
                throw new Error(messages.join(' ') || result.message || 'La modification n’a pas pu être enregistrée.');
            }

            window.sessionStorage.setItem('admin-success', 'Modifications enregistrées.');
            window.setTimeout(() => window.location.reload(), 500);
        } catch (error) {
            if (feedback) {
                feedback.hidden = false;
                feedback.classList.add('is-error');
                feedback.textContent = error.message || 'Une erreur est survenue pendant l’enregistrement.';
            }
        }
    });
});
