<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <title>Löneunderlag</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .muted {
            color: #6b7280;
        }

        .meta {
            width: 100%;
            margin-bottom: 18px;
            border-collapse: collapse;
        }

        .meta td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
        }

        .meta-label {
            width: 28%;
            color: #6b7280;
            background: #f9fafb;
        }

        table.entries {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        table.entries th {
            background: #f3f4f6;
            text-align: left;
            font-size: 11px;
            padding: 7px 6px;
            border: 1px solid #d1d5db;
        }

        table.entries td {
            padding: 7px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .total-box {
            margin-top: 18px;
            padding: 12px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .total-label {
            color: #6b7280;
            font-size: 12px;
        }

        .total-value {
            font-size: 20px;
            font-weight: bold;
        }

        .signature {
            margin-top: 42px;
            width: 100%;
        }

        .signature td {
            width: 50%;
            padding-top: 36px;
            border-top: 1px solid #111827;
            font-size: 11px;
        }

        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="title">Löneunderlag</div>
    <div class="muted">Godkända tider för vald löneperiod</div>
</div>

<table class="meta">
    <tr>
        <td class="meta-label">Namn</td>
        <td>{{ $user->name }}</td>
    </tr>
    <tr>
        <td class="meta-label">Roll</td>
        <td>{{ $user->role }}</td>
    </tr>
    <tr>
        <td class="meta-label">Period</td>
        <td>{{ $period['label'] }}</td>
    </tr>
    <tr>
        <td class="meta-label">Skapad</td>
        <td>{{ $generatedAt->format('Y-m-d H:i') }}</td>
    </tr>
</table>

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
        @forelse($entries as $entry)
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
        @empty
            <tr>
                <td colspan="6">Inga godkända tider för vald period.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="total-box">
    <div class="total-label">Total godkänd arbetstid</div>
    <div class="total-value">{{ $totalFormatted }}</div>
</div>

<table class="signature">
    <tr>
        <td>Datum / Admin</td>
        <td>Datum / Personal</td>
    </tr>
</table>

<div class="page-footer">
    Löneunderlag genererat från tidsredovisningen. Originalstämplingar och ändringslogg finns i systemet.
</div>

</body>
</html>
