<div class="table-responsive-modern">
    <table class="table-modern">
        <thead>
            <tr>
                <th>Land</th>
                <th style="width: 120px;">Bokningar</th>
                <th style="width: 120px;">Personer</th>
                <th style="width: 140px;">Noterade dagar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $row['flag_url'] }}" alt="" style="width:22px;height:16px;object-fit:cover;border-radius:2px;">
                            <span class="fw-semibold">{{ $row['name'] }}</span>
                        </div>
                    </td>
                    <td>{{ $row['bookings'] }}</td>
                    <td>{{ $row['people'] }}</td>
                    <td>{{ $row['noted_days'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="muted">{{ $emptyMessage ?? 'Inga utländska länder.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
