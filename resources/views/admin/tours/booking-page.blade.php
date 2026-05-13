@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">Specialturens bokningssida</div>
        <div class="page-subtitle">{{ $tour->title }} • {{ $tour->tour_date }} {{ substr($tour->start_time ?? '', 0, 5) }}</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.tours.show', $tour) }}" class="btn btn-outline-secondary">Tillbaka</a>
        @if($publicUrl)
            <a href="{{ $publicUrl }}" target="_blank" class="btn btn-primary">Öppna bokningssida</a>
        @endif
    </div>
</div>

<div class="page-card">
    @if($publicUrl)
        <div class="mb-3">
            <label class="form-label">Publik URL</label>
            <input type="text" class="form-control" value="{{ $publicUrl }}" readonly>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.tours.booking-page.update', $tour) }}" class="row g-3">
        @csrf
        @method('PUT')

        <div class="col-md-6">
            <label class="form-label">Slug / URL-del</label>
            <input type="text" name="slug" class="form-control" value="{{ old('slug', $bookingPage->slug) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Sidrubrik</label>
            <input type="text" name="page_title" class="form-control" value="{{ old('page_title', $bookingPage->page_title) }}" required>
        </div>

        <div class="col-12">
            <label class="form-label">Text för sidan</label>
            <textarea name="page_text" class="form-control" rows="4">{{ old('page_text', $bookingPage->page_text) }}</textarea>
        </div>

        <div class="col-md-4">
            <label class="form-label">Pris vuxen</label>
            <input type="number" step="0.01" min="0" name="adult_price" class="form-control" value="{{ old('adult_price', $bookingPage->adult_price) }}" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Pris ungdom</label>
            <input type="number" step="0.01" min="0" name="youth_price" class="form-control" value="{{ old('youth_price', $bookingPage->youth_price) }}" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Pris barn</label>
            <input type="number" step="0.01" min="0" name="child_price" class="form-control" value="{{ old('child_price', $bookingPage->child_price) }}" required>
        </div>

        <div class="col-12">
            <label class="form-label">Tacktext</label>
            <textarea name="thank_you_text" class="form-control" rows="3">{{ old('thank_you_text', $bookingPage->thank_you_text) }}</textarea>
        </div>

        <div class="col-12">
            <label class="form-label">Text för fullbokad tur</label>
            <textarea name="full_tour_text" class="form-control" rows="3">{{ old('full_tour_text', $bookingPage->full_tour_text) }}</textarea>
        </div>

        <div class="col-12">
            <label class="form-label">Bokningsvillkor</label>
            <textarea name="booking_terms" class="form-control" rows="4">{{ old('booking_terms', $bookingPage->booking_terms) }}</textarea>
        </div>

        <div class="col-12">
            <label class="form-label">Bekräftelsemail ämnesrad</label>
            <input type="text" name="confirmation_subject" class="form-control" value="{{ old('confirmation_subject', $bookingPage->confirmation_subject) }}">
        </div>

        <div class="col-12">
            <label class="form-label">Bekräftelsemail text</label>
            <textarea name="confirmation_body" class="form-control" rows="6">{{ old('confirmation_body', $bookingPage->confirmation_body) }}</textarea>
            <div class="form-text">Variabler: {{ '{' }}{{ 'contact_name' }}{{ '}' }}, {{ '{' }}{{ 'tour_title' }}{{ '}' }}, {{ '{' }}{{ 'tour_date' }}{{ '}' }}, {{ '{' }}{{ 'start_time' }}{{ '}' }}, {{ '{' }}{{ 'total_count' }}{{ '}' }}</div>
        </div>

        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="is_public" value="1" class="form-check-input" id="is_public" @checked(old('is_public', $bookingPage->is_public))>
                <label class="form-check-label" for="is_public">Bokningssidan är publik</label>
            </div>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Spara bokningssida</button>
        </div>
    </form>
</div>
@endsection