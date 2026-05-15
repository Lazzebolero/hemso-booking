<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminLockedPayrollPeriodController;
use App\Http\Controllers\Admin\AdminPayrollPdfController;
use App\Http\Controllers\Admin\AdminTimeControlPanelController;
use App\Http\Controllers\Admin\AdminTimeCsvExportController;
use App\Http\Controllers\Admin\AdminTimeEntryController;
use App\Http\Controllers\Admin\AdminTimeExportController;
use App\Http\Controllers\Admin\BackupCheckController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacilityReportController;
use App\Http\Controllers\Admin\GuideAvailabilityController;
use App\Http\Controllers\Admin\GuideStatisticsController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\LoginEventController;
use App\Http\Controllers\Admin\NotificationLogController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\QuickBookingController;
use App\Http\Controllers\Admin\ReportOptionController;
use App\Http\Controllers\Admin\ReportSettingsController;
use App\Http\Controllers\Admin\RestaurantBoardController;
use App\Http\Controllers\Admin\SecurityOverviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SpecialTourController;
use App\Http\Controllers\Admin\StaffDocumentController as AdminStaffDocumentController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\SystemMessageController;
use App\Http\Controllers\Admin\SystemMessageStatusController;
use App\Http\Controllers\Admin\TourBatchController;
use App\Http\Controllers\Admin\TourController;
use App\Http\Controllers\Admin\TourTypeController;
use App\Http\Controllers\Admin\VisitorDogController as AdminVisitorDogController;
use App\Http\Controllers\Admin\WorkShiftController;
use App\Http\Controllers\Admin\WorkShiftTemplateController;
use App\Http\Controllers\AppPulseController;
use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\Guide\DashboardController as GuideDashboardController;
use App\Http\Controllers\Guide\FacilityReportController as GuideFacilityReportController;
use App\Http\Controllers\Host\HostEntryController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MyScheduleController;
use App\Http\Controllers\PublicTourBookingController;
use App\Http\Controllers\QuickTourController;
use App\Http\Controllers\RestaurantStatisticsController;
use App\Http\Controllers\RoleSelectionController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffDocumentController as StaffStaffDocumentController;
use App\Http\Controllers\Staff\StaffScheduleController;
use App\Http\Controllers\TimeClockController;
use App\Http\Controllers\VisitorDogController;
use App\Support\ActiveRoleRedirect;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PWA / offline (explicit routes so PHPUnit hits files; production web server
| often serves these from public/ before Laravel).
|--------------------------------------------------------------------------
*/

