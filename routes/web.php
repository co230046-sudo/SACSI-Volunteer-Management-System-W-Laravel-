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
use App\Models\ActivityLog;
use App\Models\Admin;

Route::get('/', function () {
    return redirect()->route('auth.login');
});

/* ------------------ AUTH ROUTES ------------------ */
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/* ------------------ PROTECTED ROUTES ------------------ */
Route::middleware(['auth:admin'])->group(function () {

    Route::get('/home', [HomePageController::class, 'index'])->name('home');

    /* ------------------ ADMIN PROFILE + DASHBOARD ------------------ */
    // Dynamic profile first (must be above /admin/profile)
    Route::middleware(['auth:admin'])->prefix('admin')->name('admin.')->group(function () {

        // ✅ ADMIN PROFILE PAGE
        Route::get('/profile', [AdminProfileController::class, 'index'])
            ->name('profile.self');

        Route::get('/profile/{id}', [AdminProfileController::class, 'index'])
            ->name('profile');

        // ✅ UPDATE PROFILE
        Route::put('/profile/update', [AdminProfileController::class, 'update'])
            ->name('profile.update');

        // ✅ FETCH LOGS (MODAL)
        Route::get('/profile/logs/{id}', [AdminProfileController::class, 'getLogs'])
            ->name('profile.logs');

        // ✅ ✅ ✅ FETCH PROFILE FOR MODAL (THIS WAS BROKEN BEFORE)
        Route::get('/profile/view/{id}', [AdminProfileController::class, 'viewProfile'])
            ->name('profile.view');

        // ✅ DASHBOARD
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

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

    Route::get('/volunteers/data', [VolunteerListController::class, 'data'])
        ->name('volunteers.data');

    Route::get('/volunteers/locations', [VolunteerListController::class, 'locations'])
        ->name('volunteers.locations');

    /* ------------------ VOLUNTEER PROFILE ------------------ */
    Route::get('/volunteer-profile/{id}', [VolunteerProfileController::class, 'show'])
        ->name('volunteers.show');

    /* ------------------ EVENT MANAGER ------------------ */
    Route::get('/events/manage', [EventManagerController::class, 'index'])
        ->name('events.manage');

    Route::delete('/events/manage/bulk-destroy', [EventManagerController::class, 'bulkDestroy'])
        ->name('events.bulkDestroy');

    /* ------------------ EVENTS ------------------ */
    Route::prefix('events')->group(function () {

        // ✅ Force {event} to be numeric everywhere inside /events/*
        Route::pattern('event', '[0-9]+');

        // ✅ MUST be above /{event:event_id} routes, so it never gets captured
        /* ------------------ Event Organizer Directory ------------------ */
        Route::get('/organizers', [EventOrganizerDirectoryController::class, 'index'])
            ->name('organizers.index');

        // Organizer Directory CRUD (DB)
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
        Route::get('/{event:event_id}/summary', [CreateEventController::class, 'summary'])
            ->whereNumber('event')
            ->name('events.summary');

        // expected volunteers
        Route::post('/{event:event_id}/expected-volunteers', [CreateEventController::class, 'addVolunteers'])
            ->whereNumber('event')
            ->name('events.expectedVolunteers.add');

        Route::delete('/{event:event_id}/expected-volunteers/{volunteer_id}', [CreateEventController::class, 'removeExpectedVolunteer'])
            ->whereNumber('event')
            ->name('events.expectedVolunteers.remove');

        // cancel
        Route::post('/{event:event_id}/cancel', [EventDetailsController::class, 'cancel'])
            ->whereNumber('event')
            ->name('events.cancel');

        // restore
        Route::post('/{event:event_id}/restore', [EventDetailsController::class, 'restore'])
            ->whereNumber('event')
            ->name('events.restore');

        // delete (single event)
        Route::delete('/{eventId}', [EventDetailsController::class, 'destroy'])
            ->name('events.destroy');

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

});
