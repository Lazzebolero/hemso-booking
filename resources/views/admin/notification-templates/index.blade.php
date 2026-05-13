@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Mailmallar</h2>
        <div class="page-subtitle">Administrera systemets notifieringsmallar för e-post.</div>
    </div>
</div>

<div class="page-card compact-card mb-3">
    <div class="section-title">Ny mailmall</div>

    <form method="POST" action="{{ route('admin.notification-templates.store') }}" class="row g-3 js-template-form">
        @csrf

        <div class="col-lg-4">
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

        <div class="col-lg-2">
            <label class="form-label">Kanal</label>
            <input type="text" name="channel" class="form-control" value="{{ old('channel', 'mail') }}" required>
        </div>

        <div class="col-lg-3">
            <label class="form-label">Språk</label>
            <select name="language_code" class="form-select" required>
                @foreach($languages as $language)
                    <option value="{{ $language->code }}" @selected(old('language_code', 'sv') === $language->code)>
                        {{ $language->name }} ({{ strtoupper($language->code) }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_template_active" @checked(old('is_active', true))>
                <label class="form-check-label" for="new_template_active">Aktiv</label>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Ämne</label>
            <input
                type="text"
                name="subject"
                class="form-control form-control-lg template-subject-input js-template-subject"
                value="{{ old('subject') }}"
                required
            >
        </div>

        <div class="col-12">
            <div class="template-workbench">
                <div class="template-editor-panel">
                    <div class="template-panel-header">
                        <div>
                            <div class="template-panel-title">Innehåll</div>
                            <div class="small-muted">Skriv malltext med variabler som <code>@{{contact_name}}</code>.</div>
                        </div>
                    </div>

                    <textarea
                        name="body_html"
                        class="form-control template-body-input js-template-body"
                        rows="18"
                        required
                    >{{ old('body_html') }}</textarea>
                </div>

                <div class="template-preview-panel">
                    <div class="template-panel-header">
                        <div>
                            <div class="template-panel-title">Förhandsvisning</div>
                            <div class="small-muted">Visas som ett enkelt mail i inkorgsliknande vy.</div>
                        </div>
                    </div>

                    <div class="mail-preview-app">
                        <div class="mail-preview-toolbar">
                            <span class="mail-dot"></span>
                            <span class="mail-dot"></span>
                            <span class="mail-dot"></span>
                            <div class="mail-toolbar-title">Förhandsvisning</div>
                        </div>

                        <div class="mail-preview-header">
                            <div class="mail-avatar">H</div>
                            <div class="mail-header-text">
                                <div class="mail-from-row">
                                    <span class="mail-from-name">Hemsö bokningssystem</span>
                                    <span class="mail-time">Nu</span>
                                </div>
                                <div class="mail-to-row">
                                    till <span class="fw-semibold">kund@example.com</span>
                                </div>
                            </div>
                        </div>

                        <div class="mail-preview-subject js-preview-subject">
                            Ingen ämnesrad ännu
                        </div>

                        <div class="mail-preview-card">
                            <div class="mail-preview-body js-preview-body">
                                <span class="template-preview-placeholder">Ingen text ännu</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="template-variables-box">
                <div class="info-label mb-2">Tillgängliga variabler</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($availableVariables as $variable)
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary js-insert-variable"
                            data-variable="@{{{{ $variable }}}}"
                        >
                            @php echo '{{' . $variable . '}}'; @endphp
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="template-actions">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-save me-2"></i>Spara mailmall
                </button>
            </div>
        </div>
    </form>
</div>

<div class="page-card compact-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="section-title mb-0">Befintliga mailmallar</div>
        <div class="small-muted">{{ count($templates) }} mallar</div>
    </div>

    @forelse($templates as $template)
        <div class="template-box">
            <form method="POST" action="{{ route('admin.notification-templates.update', $template) }}" class="row g-3 js-template-form">
                @csrf
                @method('PUT')

                <div class="col-lg-4">
                    <label class="form-label">Malltyp</label>
                    <select name="template_key" class="form-select" required>
                        @foreach($availableTemplateKeys as $key => $label)
                            <option value="{{ $key }}" @selected($template->template_key === $key)">
                                {{ $label }} ({{ $key }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Kanal</label>
                    <input type="text" name="channel" class="form-control" value="{{ $template->channel }}" required>
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Språk</label>
                    <select name="language_code" class="form-select" required>
                        @foreach($languages as $language)
                            <option value="{{ $language->code }}" @selected($template->language_code === $language->code)">
                                {{ $language->name }} ({{ strtoupper($language->code) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $template->id }}" @checked($template->is_active)>
                        <label class="form-check-label" for="active_{{ $template->id }}">Aktiv</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Ämne</label>
                    <input
                        type="text"
                        name="subject"
                        class="form-control form-control-lg template-subject-input js-template-subject"
                        value="{{ $template->subject }}"
                        required
                    >
                </div>

                <div class="col-12">
                    <div class="template-workbench">
                        <div class="template-editor-panel">
                            <div class="template-panel-header">
                                <div>
                                    <div class="template-panel-title">Innehåll</div>
                                    <div class="small-muted">Redigera mallens HTML/text i en större arbetsyta.</div>
                                </div>
                            </div>

                            <textarea
                                name="body_html"
                                class="form-control template-body-input js-template-body"
                                rows="16"
                                required
                            >{{ $template->body_html }}</textarea>
                        </div>

                        <div class="template-preview-panel">
                            <div class="template-panel-header">
                                <div>
                                    <div class="template-panel-title">Förhandsvisning</div>
                                    <div class="small-muted">En mer realistisk mailvy.</div>
                                </div>
                            </div>

                            <div class="mail-preview-app">
                                <div class="mail-preview-toolbar">
                                    <span class="mail-dot"></span>
                                    <span class="mail-dot"></span>
                                    <span class="mail-dot"></span>
                                    <div class="mail-toolbar-title">Förhandsvisning</div>
                                </div>

                                <div class="mail-preview-header">
                                    <div class="mail-avatar">H</div>
                                    <div class="mail-header-text">
                                        <div class="mail-from-row">
                                            <span class="mail-from-name">Hemsö bokningssystem</span>
                                            <span class="mail-time">Nu</span>
                                        </div>
                                        <div class="mail-to-row">
                                            till <span class="fw-semibold">kund@example.com</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mail-preview-subject js-preview-subject">{{ $template->subject }}</div>

                                <div class="mail-preview-card">
                                    <div class="mail-preview-body js-preview-body">{!! nl2br(e($template->body_html)) !!}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="toolbar-inline">
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Spara</button>
                    </div>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.notification-templates.destroy', $template) }}" onsubmit="return confirm('Ta bort mailmallen?');" class="d-inline">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Ta bort</button>
            </form>
        </div>
    @empty
        <div class="muted">Inga mailmallar hittades.</div>
    @endforelse
</div>

<style>
.template-box {
    border: 1px solid var(--brand-line-soft);
    border-radius: 14px;
    padding: 1rem;
    background: #fbfdff;
    margin-bottom: 1rem;
}
.template-box:last-child {
    margin-bottom: 0;
}
.template-subject-input {
    min-height: 52px !important;
    font-size: 1rem;
    font-weight: 600;
}
.template-workbench {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(340px, 0.85fr);
    gap: 1rem;
    align-items: stretch;
}
.template-editor-panel,
.template-preview-panel {
    border: 1px solid var(--brand-line-soft);
    border-radius: 14px;
    background: #f8fafc;
    overflow: hidden;
}
.template-panel-header {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--brand-line-soft);
    background: rgba(255,255,255,0.75);
}
.template-panel-title {
    font-size: 0.95rem;
    font-weight: 800;
}
.template-body-input {
    min-height: 420px !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: #fcfdff !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    line-height: 1.55;
    font-size: 0.92rem;
    padding: 1rem !important;
    resize: vertical;
}
.template-body-input:focus {
    box-shadow: none !important;
    border-color: transparent !important;
}
.mail-preview-app {
    min-height: 100%;
    background: linear-gradient(180deg, #eef2f7 0%, #f8fafc 100%);
}
.mail-preview-toolbar {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.75rem 0.9rem;
    border-bottom: 1px solid var(--brand-line-soft);
    background: rgba(255,255,255,0.8);
}
.mail-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: #cbd5e1;
}
.mail-toolbar-title {
    margin-left: 0.4rem;
    font-size: 0.82rem;
    color: var(--text-soft);
    font-weight: 700;
}
.mail-preview-header {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 1rem 1rem 0.75rem;
}
.mail-avatar {
    width: 40px;
    height: 40px;
    border-radius: 999px;
    background: linear-gradient(135deg, #38bdf8, #2563eb);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    flex: 0 0 auto;
}
.mail-header-text {
    min-width: 0;
    flex: 1;
}
.mail-from-row {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: baseline;
}
.mail-from-name {
    font-weight: 800;
    color: var(--text-main);
}
.mail-time {
    font-size: 0.8rem;
    color: var(--text-soft);
    white-space: nowrap;
}
.mail-to-row {
    margin-top: 0.12rem;
    font-size: 0.84rem;
    color: var(--text-soft);
}
.mail-preview-subject {
    padding: 0 1rem 0.9rem;
    font-size: 1.02rem;
    font-weight: 800;
    color: var(--text-main);
}
.mail-preview-card {
    margin: 0 1rem 1rem;
    background: #fff;
    border: 1px solid var(--brand-line-soft);
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.mail-preview-body {
    min-height: 300px;
    padding: 1rem;
    white-space: pre-wrap;
    line-height: 1.65;
    color: var(--text-main);
}
.template-preview-placeholder {
    color: var(--text-soft);
}
.template-variables-box {
    flex: 1 1 520px;
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.85rem 0.95rem;
}
.template-actions {
    flex: 0 0 auto;
    display: flex;
    align-items: flex-end;
}
@media (max-width: 1100px) {
    .template-workbench {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-template-form').forEach(function (form) {
        const subjectInput = form.querySelector('.js-template-subject');
        const bodyInput = form.querySelector('.js-template-body');
        const previewSubject = form.querySelector('.js-preview-subject');
        const previewBody = form.querySelector('.js-preview-body');
        const variableButtons = form.querySelectorAll('.js-insert-variable');

        function escapeHtml(value) {
            return value
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderPreview() {
            if (previewSubject) {
                const subject = (subjectInput?.value || '').trim();
                previewSubject.textContent = subject || 'Ingen ämnesrad ännu';
            }

            if (previewBody) {
                const body = bodyInput?.value || '';
                if (body.trim() === '') {
                    previewBody.innerHTML = '<span class="template-preview-placeholder">Ingen text ännu</span>';
                } else {
                    previewBody.innerHTML = escapeHtml(body).replace(/\n/g, '<br>');
                }
            }
        }

        variableButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!bodyInput) return;

                const variable = button.dataset.variable || '';
                const start = bodyInput.selectionStart ?? bodyInput.value.length;
                const end = bodyInput.selectionEnd ?? bodyInput.value.length;
                const current = bodyInput.value;

                bodyInput.value = current.slice(0, start) + variable + current.slice(end);
                bodyInput.focus();
                bodyInput.selectionStart = bodyInput.selectionEnd = start + variable.length;

                renderPreview();
            });
        });

        if (subjectInput) {
            subjectInput.addEventListener('input', renderPreview);
        }

        if (bodyInput) {
            bodyInput.addEventListener('input', renderPreview);
        }

        renderPreview();
    });
});
</script>
@endsection