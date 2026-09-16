<script>
(function () {
    const form = document.querySelector('[data-time-clock-form]');
    if (!form) {
        return;
    }

    const setField = function (name, value) {
        const input = form.querySelector('input[name="' + name + '"]');
        if (input) {
            input.value = value ?? '';
        }
    };

    const submitForm = function () {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    };

    form.addEventListener('submit', function (event) {
        if (form.dataset.geoReady === '1') {
            form.dataset.geoReady = '';
            return;
        }

        event.preventDefault();

        if (!navigator.geolocation) {
            setField('location_status', 'unsupported');
            form.dataset.geoReady = '1';
            submitForm();
            return;
        }

        const timeoutId = window.setTimeout(function () {
            setField('location_status', 'timeout');
            form.dataset.geoReady = '1';
            submitForm();
        }, 8000);

        navigator.geolocation.getCurrentPosition(
            function (position) {
                window.clearTimeout(timeoutId);
                setField('latitude', String(position.coords.latitude));
                setField('longitude', String(position.coords.longitude));
                setField('accuracy_m', String(Math.round(position.coords.accuracy || 0)));
                setField('location_status', 'ok');
                form.dataset.geoReady = '1';
                submitForm();
            },
            function (error) {
                window.clearTimeout(timeoutId);
                setField('location_status', error && error.code === 1 ? 'denied' : 'unavailable');
                form.dataset.geoReady = '1';
                submitForm();
            },
            { enableHighAccuracy: true, timeout: 7000, maximumAge: 60000 }
        );
    });
})();
</script>
