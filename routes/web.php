<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\VolunteerImportController;
use App\Http\Controllers\CreateEventController;
use App\Http\Controllers\VolunteerListController;
use App\Http\Controllers\VolunteerProfileController;
use App\Http\Controllers\EventManagerController;
use App\Http\Controllers\AttendanceImportController;
use App\Http\Controllers\EventDetailsController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\EventOrganizerDirectoryController;
use App\Http\Controllers\EventSummaryController;
use App\Http\Controllers\SystemLogsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ApiDashboardController;

Route::get('/', function () {
    return redirect()->route('auth.login');
});

/* ------------------ AUTH ROUTES ------------------ */
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/* ------------------ SESSION EXPIRED (419 helper) ------------------ */
Route::get('/_session-expired', function () {
    return redirect()
        ->route('auth.login')
        ->with('success', 'Session expired. Please login again.');
})->name('session.expired');

/* ------------------ PROTECTED ROUTES ------------------ */
Route::middleware(['auth:admin'])->group(function () {

    Route::get('/home', [HomePageController::class, 'index'])->name('home');

    /* ------------------ ADMIN PROFILE + DASHBOARD ------------------ */
    Route::prefix('admin')->name('admin.')->group(function () {

        // pages
        Route::get('/registration', function () {
            return view('admin.registration');
        })->name('register');

        Route::get('/list', [AdminProfileController::class, 'adminList'])
            ->name('list');

        // profile
        Route::get('/profile/view/{id}', [AdminProfileController::class, 'viewProfile'])
            ->name('profile.view');

        Route::get('/profile/{id}/edit', [AdminProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::get('/profile/{id}', [AdminProfileController::class, 'index'])
            ->name('profile');

        Route::get('/profile', [AdminProfileController::class, 'index'])
            ->name('profile.self');

        Route::put('/profile/update', [AdminProfileController::class, 'update'])
            ->name('profile.update');

        Route::get('/profile/logs/{id}', [AdminProfileController::class, 'getLogs'])
            ->name('profile.logs');

        // dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/dashboard/data', [ApiDashboardController::class, 'fetch'])
            ->name('dashboard.data');

        // register admin user
        Route::post('/user/store', [AdminUserController::class, 'store'])
            ->name('user.store');
    });

    /* ------------------ VOLUNTEER IMPORT ------------------ */
    Route::prefix('volunteer-import')->group(function () {

        Route::get('/', [VolunteerImportController::class, 'index'])
            ->name('volunteer.import.index');

        Route::post('/preview', [VolunteerImportController::class, 'preview'])
            ->name('volunteer.import.preview');

        Route::post('/validate-save', [VolunteerImportController::class, 'validateAndSave'])
            ->name('volunteer.import.validateSave');

        Route::post('/reset', [VolunteerImportController::class, 'resetImports'])
            ->name('volunteer.import.reset');

        Route::post('/move-invalid', [VolunteerImportController::class, 'moveInvalidToValid'])
            ->name('volunteer.import.moveInvalidToValid');

        Route::put('/volunteer/update-entry/{index}/{type}', [VolunteerImportController::class, 'updateVolunteerEntry'])
            ->name('volunteer.import.update-entry');

        Route::get('/move-valid-to-invalid/{index}', [VolunteerImportController::class, 'moveValidToInvalid'])
            ->name('volunteer.moveValidToInvalid');

        Route::post('/volunteer/delete-entries', [VolunteerImportController::class, 'deleteEntries'])
            ->name('volunteer.deleteEntries');

        Route::get('/undo-delete', [VolunteerImportController::class, 'undoDelete'])
            ->name('volunteer.import.undo-delete');

        Route::put('/volunteers/{id}/update-schedule', [VolunteerImportController::class, 'updateSchedule'])
            ->name('volunteer.update-schedule');

        Route::post('/check-duplicates', [VolunteerImportController::class, 'checkDuplicates'])
            ->name('volunteer.import.checkDuplicates');

        Route::post('/update-picture', [VolunteerImportController::class, 'updatePicture'])
            ->name('volunteer.import.updatePicture');

        Route::post('/set-default-picture', [VolunteerImportController::class, 'setDefaultPicture'])
            ->name('volunteer.import.setDefaultPicture');
    });

    /* ------------------ VOLUNTEER LIST ------------------ */
    Route::get('/volunteers_list', [VolunteerListController::class, 'index'])
        ->name('volunteers.list');

    Route::post('/volunteers', [VolunteerListController::class, 'store'])
        ->name('volunteers.store');

    Route::get('/volunteers/data', [VolunteerListController::class, 'data'])
        ->name('volunteers.data');

    Route::get('/volunteers/locations', [VolunteerListController::class, 'locations'])
        ->name('volunteers.locations');

    /* ------------------ VOLUNTEER PROFILE ------------------ */
    Route::get('/volunteer-profile/{id}', [VolunteerProfileController::class, 'show'])
        ->name('volunteers.show');

    Route::put('/volunteer-profile/{id}', [VolunteerProfileController::class, 'update'])
        ->name('volunteers.update');

    Route::delete('/volunteer-profile/{id}', [VolunteerProfileController::class, 'destroy'])
        ->name('volunteers.destroy');

    Route::post('/volunteer-profile/check-unique', [VolunteerProfileController::class, 'checkUnique'])
        ->name('volunteers.checkUnique');

    /* ------------------ EVENT MANAGER ------------------ */
    Route::get('/events/manage', [EventManagerController::class, 'index'])
        ->name('events.manage');

    Route::delete('/events/manage/bulk-destroy', [EventManagerController::class, 'bulkDestroy'])
        ->name('events.bulkDestroy');

    /* ------------------ EVENTS ------------------ */
    Route::prefix('events')->group(function () {

        // Force {event} to be numeric everywhere inside /events/*
        Route::pattern('event', '[0-9]+');

        /* ------------------ Event Organizer Directory ------------------ */
        Route::get('/organizers', [EventOrganizerDirectoryController::class, 'index'])
            ->name('organizers.index');

        Route::put('/organizers/{organizer}', [EventOrganizerDirectoryController::class, 'update'])
            ->name('organizers.update');

        Route::delete('/organizers/{organizer}', [EventOrganizerDirectoryController::class, 'destroy'])
            ->name('organizers.destroy');

        /* ------------------ Event Type ------------------ */
        Route::get('/event-types/json', [EventTypeController::class, 'indexJson'])
            ->name('event-types.json');

        Route::post('/event-types', [EventTypeController::class, 'store'])
            ->name('event-types.store');

        Route::put('/event-types/{eventType}', [EventTypeController::class, 'update'])
            ->name('event-types.update');

        Route::delete('/event-types/{eventType}', [EventTypeController::class, 'destroy'])
            ->name('event-types.destroy');

        // create/store
        Route::get('/create', [CreateEventController::class, 'create'])
            ->name('events.create');

        Route::post('/', [CreateEventController::class, 'store'])
            ->name('events.store');

        // show event details
        Route::get('/{event:event_id}', [EventDetailsController::class, 'show'])
            ->whereNumber('event')
            ->name('event.details.show');

        // edit/update
        Route::get('/{event:event_id}/edit', [CreateEventController::class, 'edit'])
            ->whereNumber('event')
            ->name('events.edit');

        Route::put('/{event:event_id}', [CreateEventController::class, 'update'])
            ->whereNumber('event')
            ->name('events.update');

        // summary
        Route::get('/{event:event_id}/summary', [EventSummaryController::class, 'show'])
            ->whereNumber('event')
            ->name('events.summary');

        // expected volunteers
        Route::post('/{event:event_id}/expected-volunteers', [CreateEventController::class, 'addVolunteers'])
            ->whereNumber('event')
            ->name('events.expectedVolunteers.add');

        Route::delete('/{event:event_id}/expected-volunteers/{volunteer_id}', [CreateEventController::class, 'removeExpectedVolunteer'])
            ->whereNumber('event')
            ->name('events.expectedVolunteers.remove');

        // cancel/restore/delete
        Route::post('/{event:event_id}/cancel', [EventDetailsController::class, 'cancel'])
            ->whereNumber('event')
            ->name('events.cancel');

        Route::post('/{event:event_id}/restore', [EventDetailsController::class, 'restore'])
            ->whereNumber('event')
            ->name('events.restore');

        Route::delete('/{event:event_id}', [EventDetailsController::class, 'destroy'])
            ->whereNumber('event')
            ->name('events.destroy');

        // organizers
        Route::put('/{event:event_id}/organizers', [EventDetailsController::class, 'updateOrganizer'])
            ->whereNumber('event')
            ->name('events.organizers.update');

        Route::delete('/{event:event_id}/organizers', [EventDetailsController::class, 'destroyOrganizer'])
            ->whereNumber('event')
            ->name('events.organizers.destroy');

        /* ------------------ IMPORT ATTENDANCE ------------------ */
        Route::get('/{event:event_id}/attendance/import', [AttendanceImportController::class, 'index'])
            ->whereNumber('event')
            ->name('attendance.import.index');

        Route::post('/{event:event_id}/attendance/import/preview', [AttendanceImportController::class, 'preview'])
            ->whereNumber('event')
            ->name('attendance.import.preview');

        Route::post('/{event:event_id}/attendance/import/commit', [AttendanceImportController::class, 'commit'])
            ->whereNumber('event')
            ->name('attendance.import.commit');

        Route::post('/{event:event_id}/attendance/import/reset', [AttendanceImportController::class, 'reset'])
            ->whereNumber('event')
            ->name('attendance.import.reset');

        Route::post('/{event:event_id}/attendance/import/preview/update', [AttendanceImportController::class, 'updatePreviewRow'])
            ->whereNumber('event')
            ->name('attendance.import.preview.update');

        Route::post('/{event:event_id}/attendance/import/preview/delete', [AttendanceImportController::class, 'deletePreviewRow'])
            ->whereNumber('event')
            ->name('attendance.import.preview.delete');
    });

    /* ------------------ System Logs ------------------ */
    Route::get('/system-logs', [SystemLogsController::class, 'index'])
        ->name('system.logs.index');
});

/* ------------------ BACKUP FALLBACK ------------------ */
Route::fallback(function () {
    return redirect()->route('auth.login');
});
