<script>
(() => {
    const form = document.getElementById('facility-memory-form');
    const typeText = document.getElementById('memory-type-text');
    const typeAudio = document.getElementById('memory-type-audio');
    const textPanel = document.getElementById('memory-text-panel');
    const audioPanel = document.getElementById('memory-audio-panel');
    const bodyInput = document.getElementById('body');
    const audioInput = document.getElementById('audio');
    const durationInput = document.getElementById('audio_duration_seconds');
    const consentLabel = document.getElementById('consent_given_label');
    const startButton = document.getElementById('memory-audio-start');
    const stopButton = document.getElementById('memory-audio-stop');
    const retakeButton = document.getElementById('memory-audio-retake');
    const status = document.getElementById('memory-audio-status');
    const timer = document.getElementById('memory-audio-timer');
    const playback = document.getElementById('memory-audio-playback');
    const allowAudioFileUpload = @json($allowAudioFileUpload ?? false);

    const maxDurationSeconds = 600;
    let mediaRecorder = null;
    let mediaStream = null;
    let chunks = [];
    let timerInterval = null;
    let elapsedSeconds = 0;

    const isAudioMode = () => typeAudio.checked;

    const setStatus = (message) => {
        status.textContent = message;
    };

    const formatTime = (seconds) => {
        const minutes = Math.floor(seconds / 60);
        const remainder = seconds % 60;

        return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
    };

    const updatePanels = () => {
        const audioMode = isAudioMode();

        textPanel.classList.toggle('d-none', audioMode);
        audioPanel.classList.toggle('d-none', !audioMode);
        bodyInput.required = !audioMode;
        audioInput.required = audioMode && !allowAudioFileUpload;

        consentLabel.textContent = audioMode
            ? 'Personen godkände inspelning för internt arkiv.'
            : 'Personen godkände att vi sparar minnet internt för dokumentation.';
    };

    const stopStream = () => {
        if (mediaStream) {
            mediaStream.getTracks().forEach((track) => track.stop());
            mediaStream = null;
        }
    };

    const clearTimer = () => {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    };

    const resetRecordingUi = () => {
        clearTimer();
        elapsedSeconds = 0;
        chunks = [];
        timer.classList.add('d-none');
        timer.textContent = '00:00 / 10:00';
        playback.classList.add('d-none');
        playback.removeAttribute('src');
        retakeButton.classList.add('d-none');
        stopButton.disabled = true;
        startButton.disabled = false;

        if (!allowAudioFileUpload && typeof DataTransfer !== 'undefined') {
            const transfer = new DataTransfer();
            audioInput.files = transfer.files;
        }
    };

    const pickMimeType = () => {
        const candidates = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
            'audio/ogg;codecs=opus',
            'audio/ogg',
        ];

        if (typeof MediaRecorder === 'undefined') {
            return '';
        }

        return candidates.find((candidate) => MediaRecorder.isTypeSupported(candidate)) || '';
    };

    const assignAudioFile = (blob) => {
        const extension = blob.type.includes('mp4') ? 'm4a' : (blob.type.includes('ogg') ? 'ogg' : 'webm');
        const file = new File([blob], `anlaggningsminne.${extension}`, { type: blob.type || 'audio/webm' });

        if (typeof DataTransfer === 'undefined') {
            setStatus('Webbläsaren kunde inte bifoga ljudfilen. Prova en annan webbläsare.');
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        audioInput.files = transfer.files;
        durationInput.value = String(elapsedSeconds);

        playback.src = URL.createObjectURL(blob);
        playback.classList.remove('d-none');
        retakeButton.classList.remove('d-none');
        setStatus(`Inspelning klar (${formatTime(elapsedSeconds)}). Lyssna och spara när allt känns rätt.`);
    };

    const startRecording = async () => {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') {
            setStatus(allowAudioFileUpload
                ? 'Inspelning stöds inte här. Välj en ljudfil i stället.'
                : 'Den här webbläsaren stöder inte inspelning. Skriv minnet i stället.');
            return;
        }

        try {
            resetRecordingUi();
            mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const mimeType = pickMimeType();
            mediaRecorder = mimeType ? new MediaRecorder(mediaStream, { mimeType }) : new MediaRecorder(mediaStream);

            mediaRecorder.addEventListener('dataavailable', (event) => {
                if (event.data && event.data.size > 0) {
                    chunks.push(event.data);
                }
            });

            mediaRecorder.addEventListener('stop', () => {
                const blob = new Blob(chunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                assignAudioFile(blob);
                stopStream();
                startButton.disabled = false;
                stopButton.disabled = true;
            });

            mediaRecorder.start();
            startButton.disabled = true;
            stopButton.disabled = false;
            timer.classList.remove('d-none');
            setStatus('Inspelning pågår…');

            timerInterval = setInterval(() => {
                elapsedSeconds += 1;
                timer.textContent = `${formatTime(elapsedSeconds)} / ${formatTime(maxDurationSeconds)}`;

                if (elapsedSeconds >= maxDurationSeconds) {
                    stopRecording();
                }
            }, 1000);
        } catch (error) {
            stopStream();
            setStatus(allowAudioFileUpload
                ? 'Mikrofonen kunde inte startas. Välj en ljudfil i stället.'
                : 'Mikrofonen kunde inte startas. Kontrollera behörighet eller skriv minnet i stället.');
        }
    };

    const stopRecording = () => {
        clearTimer();

        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        } else {
            stopStream();
        }
    };

    typeText.addEventListener('change', updatePanels);
    typeAudio.addEventListener('change', updatePanels);
    startButton.addEventListener('click', startRecording);
    stopButton.addEventListener('click', stopRecording);
    retakeButton.addEventListener('click', startRecording);

    if (allowAudioFileUpload) {
        audioInput.addEventListener('change', () => {
            if (audioInput.files && audioInput.files.length > 0) {
                playback.src = URL.createObjectURL(audioInput.files[0]);
                playback.classList.remove('d-none');
                setStatus('Ljudfil vald. Spara när allt känns rätt.');
            }
        });
    }

    form.addEventListener('submit', (event) => {
        if (isAudioMode() && (!audioInput.files || audioInput.files.length === 0)) {
            event.preventDefault();
            setStatus(allowAudioFileUpload
                ? 'Spela in eller välj en ljudfil innan du sparar.'
                : 'Spela in ett minne innan du sparar, eller byt till att skriva.');
        }
    });

    window.addEventListener('pagehide', () => {
        clearTimer();
        stopStream();
    });

    updatePanels();
})();
</script>
