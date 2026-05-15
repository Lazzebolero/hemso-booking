@extends($useGuideLayout ? 'layouts.guide' : 'layouts.app')

@section('content')
<div class="staff-page-stack">
    <x-ui.page-header
        :guide="$useGuideLayout"
        title="Mina besökshundar"
        subtitle="Registreringar du har lagt in. Standard: dagens datum."
        icon="bi-heart-pulse"
    >
        <x-slot:actions>
            <a href="{{ route('visitor-dogs.create') }}" class="btn btn-primary{{ $useGuideLayout ? ' btn-sm' : '' }}">
                <i class="bi bi-plus-lg me-1"></i>Ny registrering
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @include('partials.ui.flash-messages', ['guide' => $useGuideLayout])

    <div class="{{ $useGuideLayout ? 'guide-card' : 'page-card' }}">
        @include('partials.visitor-dogs.date-filter', [
            'action' => route('visitor-dogs.index'),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'resetUrl' => route('visitor-dogs.index'),
        ])
    </div>

    <div class="{{ $useGuideLayout ? 'guide-card' : 'page-card' }}">
        @if($dogs->isEmpty())
            <p class="text-muted mb-0">Inga registreringar i valt intervall.</p>
        @else
            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Namn</th>
                            <th>Ras</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dogs as $dog)
                            <tr>
                                <td>{{ $dog->visit_date?->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $dog->dog_name }}</td>
                                <td>{{ $dog->breed ?: '—' }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ \App\Support\VisitorDogSupport::routeForDog('visitor-dogs.show', $dog, \App\Support\VisitorDogSupport::linkQueryForReturn(request(), \App\Support\VisitorDogSupport::RETURN_MINE)) }}" class="btn btn-sm btn-outline-primary">Visa</a>
                                    <a href="{{ \App\Support\VisitorDogSupport::routeForDog('visitor-dogs.edit', $dog, \App\Support\VisitorDogSupport::linkQueryForReturn(request(), \App\Support\VisitorDogSupport::RETURN_MINE)) }}" class="btn btn-sm btn-outline-secondary">Redigera</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $dogs->links() }}</div>
        @endif
    </div>
</div>
@endsection
