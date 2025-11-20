<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\VolunteerImportController;
use App\Http\Controllers\CreateEventController;

Route::get('/', function () {
    return redirect()->route('auth.login');
});

/* ------------------ AUTH ROUTES (PUBLIC) ------------------ */
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/* ------------------ PROTECTED ROUTES (ADMIN ONLY) ------------------ */
Route::middleware(['auth:admin'])->group(function () {

    Route::get('/home', [HomePageController::class, 'index'])->name('home');

    Route::prefix('volunteer-import')->group(function () {

        Route::get('/', [VolunteerImportController::class, 'index'])->name('volunteer.import.index');

        Route::post('/preview', [VolunteerImportController::class, 'preview'])->name('volunteer.import.preview');

        Route::post('/validate-save', [VolunteerImportController::class, 'validateAndSave'])
            ->name('volunteer.import.validateSave');

        Route::post('/clear-invalid', [VolunteerImportController::class, 'clearInvalid'])
            ->name('volunteer.import.clearInvalid');

        Route::post('/reset', [VolunteerImportController::class, 'resetImports'])
            ->name('volunteer.import.reset');

        Route::post('/move-invalid', [VolunteerImportController::class, 'moveInvalidToValid'])
            ->name('volunteer.import.moveInvalidToValid');

        Route::put('/volunteer-import/volunteer/update-entry/{index}/{type}', 
            [VolunteerImportController::class, 'updateVolunteerEntry'])
            ->name('volunteer.import.update-entry');

        Route::get('/move-valid-to-invalid/{index}', 
            [VolunteerImportController::class, 'moveValidToInvalid'])
            ->name('volunteer.moveValidToInvalid');

        Route::post('/volunteer/delete-entries', 
            [VolunteerImportController::class, 'deleteEntries'])
            ->name('volunteer.deleteEntries');

        Route::get('/volunteer-import/undo-delete', 
            [VolunteerImportController::class, 'undoDelete'])
            ->name('volunteer.import.undo-delete');

        Route::put('/volunteers/{id}/update-schedule', 
            [VolunteerImportController::class, 'updateSchedule'])
            ->name('volunteer.update-schedule');

        Route::post('/check-duplicates', 
            [VolunteerImportController::class, 'checkDuplicates'])
            ->name('volunteer.import.checkDuplicates');
    });
});

//Route::get('/create-event', [CreateEventController::class, 'create-event'])->name('create-event');