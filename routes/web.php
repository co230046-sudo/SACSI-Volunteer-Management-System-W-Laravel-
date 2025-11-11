<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\VolunteerImportController;

// Default root redirects to login
Route::get('/', function () {
    return redirect()->route('auth.login');
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// Homepage
Route::get('/home', [HomePageController::class, 'index'])->name('home');

// Volunteer Import routes (temporary-session based)
Route::prefix('volunteer-import')->group(function () {
    // Route to go to volunteer-import page
    Route::get('/', [VolunteerImportController::class, 'index'])->name('volunteer.import.index');

    // Show Import (Invalid, Valid, Import Logs)
    Route::post('/preview', [VolunteerImportController::class, 'preview'])->name('volunteer.import.preview');
    
    // Reset/Clear Import File
    Route::post('/validate-save', [VolunteerImportController::class, 'validateAndSave'])->name('volunteer.import.validateSave');

    // Update Invalid/Valid Volunteer
    Route::post('/clear-invalid', [VolunteerImportController::class, 'clearInvalid'])->name('volunteer.import.clearInvalid');

    // Reset CSV File Import
    Route::post('/reset', [VolunteerImportController::class, 'resetImports'])->name('volunteer.import.reset');

    // Move Invalid to Valid
    Route::post('/move-invalid', [VolunteerImportController::class, 'moveInvalidToValid'])->name('volunteer.import.moveInvalidToValid');

    // Update Invalid/Valid Volunteer
    Route::put('/volunteer-import/volunteer/update-entry/{index}/{type}', [VolunteerImportController::class, 'updateVolunteerEntry'])->name('volunteer.import.update-entry');

    // Move Valid to Invalid
    Route::get('/move-valid-to-invalid/{index}', [VolunteerImportController::class, 'moveValidToInvalid'])->name('volunteer.moveValidToInvalid');

    // Delete selected invalid entries
    Route::post('/volunteer/delete-entries', [VolunteerImportController::class, 'deleteEntries'])->name('volunteer.deleteEntries');

    // Undo Delete selected invalid entries
    Route::get('/volunteer-import/undo-delete', [VolunteerImportController::class, 'undoDelete'])->name('volunteer.import.undo-delete');

    
});