Route::get('/manifest.webmanifest', function () {
    return response()->json([
        'name' => 'Hemsö Fästning Bokning',
        'short_name' => 'Hemsö',
        'description' => 'Bokning, schema och tidrapportering',
        'start_url' => url('/'),
        'scope' => url('/'),
        'display' => 'standalone',
        'background_color' => '#f8fafc',
        'theme_color' => '#0f172a',
        'icons' => [
            [
                'src' => asset('icons/pwa-icon-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
            [
                'src' => asset('icons/pwa-icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ],
    ], 200, [
        'Content-Type' => 'application/manifest+json; charset=UTF-8',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
})->name('pwa.manifest');

Route::get('/service-worker.js', function () {
    $path = public_path('service-worker.js');
    abort_unless(File::isFile($path), 404);

    return response(File::get($path), 200, [
        'Content-Type' => 'application/javascript; charset=UTF-8',
    ]);
})->name('pwa.service-worker');

Route::get('/offline.html', function () {
    $path = public_path('offline.html');
    abort_unless(File::isFile($path), 404);

    return response(File::get($path), 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
})->name('pwa.offline');

Route::get('/js/offline-queue.js', function () {
    $path = public_path('js/offline-queue.js');
    abort_unless(File::isFile($path), 404);

    return response(File::get($path), 200, [
        'Content-Type' => 'application/javascript; charset=UTF-8',
    ]);
})->name('pwa.offline-queue');

/*
|--------------------------------------------------------------------------
| Start
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});
/*
|--------------------------------------------------------------------------
| Gemensamt efter inloggning
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $activeRole = session('active_role');

        if (! $activeRole || ! $user->canActivateRole($activeRole)) {
            session()->forget('active_role');

            return redirect()->route('role.select');
        }

        return redirect()->route(
            ActiveRoleRedirect::routeNameFor($activeRole, $user)
        );
    })->name('dashboard');

    Route::get('/select-role', [RoleSelectionController::class, 'create'])
        ->name('role.select');

    Route::post('/select-role', [RoleSelectionController::class, 'store'])
        ->name('role.store');

    Route::post('/switch-role', [RoleSelectionController::class, 'store'])
        ->name('role.switch');

    Route::get('/quick-tours/create', [QuickTourController::class, 'create'])
        ->name('quick-tours.create');

    Route::post('/quick-tours', [QuickTourController::class, 'store'])
        ->name('quick-tours.store');

    Route::post('/system-messages/{systemMessage}/read', [SystemMessageStatusController::class, 'read'])
        ->name('system-messages.read');

    Route::post('/system-messages/{systemMessage}/dismiss', [SystemMessageStatusController::class, 'dismiss'])
        ->name('system-messages.dismiss');

    Route::post('/system-messages/{systemMessage}/restore', [SystemMessageStatusController::class, 'restore'])
        ->name('system-messages.restore');

    Route::post('/system-messages/{systemMessage}/acknowledge', [SystemMessageStatusController::class, 'acknowledge'])
        ->name('system-messages.acknowledge');

    Route::get('/system-messages/live/panel', [SystemMessageController::class, 'livePanel'])
        ->name('system-messages.live-panel');

    Route::get('/system-messages/force-popup/panel', [SystemMessageController::class, 'forcePopupPanel'])
        ->name('system-messages.force-popup-panel');
    Route::middleware(['auth'])->get('/app/pulse', AppPulseController::class)
        ->name('app.pulse');
});
/*
|--------------------------------------------------------------------------
| Publika bokningssidor
|--------------------------------------------------------------------------
*/

Route::get('/tour-booking/{slug}', [PublicTourBookingController::class, 'show'])
    ->name('public.tour-booking.show');

Route::post('/tour-booking/{slug}', [PublicTourBookingController::class, 'store'])
    ->name('public.tour-booking.store');

/*
|--------------------------------------------------------------------------
| Meddelanden och gruppchattar
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role'])
    ->prefix('messages')
    ->name('messages.')
    ->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/create', [MessageController::class, 'create'])->name('create');
        Route::post('/direct', [MessageController::class, 'storeDirect'])->name('direct.store');

        Route::get('/{conversation}', [MessageController::class, 'show'])->name('show');
        Route::post('/{conversation}/send', [MessageController::class, 'send'])->name('send');
        Route::post('/{conversation}/read', [MessageController::class, 'markRead'])->name('read');
    });

Route::middleware(['auth', 'ensure.active.role'])
    ->prefix('group-chats')
    ->name('group-chats.')
    ->group(function () {
        Route::get('/create', [GroupChatController::class, 'create'])->name('create');
        Route::post('/', [GroupChatController::class, 'store'])->name('store');
    });

/*
|--------------------------------------------------------------------------
| Eget schema
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role'])->group(function () {
    Route::get('/my-schedule', [MyScheduleController::class, 'index'])
        ->name('my-schedule.index');
});

/*
|--------------------------------------------------------------------------
| Personal / mobil personalyta
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/', [StaffDashboardController::class, 'index'])->name('dashboard');
        Route::get('/schedule', [StaffScheduleController::class, 'index'])->name('schedule');

        Route::get('/documents', [StaffStaffDocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{staffDocument}', [StaffStaffDocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{staffDocument}/preview', [StaffStaffDocumentController::class, 'preview'])->name('documents.preview');
        Route::get('/documents/{staffDocument}/download', [StaffStaffDocumentController::class, 'download'])->name('documents.download');
    });

/*
|--------------------------------------------------------------------------
| Besökshundar (guide och värd)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.roles:guide,host'])
    ->prefix('besokshundar')
    ->name('visitor-dogs.')
    ->group(function () {
        Route::get('mina', [VisitorDogController::class, 'index'])->name('index');
        Route::get('/', [VisitorDogController::class, 'create'])->name('create');
        Route::post('/', [VisitorDogController::class, 'store'])->name('store');
        Route::get('{visitorDog}/edit', [VisitorDogController::class, 'edit'])->name('edit');
        Route::put('{visitorDog}', [VisitorDogController::class, 'update'])->name('update');
        Route::get('{visitorDog}/photo', [VisitorDogController::class, 'photo'])->name('photo');
        Route::get('{visitorDog}', [VisitorDogController::class, 'show'])->name('show');
        Route::delete('{visitorDog}', [VisitorDogController::class, 'destroy'])->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('visitor-dogs', [AdminVisitorDogController::class, 'index'])->name('visitor-dogs.index');
        Route::get('visitor-dogs/gallery', [AdminVisitorDogController::class, 'gallery'])->name('visitor-dogs.gallery');
        Route::get('visitor-dogs/{visitorDog}/edit', [AdminVisitorDogController::class, 'edit'])->name('visitor-dogs.edit');
        Route::put('visitor-dogs/{visitorDog}', [AdminVisitorDogController::class, 'update'])->name('visitor-dogs.update');
        Route::get('visitor-dogs/{visitorDog}/photo', [AdminVisitorDogController::class, 'photo'])->name('visitor-dogs.photo');
        Route::get('visitor-dogs/{visitorDog}', [AdminVisitorDogController::class, 'show'])->name('visitor-dogs.show');
        Route::delete('visitor-dogs/{visitorDog}', [AdminVisitorDogController::class, 'destroy'])->name('visitor-dogs.destroy');

        Route::get('tours/batch-create', [TourBatchController::class, 'create'])->name('tours.batch-create');
        Route::post('tours/batch-create', [TourBatchController::class, 'store'])->name('tours.batch-store');

        Route::post('tours/{tour}/start', [TourController::class, 'start'])->name('tours.start');
        Route::post('tours/{tour}/complete', [TourController::class, 'complete'])->name('tours.complete');
        Route::post('tours/{tour}/cancel', [TourController::class, 'cancel'])->name('tours.cancel');

        Route::resource('tours', TourController::class);
        Route::resource('special-tours', SpecialTourController::class)
            ->parameters(['special-tours' => 'tour']);

        Route::get('guides/availability', [GuideAvailabilityController::class, 'index'])->name('guides.availability');

        Route::get('bookings/export-csv', [BookingController::class, 'exportCsv'])->name('bookings.export-csv');
        Route::get('quick-bookings/create', [QuickBookingController::class, 'create'])->name('bookings.quick-create');
        Route::post('quick-bookings', [QuickBookingController::class, 'store'])->name('bookings.quick-store');
        Route::patch('bookings/{booking}/participants', [BookingController::class, 'quickUpdateParticipants'])->name('bookings.quick-update-participants');
        Route::patch('bookings/{booking}/move', [BookingController::class, 'move'])->name('bookings.move');
        Route::patch('bookings/{booking}/arrival', [BookingController::class, 'markArrival'])->name('bookings.mark-arrival');
        Route::resource('bookings', BookingController::class)->except(['show']);

        Route::get('work-shifts/staffing', [WorkShiftController::class, 'staffing'])
            ->name('work-shifts.staffing');
        Route::get('work-shifts', [WorkShiftController::class, 'index'])->name('work-shifts.index');
        Route::get('work-shifts/create', [WorkShiftController::class, 'create'])->name('work-shifts.create');
        Route::post('work-shifts', [WorkShiftController::class, 'store'])->name('work-shifts.store');
        Route::get('work-shifts/{workShift}/edit', [WorkShiftController::class, 'edit'])->name('work-shifts.edit');
        Route::put('work-shifts/{workShift}', [WorkShiftController::class, 'update'])->name('work-shifts.update');
        Route::delete('work-shifts/{workShift}', [WorkShiftController::class, 'destroy'])->name('work-shifts.destroy');

        Route::post('work-shifts/copy-week', [WorkShiftController::class, 'copyWeek'])->name('work-shifts.copy-week');
        Route::post('work-shifts/copy-day', [WorkShiftController::class, 'copyDay'])->name('work-shifts.copy-day');
        Route::post('work-shifts/copy-day-many', [WorkShiftController::class, 'copyDayToMany'])->name('work-shifts.copy-day-many');

        Route::get('work-shift-templates', [WorkShiftTemplateController::class, 'index'])->name('work-shift-templates.index');
        Route::post('work-shift-templates', [WorkShiftTemplateController::class, 'store'])->name('work-shift-templates.store');
        Route::put('work-shift-templates/{workShiftTemplate}', [WorkShiftTemplateController::class, 'update'])->name('work-shift-templates.update');
        Route::delete('work-shift-templates/{workShiftTemplate}', [WorkShiftTemplateController::class, 'destroy'])->name('work-shift-templates.destroy');
        Route::post('work-shift-templates/generate', [WorkShiftTemplateController::class, 'generate'])->name('work-shift-templates.generate');

        Route::get('staff-documents', [AdminStaffDocumentController::class, 'index'])->name('staff-documents.index');
        Route::get('staff-documents/create', [AdminStaffDocumentController::class, 'create'])->name('staff-documents.create');
        Route::post('staff-documents', [AdminStaffDocumentController::class, 'store'])->name('staff-documents.store');
        Route::get('staff-documents/{staffDocument}/edit', [AdminStaffDocumentController::class, 'edit'])->name('staff-documents.edit');
        Route::put('staff-documents/{staffDocument}', [AdminStaffDocumentController::class, 'update'])->name('staff-documents.update');
        Route::delete('staff-documents/{staffDocument}', [AdminStaffDocumentController::class, 'destroy'])->name('staff-documents.destroy');

        Route::get('restaurant-board', [RestaurantBoardController::class, 'index'])->name('restaurant-board');
        Route::get('restaurant-board/kiosk', [RestaurantBoardController::class, 'kiosk'])->name('restaurant-board.kiosk');

        Route::get('reports/create', [FacilityReportController::class, 'create'])->name('reports.create');
        Route::post('reports', [FacilityReportController::class, 'store'])->name('reports.store');
        Route::get('reports/{report}/attachment', [FacilityReportController::class, 'attachment'])->name('reports.attachment');
        Route::resource('reports', FacilityReportController::class)->except(['create', 'store']);

        Route::get('statistics', [StatisticsController::class, 'index'])->name('statistics.index');
        Route::get('statistics/live', [StatisticsController::class, 'live'])->name('statistics.live');
        Route::get('statistics/export-csv', [StatisticsController::class, 'exportCsv'])->name('statistics.export-csv');

        Route::get('statistics/guides', [GuideStatisticsController::class, 'index'])->name('statistics.guides');
        Route::get('statistics/guides/export', [GuideStatisticsController::class, 'export'])->name('statistics.guides.export');
        Route::get('statistics/guides/{user}', [GuideStatisticsController::class, 'show'])->name('statistics.guides.show');
        Route::get('statistics/guides/{user}/tour-types/{tourType}', [GuideStatisticsController::class, 'tourType'])->name('statistics.guides.tour-type');

        Route::post('system-messages/reminder-sweep', [SystemMessageController::class, 'reminderSweep'])->name('system-messages.reminder-sweep');
        Route::get('system-messages/{systemMessage}/readers', [SystemMessageController::class, 'readers'])->name('system-messages.readers');
        Route::get('system-messages/{systemMessage}/readers/export', [SystemMessageController::class, 'exportReaders'])->name('system-messages.readers.export');
        Route::resource('system-messages', SystemMessageController::class)->except(['show'])->names('system-messages');

        Route::resource('users', UserController::class);
        Route::resource('tour-types', TourTypeController::class);

        Route::resource('notification-templates', NotificationTemplateController::class)->except(['show']);

        Route::get('notification-logs', [NotificationLogController::class, 'index'])->name('notification-logs.index');
        Route::post('notification-logs/{notificationLog}/resend', [NotificationLogController::class, 'resend'])->name('notification-logs.resend');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/{entityType}/{entityId}', [ActivityLogController::class, 'showEntityHistory'])->name('activity-logs.entity-history');

        Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
        Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
        Route::put('languages/{language}', [LanguageController::class, 'update'])->name('languages.update');
        Route::delete('languages/{language}', [LanguageController::class, 'destroy'])->name('languages.destroy');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('settings/reports', [ReportSettingsController::class, 'index'])->name('settings.reports.index');
        Route::post('settings/reports/categories', [ReportSettingsController::class, 'storeCategory'])->name('settings.reports.categories.store');
        Route::put('settings/reports/categories/{category}', [ReportSettingsController::class, 'updateCategory'])->name('settings.reports.categories.update');
        Route::delete('settings/reports/categories/{category}', [ReportSettingsController::class, 'destroyCategory'])->name('settings.reports.categories.destroy');
        Route::post('settings/reports/priorities', [ReportSettingsController::class, 'storePriority'])->name('settings.reports.priorities.store');
        Route::put('settings/reports/priorities/{priority}', [ReportSettingsController::class, 'updatePriority'])->name('settings.reports.priorities.update');
        Route::delete('settings/reports/priorities/{priority}', [ReportSettingsController::class, 'destroyPriority'])->name('settings.reports.priorities.destroy');
        Route::post('settings/reports/statuses', [ReportSettingsController::class, 'storeStatus'])->name('settings.reports.statuses.store');
        Route::put('settings/reports/statuses/{status}', [ReportSettingsController::class, 'updateStatus'])->name('settings.reports.statuses.update');
        Route::delete('settings/reports/statuses/{status}', [ReportSettingsController::class, 'destroyStatus'])->name('settings.reports.statuses.destroy');
        Route::post('settings/reports/locations', [ReportSettingsController::class, 'storeLocation'])->name('settings.reports.locations.store');
        Route::put('settings/reports/locations/{location}', [ReportSettingsController::class, 'updateLocation'])->name('settings.reports.locations.update');
        Route::delete('settings/reports/locations/{location}', [ReportSettingsController::class, 'destroyLocation'])->name('settings.reports.locations.destroy');

        Route::get('settings/report-options', [ReportOptionController::class, 'index'])->name('report-options.index');
        Route::post('settings/report-options', [ReportOptionController::class, 'store'])->name('report-options.store');
        Route::put('settings/report-options/{reportOption}', [ReportOptionController::class, 'update'])->name('report-options.update');
        Route::delete('settings/report-options/{reportOption}', [ReportOptionController::class, 'destroy'])->name('report-options.destroy');

        Route::get('work-shifts/person', [WorkShiftController::class, 'person'])
            ->name('work-shifts.person');

        Route::post('work-shifts/person', [WorkShiftController::class, 'storePerson'])
            ->name('work-shifts.person.store');

        Route::get('system-health', [SystemHealthController::class, 'index'])
            ->name('system-health.index');
        Route::get('system-logs', [SystemLogController::class, 'index'])
            ->name('system-logs.index');
        Route::get('login-events', [LoginEventController::class, 'index'])
            ->name('login-events.index');
        Route::get('security-overview', [SecurityOverviewController::class, 'index'])
            ->name('security-overview.index');
        Route::get('backup-check', [BackupCheckController::class, 'index'])
            ->name('backup-check.index');

        Route::post('backup-check', [BackupCheckController::class, 'update'])
            ->name('backup-check.update');

        Route::get('/time/payroll-locks', [AdminLockedPayrollPeriodController::class, 'index'])
            ->name('time.payroll-locks.index');
        Route::post('/time/payroll-locks', [AdminLockedPayrollPeriodController::class, 'store'])
            ->name('time.payroll-locks.store');
        Route::delete('/time/payroll-locks/{lockedPayrollPeriod}', [AdminLockedPayrollPeriodController::class, 'destroy'])
            ->name('time.payroll-locks.destroy');

        Route::get('/time/export/entries-csv', [AdminTimeCsvExportController::class, 'entries'])
            ->name('time.export.entries-csv');

        Route::get('/time/export/summary-csv', [AdminTimeCsvExportController::class, 'summary'])
            ->name('time.export.summary-csv');
        Route::get('/time/payroll-pdf/all', [AdminPayrollPdfController::class, 'all'])
            ->name('time.payroll-pdf.all');
        Route::get('/time/control-panel', [AdminTimeControlPanelController::class, 'index'])
            ->name('time.control-panel');
        Route::get('/time/payroll-pdf/{user}', [AdminPayrollPdfController::class, 'person'])
            ->name('time.payroll-pdf.person');
        Route::get('/time', [AdminTimeEntryController::class, 'index'])
            ->name('time.index');
        Route::get('/time/export', [AdminTimeExportController::class, 'export'])
            ->name('time.export');
        Route::get('/time/{timeEntry}', [AdminTimeEntryController::class, 'show'])
            ->name('time.show');

        Route::patch('/time/{timeEntry}/approve', [AdminTimeEntryController::class, 'approve'])
            ->name('time.approve');

        Route::patch('/time/{timeEntry}/correct', [AdminTimeEntryController::class, 'correct'])
            ->name('time.correct');

    });

/*
|--------------------------------------------------------------------------
| Host
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.role:host'])
    ->prefix('host')
    ->name('host.')
    ->group(function () {
        Route::get('/valj-vy', HostEntryController::class)->name('entry');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('visitor-dogs', [AdminVisitorDogController::class, 'index'])->name('visitor-dogs.index');
        Route::get('visitor-dogs/gallery', [AdminVisitorDogController::class, 'gallery'])->name('visitor-dogs.gallery');
        Route::get('visitor-dogs/{visitorDog}/edit', [AdminVisitorDogController::class, 'edit'])->name('visitor-dogs.edit');
        Route::put('visitor-dogs/{visitorDog}', [AdminVisitorDogController::class, 'update'])->name('visitor-dogs.update');
        Route::get('visitor-dogs/{visitorDog}/photo', [AdminVisitorDogController::class, 'photo'])->name('visitor-dogs.photo');
        Route::get('visitor-dogs/{visitorDog}', [AdminVisitorDogController::class, 'show'])->name('visitor-dogs.show');
        Route::delete('visitor-dogs/{visitorDog}', [AdminVisitorDogController::class, 'destroy'])->name('visitor-dogs.destroy');

        Route::get('tours/batch-create', [TourBatchController::class, 'create'])->name('tours.batch-create');
        Route::post('tours/batch-create', [TourBatchController::class, 'store'])->name('tours.batch-store');

        Route::post('tours/{tour}/start', [TourController::class, 'start'])->name('tours.start');
        Route::post('tours/{tour}/complete', [TourController::class, 'complete'])->name('tours.complete');
        Route::post('tours/{tour}/cancel', [TourController::class, 'cancel'])->name('tours.cancel');

        Route::resource('tours', TourController::class);
        Route::resource('special-tours', SpecialTourController::class)
            ->parameters(['special-tours' => 'tour']);

        Route::get('guides/availability', [GuideAvailabilityController::class, 'index'])->name('guides.availability');

        Route::get('work-shifts/staffing', [WorkShiftController::class, 'staffing'])
            ->name('work-shifts.staffing');

        Route::get('bookings/export-csv', [BookingController::class, 'exportCsv'])->name('bookings.export-csv');
        Route::get('quick-bookings/create', [QuickBookingController::class, 'create'])->name('bookings.quick-create');
        Route::post('quick-bookings', [QuickBookingController::class, 'store'])->name('bookings.quick-store');
        Route::patch('bookings/{booking}/participants', [BookingController::class, 'quickUpdateParticipants'])->name('bookings.quick-update-participants');
        Route::patch('bookings/{booking}/move', [BookingController::class, 'move'])->name('bookings.move');
        Route::patch('bookings/{booking}/arrival', [BookingController::class, 'markArrival'])->name('bookings.mark-arrival');
        Route::resource('bookings', BookingController::class)->except(['show']);

        Route::get('restaurant-board', [RestaurantBoardController::class, 'index'])->name('restaurant-board');
        Route::get('restaurant-board/kiosk', [RestaurantBoardController::class, 'kiosk'])->name('restaurant-board.kiosk');

        Route::get('statistics', [StatisticsController::class, 'index'])->name('statistics.index');
        Route::get('statistics/live', [StatisticsController::class, 'live'])->name('statistics.live');

        Route::get('system-health', [SystemHealthController::class, 'index'])
            ->name('system-health.index');
        Route::get('system-logs', [SystemLogController::class, 'index'])
            ->name('system-logs.index');
    });

/*
|--------------------------------------------------------------------------
| Guide
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.role:guide'])
    ->prefix('guide')
    ->name('guide.')
    ->group(function () {
        Route::get('/dashboard', [GuideDashboardController::class, 'index'])->name('dashboard');

        Route::post('/tours/{tour}/start', [GuideDashboardController::class, 'startTour'])->name('tours.start');
        Route::post('/tours/{tour}/complete', [GuideDashboardController::class, 'completeTour'])->name('tours.complete');
        Route::get('/tours/{tour}', [GuideDashboardController::class, 'showTour'])->name('tours.show');

        Route::patch('/bookings/{booking}/participants', [GuideDashboardController::class, 'updateBookingParticipants'])->name('bookings.update-participants');

        Route::get('/reports/create', [GuideFacilityReportController::class, 'create'])->name('reports.create');
        Route::post('/reports', [GuideFacilityReportController::class, 'store'])->name('reports.store');
    });

/*
|--------------------------------------------------------------------------
| Restaurang
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.role:restaurant'])
    ->prefix('restaurant')
    ->name('restaurant.')
    ->group(function () {
        Route::get('/dashboard', [RestaurantBoardController::class, 'index'])->name('dashboard');
        Route::get('/board', [RestaurantBoardController::class, 'index'])->name('board');
        Route::get('/kiosk', [RestaurantBoardController::class, 'kiosk'])->name('kiosk');
    });

/*
|--------------------------------------------------------------------------
| Restaurang statistik
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])
    ->prefix('statistik/restaurang')
    ->name('restaurant-statistik.')
    ->group(function () {
        Route::get('/', [RestaurantBoardController::class, 'statistik'])
            ->name('dashboard');
    });
/*
|--------------------------------------------------------------------------
| Restaurang statistik - fristående login
|--------------------------------------------------------------------------
*/

Route::get('/restaurant-statistics/login', [RestaurantStatisticsController::class, 'loginForm'])
    ->name('restaurant-statistics.login');

Route::post('/restaurant-statistics/login', [RestaurantStatisticsController::class, 'login'])
    ->name('restaurant-statistics.login.store');

Route::post('/restaurant-statistics/logout', [RestaurantStatisticsController::class, 'logout'])
    ->name('restaurant-statistics.logout');

Route::middleware(['restaurant.statistics.access'])
    ->prefix('restaurant-statistics')
    ->name('restaurant-statistics.')
    ->group(function () {
        Route::get('/', [RestaurantStatisticsController::class, 'dashboard'])
            ->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| Tid, stämpeklocka
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/time', [TimeEntryController::class, 'index'])->name('time.index');
    Route::post('/time/clock-in', [TimeClockController::class, 'clockIn'])->name('time.clock-in');
    Route::post('/time/clock-out', [TimeClockController::class, 'clockOut'])->name('time.clock-out');
    Route::get('/time/{timeEntry}/edit', [TimeEntryController::class, 'edit'])->name('time.edit');
    Route::patch('/time/{timeEntry}', [TimeEntryController::class, 'update'])->name('time.update');
    Route::patch('/time/{timeEntry}/submit', [TimeEntryController::class, 'submit'])->name('time.submit');
});
require __DIR__.'/auth.php';
