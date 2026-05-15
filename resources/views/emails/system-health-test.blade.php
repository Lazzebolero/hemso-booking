<p>Hej {{ $user->name }},</p>

<p>Detta är ett testmail från <strong>{{ $appName }}</strong> (systemhälsa).</p>

<p>
    Skickat: {{ $sentAt }}<br>
    Miljö: {{ $environment }}
</p>

<p>Om du läser detta fungerar e-postkonfigurationen för applikationen.</p>
