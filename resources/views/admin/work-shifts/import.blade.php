@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Importera arbetsschema</h2>
        <div class="page-subtitle">
            Planera i Excel och ladda upp när filen är klar. {{ $staffCount }} aktiva personer finns i mallen just nu.
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.work-shifts.template') }}" class="btn btn-outline-secondary">Ladda ner mall</a>
        <a href="{{ route('admin.work-shifts.index') }}" class="btn btn-outline-secondary">Tillbaka</a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="page-card">
    <p class="small-muted mb-3">
        Använd mallen. Första fliken är arbetspassen. E-post måste tillhöra en aktiv person (admin, värd, guide, trainee eller restaurang). TV-produktion ingår inte.
    </p>
    <form method="POST" action="{{ route('admin.work-shifts.import.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="file">Excel-fil</label>
            <input id="file" class="form-control" type="file" name="file" accept=".xlsx,.xls,.csv" required>
        </div>
        <button class="btn btn-primary" type="submit">Förhandsgranska</button>
    </form>
</div>
@endsection
