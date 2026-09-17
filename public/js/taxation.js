(() => {
    const country = document.getElementById('tax-country');
    const currency = document.getElementById('tax-currency');
    const authority = document.getElementById('tax-authority');
    country?.addEventListener('change', () => {
        const option = country.selectedOptions[0];
        if (option?.dataset.currency && currency) {
            currency.value = option.dataset.currency;
        }
        if (option?.dataset.authority && authority) {
            authority.value = option.dataset.authority;
        }
    });
})();
