<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\VolunteerImportController;
use App\Http\Controllers\CreateEventController;
use App\Http\Controllers\VolunteerListController;
use App\Http\Controllers\VolunteerProfileController;
use App\Http\Controllers\EventDetailsController;
use App\Http\Controllers\DashboardController;


Route::get('/test123', function () {
    return "THIS IS THE REAL PROJECT";
});

Route::get('/', function () {
    return redirect()->route('auth.login');
});

/* ------------------ AUTH ROUTES (PUBLIC) ------------------ */
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


/* ------------------ GLOBAL ADMIN PROFILE ROUTE (FIX) ------------------ */
Route::get('/admin/profile', function () {
    return view('admin.profile');
})->name('admin.profile');


/* ------------------ PROTECTED ROUTES (ADMIN ONLY) ------------------ */
Route::middleware(['auth:admin'])->group(function () {

    /* Dashboard */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* Home Page */
    Route::get('/home', [HomePageController::class, 'index'])->name('home');

    /* --- Import Volunteer --- */
    Route::prefix('volunteer-import')->group(function () {

        Route::get('/', [VolunteerImportController::class, 'index'])
            ->name('volunteer.import.index');

        Route::post('/preview', [VolunteerImportController::class, 'preview'])
            ->name('volunteer.import.preview');

        Route::post('/validate-save', [VolunteerImportController::class, 'validateAndSave'])
            ->name('volunteer.import.validateSave');

        Route::post('/clear-invalid', [VolunteerImportController::class, 'clearInvalid'])
            ->name('volunteer.import.clearInvalid');

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
    });

    /* ------------------ CREATE EVENT ROUTES ------------------ */
    Route::prefix('events')->group(function () {

        Route::get('/create', [CreateEventController::class, 'create'])
            ->name('events.create');

        Route::post('/store', [CreateEventController::class, 'store'])
            ->name('events.store');
    });

    /* ------------------ VOLUNTEER LIST ------------------ */
    Route::get('/volunteers_list', [VolunteerListController::class, 'index'])->name('volunteers.list');

    Route::get('/volunteers/data', [VolunteerListController::class, 'data'])->name('volunteers.data');

    Route::get('/volunteers/locations', [VolunteerListController::class, 'locations'])->name('volunteers.locations');

    /* ------------------ VOLUNTEER PROFILE ------------------ */
    Route::get('/volunteer-profile/{id}', [VolunteerProfileController::class, 'show'])
        ->name('volunteers.show');

    /* ------------------ Event Details ------------------ */
    Route::get('/event_details', [EventDetailsController::class, 'index'])->name('event.details');
});
