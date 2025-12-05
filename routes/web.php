<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\VolunteerImportController;
use App\Http\Controllers\CreateEventController;
use App\Http\Controllers\VolunteerListController;
use App\Http\Controllers\VolunteerProfileController;
use App\Http\Controllers\EventDetailsController;
use App\Http\Controllers\EventManagerController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\DashboardController;


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

    /* --- Volunteer Import --- */
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

        Route::put('/volunteer/update-entry/{index}/{type}',
            [VolunteerImportController::class, 'updateVolunteerEntry'])
            ->name('volunteer.import.update-entry');

        Route::get('/move-valid-to-invalid/{index}',
            [VolunteerImportController::class, 'moveValidToInvalid'])
            ->name('volunteer.moveValidToInvalid');

        Route::post('/volunteer/delete-entries',
            [VolunteerImportController::class, 'deleteEntries'])
            ->name('volunteer.deleteEntries');

        Route::get('/undo-delete',
            [VolunteerImportController::class, 'undoDelete'])
            ->name('volunteer.import.undo-delete');

        Route::put('/volunteers/{id}/update-schedule',
            [VolunteerImportController::class, 'updateSchedule'])
            ->name('volunteer.update-schedule');

        Route::post('/check-duplicates',
            [VolunteerImportController::class, 'checkDuplicates'])
            ->name('volunteer.import.checkDuplicates');

        Route::post('/update-picture',
            [VolunteerImportController::class, 'updatePicture'])
            ->name('volunteer.import.updatePicture');

        Route::post('/set-default-picture',
            [VolunteerImportController::class, 'setDefaultPicture'])
            ->name('volunteer.import.setDefaultPicture');

        /* ❌ REMOVE ALL ADMIN ROUTES HERE */
    });

    /* -------- ADMIN PROFILE ROUTES (GLOBAL) -------- */
// View OWN profile
/* -------- ADMIN PROFILE ROUTES (GLOBAL) -------- */

// View OWN profile (must be above wildcard)
// Dynamic profile first
Route::get('/admin/profile/{id}', [AdminProfileController::class, 'index'])
    ->name('admin.profile');

// Self profile second
Route::get('/admin/profile', [AdminProfileController::class, 'index'])
    ->name('admin.profile.self');


// Update profile
Route::put('/admin/profile/update', [AdminProfileController::class, 'update'])
    ->name('admin.profile.update');

// Logs
Route::get('/admin/profile/logs/{id}', [AdminProfileController::class, 'getLogs'])
    ->name('admin.profile.logs');

Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->name('admin.dashboard');



// TEMP test route — add then browse /admin/test-profile/3
Route::get('/test-admin/{id}', function($id){
    return \App\Models\AdminAccount::find($id) ?: 'NOT FOUND';
});



    


    /* -------- EVENTS -------- */
    Route::prefix('events')->group(function () {
        Route::get('/create', [CreateEventController::class, 'create'])->name('events.create');
        Route::post('/store', [CreateEventController::class, 'store'])->name('events.store');
        Route::post('/{event_id}/add-volunteers',
            [EventDetailsController::class, 'addVolunteers'])->name('events.addVolunteers');
    });

    Route::get('/events/manage', [EventManagerController::class, 'index'])->name('events.manage');
    Route::get('/events/{event_id}', [EventDetailsController::class, 'show'])->name('event.details.show');

    /* -------- VOLUNTEERS -------- */
    Route::get('/volunteers_list', [VolunteerListController::class, 'index'])->name('volunteers.list');
    Route::get('/volunteers/data', [VolunteerListController::class, 'data'])->name('volunteers.data');
    Route::get('/volunteers/locations', [VolunteerListController::class, 'locations'])->name('volunteers.locations');
    Route::get('/volunteer-profile/{id}', [VolunteerProfileController::class, 'show'])->name('volunteers.show');
});
