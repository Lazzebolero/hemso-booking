@props(['check'])

@php
    $statusClass = match($check['status']) {
        'ok' => 'health-ok',
        'warning' => 'health-warning',
        'error' => 'health-error',
        default => 'health-warning',
    };

    $statusLabel = match($check['status']) {
        'ok' => 'OK',
        'warning' => 'Varning',
        'error' => 'Fel',
        default => 'Okänd',
    };

    $statusIcon = match($check['status']) {
        'ok' => 'bi-check-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'error' => 'bi-x-circle-fill',
        default => 'bi-question-circle-fill',
    };
@endphp

<article class="health-card {{ $statusClass }}">
    <header class="health-card-header">
        <section class="health-card-heading">
            <span class="health-card-icon" aria-hidden="true">
                <i class="bi {{ $statusIcon }}"></i>
            </span>
            <h3 class="health-card-title">{{ $check['title'] }}</h3>
        </section>
        <span class="health-badge">{{ $statusLabel }}</span>
    </header>

    <p class="health-card-summary">{{ $check['message'] }}</p>

    @foreach($check['groups'] ?? [] as $group)
        <section class="health-detail-section">
            <h4 class="health-detail-section-title">{{ $group['title'] }}</h4>
            <table class="health-detail-table">
                <tbody>
                    @foreach($group['items'] as $label => $value)
                        <tr>
                            <th scope="row">{{ $label }}</th>
                            <td>{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
</article>
