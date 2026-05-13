@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Mailmallar</h2>
        <div class="muted">Administrera systemets notifieringsmallar för e-post.</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Det finns fel i formuläret:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="page-card mb-4">
            <div class="section-title">Ny mailmall</div>

            <form method="POST" action="{{ route('admin.notification-templates.store') }}" class="row g-3">
                @csrf

                <div class="col-12">
                    <label class="form-label">Malltyp</label>
                    <select name="template_key" class="form-select" required>
                        <option value="">Välj malltyp</option>
                        @foreach($availableTemplateKeys as $key => $label)
                            <option value="{{ $key }}" @selected(old('template_key') === $key)>
                                {{ $label }} ({{ $key }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Kanal</label>
                    <input type="text" name="channel" class="form-control" value="{{ old('channel', 'mail') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Språk</label>
                    <select name="language_code" class="form-select" required>
                        @foreach($languages as $language)
                            <option value="{{ $language->code }}" @selected(old('language_code', 'sv') === $language->code)>
                                {{ $language->name }} ({{ strtoupper($language->code) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_template_active" @checked(old('is_active', true))>
                        <label class="form-check-label" for="new_template_active">Aktiv</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Ämne</label>
                    <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Innehåll</label>
                    <textarea name="body_html" class="form-control" rows="10" required>{{ old('body_html') }}</textarea>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Spara mailmall</button>
                </div>
            </form>
        </div>

        <div class="page-card">
            <div class="section-title">Tillgängliga variabler</div>
            <div class="small muted mb-3">
                Dessa kan användas i ämne och innehåll.
            </div>

            <div class="d-flex flex-wrap gap-2">
                @foreach($availableVariables as $variable)
                    <span class="badge text-bg-light border">@php echo '{{' . $variable . '}}'; @endphp</span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="page-card">
            <div class="section-title">Befintliga mailmallar</div>

            @forelse($templates as $template)
                <div class="border rounded p-3 mb-4">
                    <form method="POST" action="{{ route('admin.notification-templates.update', $template) }}" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-md-4">
                            <label class="form-label">Malltyp</label>
                            <select name="template_key" class="form-select" required>
                                @foreach($availableTemplateKeys as $key => $label)
                                    <option value="{{ $key }}" @selected($template->template_key === $key)>
                                        {{ $label }} ({{ $key }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Kanal</label>
                            <input type="text" name="channel" class="form-control" value="{{ $template->channel }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Språk</label>
                            <select name="language_code" class="form-select" required>
                                @foreach($languages as $language)
                                    <option value="{{ $language->code }}" @selected($template->language_code === $language->code)>
                                        {{ $language->name }} ({{ strtoupper($language->code) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $template->id }}" @checked($template->is_active)>
                                <label class="form-check-label" for="active_{{ $template->id }}">Aktiv</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Ämne</label>
                            <input type="text" name="subject" class="form-control" value="{{ $template->subject }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Innehåll</label>
                            <textarea name="body_html" class="form-control" rows="8" required>{{ $template->body_html }}</textarea>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm">Spara</button>
                    </form>

                            <form method="POST" action="{{ route('admin.notification-templates.destroy', $template) }}" onsubmit="return confirm('Ta bort mailmallen?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Ta bort</button>
                            </form>
                        </div>
                </div>
            @empty
                <div class="muted">Inga mailmallar hittades.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection