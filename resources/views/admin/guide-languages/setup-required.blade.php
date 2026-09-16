@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Guide på språk</h2>
        <div class="page-subtitle">
            Funktionen är inte aktiverad i databasen ännu.
        </div>
    </div>
</div>

<div class="page-card">
    <div class="alert alert-warning mb-3">
        Tabellen <code>guide_language</code> saknas. Det ger 500-fel tills migrationen körts på servern.
    </div>

    <p class="mb-3">
        Kör på servern:
    </p>

    <pre class="bg-light border rounded p-3 mb-3"><code>php artisan migrate --force</code></pre>

    <p class="small-muted mb-0">
        Kontrollera att filen
        <code>database/migrations/2026_06_17_110000_create_guide_language_table.php</code>
        finns uppladdad innan du migrerar.
    </p>
</div>
@endsection
