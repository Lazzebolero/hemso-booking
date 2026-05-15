@props(['filter'])

<a href="{{ route('time.index', ['filter' => 'today']) }}"
   class="btn btn-sm {{ $filter === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">Idag</a>

<a href="{{ route('time.index', ['filter' => 'week']) }}"
   class="btn btn-sm {{ $filter === 'week' ? 'btn-primary' : 'btn-outline-secondary' }}">Vecka</a>

<a href="{{ route('time.index', ['filter' => 'month']) }}"
   class="btn btn-sm {{ $filter === 'month' ? 'btn-primary' : 'btn-outline-secondary' }}">Månad</a>

<a href="{{ route('time.index', ['filter' => '30days']) }}"
   class="btn btn-sm {{ $filter === '30days' ? 'btn-primary' : 'btn-outline-secondary' }}">30 dagar</a>
