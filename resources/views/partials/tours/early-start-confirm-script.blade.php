<script>
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-tour-early-start-form]');

        if (!form || form.dataset.earlyStartConfirmed === '1') {
            return;
        }

        if (form.dataset.requiresEarlyStartConfirm !== '1') {
            return;
        }

        event.preventDefault();

        const message = form.dataset.earlyStartMessage || 'Vill du verkligen starta denna tur?';

        if (!window.confirm(message)) {
            return;
        }

        let confirmInput = form.querySelector('input[name="confirm_early_start"]');

        if (!confirmInput) {
            confirmInput = document.createElement('input');
            confirmInput.type = 'hidden';
            confirmInput.name = 'confirm_early_start';
            confirmInput.value = '1';
            form.appendChild(confirmInput);
        }

        form.dataset.earlyStartConfirmed = '1';
        form.requestSubmit();
    });
</script>
