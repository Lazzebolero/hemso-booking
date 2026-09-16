<div class="tour-availability-warning js-tour-availability-warning d-none" role="alert" aria-live="polite"></div>
<div class="tour-overbook-hint js-tour-overbook-hint d-none small-muted">
    Turen är fullbokad enligt maxantalet, men personal kan fortfarande lägga bokningen. Planera vid behov extra guide eller annan åtgärd.
</div>

@once
    <style>
        .tour-availability-warning {
            margin-top: 0.55rem;
            font-size: 0.92rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #b45309;
        }

        .tour-availability-warning.is-full {
            color: #b91c1c;
        }

        .js-tour-select.is-tour-warning {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 0.18rem rgba(245, 158, 11, 0.16) !important;
        }

        .tour-overbook-hint {
            margin-top: 0.35rem;
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .js-tour-select.is-tour-full {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 0.18rem rgba(239, 68, 68, 0.14) !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-tour-select').forEach(function (select) {
                const warning = select.parentElement?.querySelector('.js-tour-availability-warning');
                const overbookHint = select.parentElement?.querySelector('.js-tour-overbook-hint');

                if (!warning) {
                    return;
                }

                const updateWarning = function () {
                    const option = select.options[select.selectedIndex];
                    const message = option?.dataset?.availabilityWarning || '';
                    const overbookAllowed = option?.dataset?.overbookAllowed === '1';

                    warning.textContent = message ? '⚠ ' + message : '';
                    warning.classList.toggle('d-none', message === '');
                    warning.classList.toggle('is-full', overbookAllowed);
                    select.classList.toggle('is-tour-warning', message !== '' && !overbookAllowed);
                    select.classList.toggle('is-tour-full', overbookAllowed);

                    if (overbookHint) {
                        overbookHint.classList.toggle('d-none', !overbookAllowed);
                    }
                };

                select.addEventListener('change', updateWarning);
                updateWarning();
            });
        });
    </script>
@endonce
