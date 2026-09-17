window.posFetch = (url, options = {}) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const headers = Object.assign({
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token || '',
    }, options.headers || {});

    return fetch(url, Object.assign({}, options, { headers }));
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-toggle-category]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.getAttribute('data-toggle-category');
            const response = await window.posFetch(url, { method: 'POST' });
            const data = await response.json();
            button.textContent = data.is_active ? 'Enabled' : 'Disabled';
            button.classList.toggle('btn-success', data.is_active);
            button.classList.toggle('btn-outline-secondary', !data.is_active);
        });
    });
});
