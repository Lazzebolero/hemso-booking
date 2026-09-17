<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminLockedPayrollPeriodController;
use App\Http\Controllers\Admin\AdminPayrollPdfController;
use App\Http\Controllers\Admin\AdminTimeClockQrController;
use App\Http\Controllers\Admin\AdminTimeControlPanelController;
use App\Http\Controllers\Admin\AdminTimeCsvExportController;
use App\Http\Controllers\Admin\AdminTimeEntryController;
use App\Http\Controllers\Admin\AdminTimeExportController;
use App\Http\Controllers\Admin\AudioDeviceController;
use App\Http\Controllers\Admin\AudioGroupController;
use App\Http\Controllers\Admin\AudioSoundController;
use App\Http\Controllers\Admin\BackupCheckController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\DailyCountryLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EconomyProfitabilityController;
use App\Http\Controllers\Admin\EconomySettingController;
use App\Http\Controllers\Admin\FacilityMemoryController;
use App\Http\Controllers\Admin\FacilityOccupancyController;
use App\Http\Controllers\Admin\FacilityReportController;
use App\Http\Controllers\Admin\GroupBookingController;
use App\Http\Controllers\Admin\GuideAvailabilityController;
use App\Http\Controllers\Admin\GuideLanguageController;
use App\Http\Controllers\Admin\GuideStatisticsController;
use App\Http\Controllers\Admin\HistoricalVisitorStatController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\LoginEventController;
use App\Http\Controllers\Admin\NotificationLogController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\OpeningCheckController;
use App\Http\Controllers\Admin\PostalCodeCollectionController;
use App\Http\Controllers\Admin\PostalCodeReportController;
use App\Http\Controllers\Admin\ProductionController;
use App\Http\Controllers\Admin\ProductionPhoneNumberController;
use App\Http\Controllers\Admin\ProductionPresenceLogController;
use App\Http\Controllers\Admin\ProductionPresenceOverviewController;
use App\Http\Controllers\Admin\QuickBookingController;
use App\Http\Controllers\Admin\ReportOptionController;
use App\Http\Controllers\Admin\ReportSettingsController;
use App\Http\Controllers\Admin\RestaurantBoardController;
use App\Http\Controllers\Admin\RestaurantEconomyCostController;
use App\Http\Controllers\Admin\RestaurantFunctionController;
use App\Http\Controllers\Admin\SecurityOverviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SpecialTourController;
use App\Http\Controllers\Admin\StaffDocumentController as AdminStaffDocumentController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\StatisticsDayNoteController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\SystemMessageController;
use App\Http\Controllers\Admin\TourBatchController;
use App\Http\Controllers\Admin\TourController;
use App\Http\Controllers\Admin\TourStaffingSimulatorController;
use App\Http\Controllers\Admin\TourTypeController;
use App\Http\Controllers\Admin\UnspecifiedFollowUpController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VisitorDogController as AdminVisitorDogController;
use App\Http\Controllers\Admin\WorkShiftController;
use App\Http\Controllers\Admin\WorkShiftImportController;
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
        Route::patch('/facility-occupancy/extra', [FacilityOccupancyController::class, 'updateExtra'])->name('facility-occupancy.update-extra');

        Route::get('productions', [ProductionController::class, 'index'])->name('productions.index');
        Route::post('productions', [ProductionController::class, 'store'])->name('productions.store');
        Route::get('productions/narvaro', ProductionPresenceOverviewController::class)->name('productions.presence');
        Route::get('productions/logg', ProductionPresenceLogController::class)->name('productions.log');
        Route::get('productions/{production}', [ProductionController::class, 'show'])->name('productions.show');
        Route::put('productions/{production}', [ProductionController::class, 'update'])->name('productions.update');
        Route::post('productions/{production}/people', [ProductionController::class, 'storePerson'])->name('productions.people.store');
        Route::post('productions/{production}/import', [ProductionController::class, 'import'])->name('productions.import');
        Route::delete('productions/{production}/people/{person}', [ProductionController::class, 'destroyPerson'])->name('productions.people.destroy');
        Route::post('productions/{production}/phone-numbers', [ProductionPhoneNumberController::class, 'store'])->name('productions.phone-numbers.store');
        Route::put('productions/{production}/phone-numbers/{phoneNumber}', [ProductionPhoneNumberController::class, 'update'])->name('productions.phone-numbers.update');
        Route::delete('productions/{production}/phone-numbers/{phoneNumber}', [ProductionPhoneNumberController::class, 'destroy'])->name('productions.phone-numbers.destroy');

        Route::get('daily-countries', [DailyCountryLogController::class, 'edit'])->name('daily-countries.edit');
        Route::put('daily-countries', [DailyCountryLogController::class, 'update'])->name('daily-countries.update');

        Route::get('statistics-notes', [StatisticsDayNoteController::class, 'edit'])->name('statistics-notes.edit');
        Route::put('statistics-notes', [StatisticsDayNoteController::class, 'update'])->name('statistics-notes.update');

        Route::get('postal-codes', [PostalCodeCollectionController::class, 'edit'])->name('postal-codes.edit');
        Route::put('postal-codes', [PostalCodeCollectionController::class, 'update'])->name('postal-codes.update');
        Route::get('postal-codes/report', [PostalCodeReportController::class, 'index'])->name('postal-codes.report');

        Route::get('visitor-dogs/create', [AdminVisitorDogController::class, 'create'])->name('visitor-dogs.create');
        Route::post('visitor-dogs', [AdminVisitorDogController::class, 'store'])->name('visitor-dogs.store');
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
        Route::post('tours/{tour}/close-for-bookings', [TourController::class, 'closeForBookings'])->name('tours.close-for-bookings');
        Route::post('tours/{tour}/reopen-for-bookings', [TourController::class, 'reopenForBookings'])->name('tours.reopen-for-bookings');
        Route::post('tours/{tour}/cancel', [TourController::class, 'cancel'])->name('tours.cancel');

        Route::resource('tours', TourController::class);
        Route::resource('special-tours', SpecialTourController::class)
            ->parameters(['special-tours' => 'tour']);

        Route::get('guides/availability', [GuideAvailabilityController::class, 'index'])->name('guides.availability');
        Route::get('guide-languages', [GuideLanguageController::class, 'index'])->name('guide-languages.index');

        Route::get('bookings/export-csv', [BookingController::class, 'exportCsv'])->name('bookings.export-csv');
        Route::get('bookings/tours/search', [BookingController::class, 'searchTours'])->middleware('throttle:30,1')->name('bookings.tours.search');
        Route::get('quick-bookings/create', [QuickBookingController::class, 'create'])->name('bookings.quick-create');
        Route::post('quick-bookings', [QuickBookingController::class, 'store'])->name('bookings.quick-store');
        Route::get('group-bookings/create', [GroupBookingController::class, 'create'])->name('group-bookings.create');
        Route::post('group-bookings', [GroupBookingController::class, 'store'])->name('group-bookings.store');
        Route::patch('bookings/{booking}/participants', [BookingController::class, 'quickUpdateParticipants'])->name('bookings.quick-update-participants');
        Route::patch('bookings/{booking}/move', [BookingController::class, 'move'])->name('bookings.move');
        Route::patch('bookings/{booking}/arrival', [BookingController::class, 'markArrival'])->name('bookings.mark-arrival');
        Route::resource('bookings', BookingController::class)->except(['show']);

        Route::get('work-shifts/staffing', [WorkShiftController::class, 'staffing'])
            ->name('work-shifts.staffing');
        Route::get('work-shifts/mall', [WorkShiftImportController::class, 'template'])->name('work-shifts.template');
        Route::get('work-shifts/import', [WorkShiftImportController::class, 'create'])->name('work-shifts.import');
        Route::post('work-shifts/import', [WorkShiftImportController::class, 'store'])->name('work-shifts.import.store');
        Route::post('work-shifts/import/bekrafta', [WorkShiftImportController::class, 'confirm'])->name('work-shifts.import.confirm');
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
        Route::get('restaurant-board/poll', [RestaurantBoardController::class, 'poll'])->name('restaurant-board.poll');
        Route::get('restaurant-board/ferry-timetable', [RestaurantBoardController::class, 'ferryTimetable'])->name('restaurant-board.ferry-timetable');

        Route::get('reports/create', [FacilityReportController::class, 'create'])->name('reports.create');
        Route::post('reports', [FacilityReportController::class, 'store'])->name('reports.store');
        Route::get('reports/{report}/attachment', [FacilityReportController::class, 'attachment'])->name('reports.attachment');
        Route::get('reports/{report}/attachments/{attachment}', [FacilityReportController::class, 'showAttachment'])->name('reports.attachments.show');
        Route::resource('reports', FacilityReportController::class)->except(['create', 'store']);

        Route::get('opening-checks', [OpeningCheckController::class, 'index'])->name('opening-checks.index');
        Route::get('opening-checks/{openingCheck}', [OpeningCheckController::class, 'show'])->name('opening-checks.show');
        Route::patch('opening-checks/{openingCheck}/deviations/{openingDeviation}/resolve', [OpeningCheckController::class, 'resolveDeviation'])->name('opening-checks.deviations.resolve');

        Route::get('facility-memories/{facilityMemory}/audio', [FacilityMemoryController::class, 'audio'])->name('facility-memories.audio');
        Route::get('facility-memories', [FacilityMemoryController::class, 'index'])->name('facility-memories.index');
        Route::get('facility-memories/{facilityMemory}', [FacilityMemoryController::class, 'show'])->name('facility-memories.show');
        Route::patch('facility-memories/{facilityMemory}', [FacilityMemoryController::class, 'update'])->name('facility-memories.update');
        Route::delete('facility-memories/{facilityMemory}', [FacilityMemoryController::class, 'destroy'])->name('facility-memories.destroy');

        Route::get('statistics', [StatisticsController::class, 'index'])->name('statistics.index');
        Route::get('statistics/live', [StatisticsController::class, 'live'])->name('statistics.live');
        Route::get('statistics/export-csv', [StatisticsController::class, 'exportCsv'])->name('statistics.export-csv');
        Route::get('statistics/booking-inflow', [StatisticsController::class, 'bookingInflow'])->name('statistics.booking-inflow');
        Route::get('statistics/ferry-booking-waves', [StatisticsController::class, 'ferryBookingWaves'])->name('statistics.ferry-booking-waves');
        Route::get('statistics/tour-wait-times', [StatisticsController::class, 'tourWaitTimes'])->name('statistics.tour-wait-times');
        Route::get('statistics/year-countries-map', [StatisticsController::class, 'yearCountriesMap'])->name('statistics.year-countries-map');

        Route::get('statistics/historical-visitors', [HistoricalVisitorStatController::class, 'index'])->name('statistics.historical-visitors.index');
        Route::post('statistics/historical-visitors/import', [HistoricalVisitorStatController::class, 'import'])->name('statistics.historical-visitors.import');
        Route::delete('statistics/historical-visitors/{historicalDailyVisitor}', [HistoricalVisitorStatController::class, 'destroy'])->name('statistics.historical-visitors.destroy');

        Route::get('statistics/unspecified-follow-up', [UnspecifiedFollowUpController::class, 'index'])->name('statistics.unspecified-follow-up');

        Route::get('statistics/guides', [GuideStatisticsController::class, 'index'])->name('statistics.guides');
        Route::get('statistics/guides/export', [GuideStatisticsController::class, 'export'])->name('statistics.guides.export');
        Route::get('statistics/guides/{user}', [GuideStatisticsController::class, 'show'])->name('statistics.guides.show');
        Route::get('statistics/guides/{user}/tour-types/{tourType}', [GuideStatisticsController::class, 'tourType'])->name('statistics.guides.tour-type');

        Route::get('economy-profitability', [EconomyProfitabilityController::class, 'index'])->name('economy-profitability.index');
        Route::get('restaurant-economy-cost', [RestaurantEconomyCostController::class, 'index'])->name('restaurant-economy-cost.index');
        Route::get('tour-staffing-simulator', [TourStaffingSimulatorController::class, 'index'])->name('tour-staffing-simulator.index');

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

        Route::put('countries/quick-pick-limit', [CountryController::class, 'updateQuickPickLimit'])->name('countries.quick-pick-limit');
        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::put('countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');

        Route::get('restaurant-functions', [RestaurantFunctionController::class, 'index'])->name('restaurant-functions.index');
        Route::post('restaurant-functions', [RestaurantFunctionController::class, 'store'])->name('restaurant-functions.store');
        Route::put('restaurant-functions/{restaurantFunction}', [RestaurantFunctionController::class, 'update'])->name('restaurant-functions.update');
        Route::delete('restaurant-functions/{restaurantFunction}', [RestaurantFunctionController::class, 'destroy'])->name('restaurant-functions.destroy');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('economy-settings', [EconomySettingController::class, 'edit'])->name('economy-settings.edit');
        Route::put('economy-settings', [EconomySettingController::class, 'update'])->name('economy-settings.update');

        Route::get('settings/reports', [ReportSettingsController::class, 'index'])->name('settings.reports.index');
        Route::put('settings/reports/notification-emails', [ReportSettingsController::class, 'updateNotificationEmails'])->name('settings.reports.notification-emails.update');
        Route::put('settings/reports/opening-deviation-emails', [ReportSettingsController::class, 'updateOpeningDeviationEmails'])->name('settings.reports.opening-deviation-emails.update');
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
        Route::post('system-health/migrate', [SystemHealthController::class, 'runMigrations'])
            ->name('system-health.migrate');
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
        Route::get('/time/qr-codes', [AdminTimeClockQrController::class, 'index'])
            ->name('time.qr-codes');
        Route::get('/time/export', [AdminTimeExportController::class, 'export'])
            ->name('time.export');
        Route::get('/time/{timeEntry}', [AdminTimeEntryController::class, 'show'])
            ->name('time.show');

        Route::patch('/time/{timeEntry}/approve', [AdminTimeEntryController::class, 'approve'])
            ->name('time.approve');

        Route::patch('/time/{timeEntry}/correct', [AdminTimeEntryController::class, 'correct'])
            ->name('time.correct');

        Route::prefix('audio')->name('audio.')->group(function () {
            Route::get('/', [AudioDeviceController::class, 'index'])->name('index');
            Route::post('/stop-all', [AudioDeviceController::class, 'stopAll'])->name('stop-all');

            Route::get('/groups', [AudioGroupController::class, 'index'])->name('groups.index');
            Route::post('/groups', [AudioGroupController::class, 'store'])->name('groups.store');
            Route::get('/groups/{group}', [AudioGroupController::class, 'show'])->name('groups.show');
            Route::put('/groups/{group}', [AudioGroupController::class, 'update'])->name('groups.update');
            Route::delete('/groups/{group}', [AudioGroupController::class, 'destroy'])->name('groups.destroy');
            Route::post('/groups/{group}/play', [AudioGroupController::class, 'play'])->name('groups.play');
            Route::post('/groups/{group}/stop', [AudioGroupController::class, 'stop'])->name('groups.stop');

            Route::get('/sounds', [AudioSoundController::class, 'index'])->name('sounds.index');
            Route::post('/sounds', [AudioSoundController::class, 'store'])->name('sounds.store');
            Route::delete('/sounds/{sound}', [AudioSoundController::class, 'destroy'])->name('sounds.destroy');

            Route::get('/devices/create', [AudioDeviceController::class, 'create'])->name('devices.create');
            Route::post('/devices', [AudioDeviceController::class, 'store'])->name('devices.store');
            Route::get('/devices/{device}', [AudioDeviceController::class, 'show'])->name('devices.show');
            Route::get('/devices/{device}/edit', [AudioDeviceController::class, 'edit'])->name('devices.edit');
            Route::put('/devices/{device}', [AudioDeviceController::class, 'update'])->name('devices.update');
            Route::delete('/devices/{device}', [AudioDeviceController::class, 'destroy'])->name('devices.destroy');
            Route::get('/devices/{device}/setup', [AudioDeviceController::class, 'setup'])->name('devices.setup');
            Route::post('/devices/{device}/setup/config', [AudioDeviceController::class, 'downloadConfig'])->name('devices.setup-config');
            Route::post('/devices/{device}/stop', [AudioDeviceController::class, 'stopDevice'])->name('devices.stop');
            Route::post('/devices/{device}/channels', [AudioDeviceController::class, 'storeChannel'])->name('devices.channels.store');

            Route::patch('/channels/{loudspeaker}', [AudioDeviceController::class, 'updateChannel'])->name('channels.update');
            Route::delete('/channels/{loudspeaker}', [AudioDeviceController::class, 'destroyChannel'])->name('channels.destroy');
            Route::post('/channels/{loudspeaker}/play', [AudioDeviceController::class, 'playChannel'])->name('channels.play');
            Route::post('/channels/{loudspeaker}/stop', [AudioDeviceController::class, 'stopChannel'])->name('channels.stop');
        });

    });
