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
use App\Http\Controllers\Admin\TourBatchController;
use App\Http\Controllers\Admin\TourController;
use App\Http\Controllers\Admin\TourPhotoController;
use App\Http\Controllers\Admin\TourTypeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VisitorDogController as AdminVisitorDogController;
use App\Http\Controllers\Admin\WorkShiftController;
use App\Http\Controllers\Admin\WorkShiftTemplateController;
use Illuminate\Support\Facades\Route;

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
        Route::get('tours/{tour}/photos/{tourPhoto}', [TourPhotoController::class, 'show'])->name('tours.photos.show');
        Route::get('tours/{tour}/photos/{tourPhoto}/download', [TourPhotoController::class, 'download'])->name('tours.photos.download');
        Route::delete('tours/{tour}/photos/{tourPhoto}', [TourPhotoController::class, 'destroy'])->name('tours.photos.destroy');

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

        Route::get('users/export/contacts-csv', [UserController::class, 'exportContactsCsv'])
            ->name('users.export.contacts-csv');
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
        Route::post('system-health/test-mail', [SystemHealthController::class, 'sendTestMail'])
            ->name('system-health.test-mail');
        Route::get('system-health/status.json', [SystemHealthController::class, 'statusJson'])
            ->name('system-health.status-json');
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
