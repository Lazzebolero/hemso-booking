<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourBookingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TourBookingPageController extends Controller
{
    public function edit(Tour $tour)
    {
        $tour->load('bookingPage');

        $bookingPage = $tour->bookingPage ?? new TourBookingPage([
            'tour_id' => $tour->id,
            'page_title' => $tour->title,
            'slug' => Str::slug($tour->title . '-' . optional($tour->tour_date)->format('Y-m-d')),
            'thank_you_text' => 'Tack för din bokning.',
            'full_tour_text' => 'Denna tur är fullbokad.',
            'booking_terms' => 'Bokningen är bindande enligt angivna villkor.',
            'confirmation_subject' => 'Bokningsbekräftelse',
            'confirmation_body' => "Hej {{contact_name}},\n\nTack för din bokning till {{tour_title}} den {{tour_date}} kl {{start_time}}.\nAntal personer: {{total_count}}.\n\nVälkommen!",
            'is_public' => true,
        ]);

        $publicUrl = $bookingPage->slug
            ? route('public.tour-booking.show', $bookingPage->slug)
            : null;

        return view('admin.tours.booking-page', compact('tour', 'bookingPage', 'publicUrl'));
    }

    public function update(Request $request, Tour $tour)
    {
        $bookingPage = $tour->bookingPage ?? new TourBookingPage(['tour_id' => $tour->id]);

        $data = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tour_booking_pages', 'slug')->ignore($bookingPage->id),
            ],
            'page_title' => ['required', 'string', 'max:255'],
            'page_text' => ['nullable', 'string'],
            'thank_you_text' => ['nullable', 'string'],
            'full_tour_text' => ['nullable', 'string'],
            'booking_terms' => ['nullable', 'string'],
            'adult_price' => ['required', 'numeric', 'min:0'],
            'youth_price' => ['required', 'numeric', 'min:0'],
            'child_price' => ['required', 'numeric', 'min:0'],
            'confirmation_subject' => ['nullable', 'string', 'max:255'],
            'confirmation_body' => ['nullable', 'string'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $data['is_public'] = $request->boolean('is_public');

        $bookingPage->fill($data);
        $bookingPage->tour_id = $tour->id;
        $bookingPage->save();

        return redirect()
            ->route('admin.tours.booking-page.edit', $tour)
            ->with('success', 'Bokningssidan sparades.');
    }
}