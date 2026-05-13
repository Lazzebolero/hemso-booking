<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $templates = NotificationTemplate::query()
            ->orderBy('template_key')
            ->orderBy('language_code')
            ->get();

        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $availableTemplateKeys = [
            'booking_confirmation' => 'Bokningsbekräftelse',
            'booking_updated' => 'Bokningsändring',
            'booking_cancelled' => 'Bokningsavbokning',
            'booking_reminder' => 'Påminnelse',
            'guide_assigned' => 'Guide tilldelad tur',
            'guide_tour_updated' => 'Guideinfo om ändrad tur',
        ];

        $availableVariables = [
            'booking_name',
            'contact_name',
            'phone',
            'email',
            'total_count',
            'status',
            'arrival_status',
            'tour_title',
            'tour_date',
            'start_time',
            'end_time',
            'guide_name',
            'guide_email',
            'tour_type',
            'languages',
        ];

        return view('admin.notification-templates.index', compact(
            'templates',
            'languages',
            'availableTemplateKeys',
            'availableVariables'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $request->validate([
            'template_key' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string', 'max:50'],
            'language_code' => ['required', 'string', 'max:10'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $exists = NotificationTemplate::query()
            ->where('template_key', $data['template_key'])
            ->where('language_code', $data['language_code'])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'template_key' => 'Det finns redan en mall med samma nyckel och språk.',
                ])
                ->withInput();
        }

        $data['is_active'] = $request->boolean('is_active');

        NotificationTemplate::create($data);

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'Mailmall skapad.');
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $request->validate([
            'template_key' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string', 'max:50'],
            'language_code' => ['required', 'string', 'max:10'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $exists = NotificationTemplate::query()
            ->where('template_key', $data['template_key'])
            ->where('language_code', $data['language_code'])
            ->where('id', '!=', $notificationTemplate->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'template_key' => 'Det finns redan en mall med samma nyckel och språk.',
            ]);
        }

        $data['is_active'] = $request->boolean('is_active');

        $notificationTemplate->update($data);

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'Mailmall uppdaterad.');
    }

    public function destroy(NotificationTemplate $notificationTemplate)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $notificationTemplate->delete();

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'Mailmall borttagen.');
    }
}