<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <title>Löneunderlag alla</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .page-break {
            page-break-after: always;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
        }

        .muted {
            color: #6b7280;
        }

        .person-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 8px;
        }

        table.entries {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.entries th {
            background: #f3f4f6;
            text-align: left;
            padding: 6px;
            border: 1px solid #d1d5db;
        }

        table.entries td {
            padding: 6px;
            border: 1px solid #e5e7eb;
        }

        .text-right {
            text-align: right;
        }

        .total {
            margin-top: 10px;
            padding: 9px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            font-weight: bold;
        }

        .signature {
            margin-top: 34px;
            width: 100%;
        }

        .signature td {
            width: 50%;
            padding-top: 28px;
            border-top: 1px solid #111827;
            font-size: 10px;
        }
    </style>
</head>
<body>

@foreach($usersWithEntries as $data)
    <div class="header">
        <div class="title">Löneunderlag</div>
        <div class="muted">
            {{ $period['label'] }} | Skapad {{ $generatedAt->format('Y-m-d H:i') }}
        </div>
    </div>

    <div class="person-title">
        {{ $data['user']->name }} <span class="muted">({{ $data['user']->role }})</span>
    </div>

    <table class="entries">
        <thead>
            <tr>
                <th>Datum</th>
                <th>Start</th>
                <th>Slut</th>
                <th class="text-right">Rast</th>
                <th class="text-right">Tid</th>
                <th>Kommentar</th>
            </tr>
        </thead>

        <tbody>
            @foreach($data['entries'] as $entry)
                <tr>
                    <td>{{ optional($entry->work_date)->format('Y-m-d') }}</td>
                    <td>{{ optional($entry->start_at)->format('H:i') }}</td>
                    <td>{{ optional($entry->end_at)->format('H:i') }}</td>
                    <td class="text-right">{{ (int) $entry->break_minutes }} min</td>
                    <td class="text-right">{{ $entry->worked_hours_formatted }}</td>
                    <td>
                        @if($entry->user_comment)
                            Personal: {{ $entry->user_comment }}<br>
                        @endif

                        @if($entry->admin_comment)
                            Admin: {{ $entry->admin_comment }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total godkänd arbetstid: {{ $data['totalFormatted'] }}
    </div>

    <table class="signature">
        <tr>
            <td>Datum / Admin</td>
            <td>Datum / Personal</td>
        </tr>
    </table>

    @if(! $loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>
