<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Volunteer;
use App\Models\VolunteerProfile;
use App\Models\ImportLog;
use App\Models\FactType;
use App\Models\FactLog;

class VolunteerImportController extends Controller
{
    public function index()
    {
        //session()->forget('csv_imported'); //Add this if import button won't appear
        $validEntries = session('validEntries', []);
        $invalidEntries = session('invalidEntries', []);
        $uploadedFileName = session('uploaded_file_name', null);
        $uploadedFilePath = session('uploaded_file_path', null);
        $importLogs = ImportLog::orderBy('created_at', 'desc')->get();

        return view('volunteer_import.volunteer_import', compact(
            'validEntries', 'invalidEntries', 'uploadedFileName', 'uploadedFilePath', 'importLogs'
        ));
    }

  public function preview(Request $request)
{
    $request->validate([
        'csv_file' => 'required|mimes:csv,txt|max:2048',
    ]);

    // Clear previous sessions
    session()->forget(['validEntries', 'invalidEntries', 'duplicateEntries', 'import_log_id']);

    $file = $request->file('csv_file');
    $filename = $file->getClientOriginalName();
    $path = $file->store('uploads', 'public');

    session([
        'uploaded_file_name' => $filename,
        'uploaded_file_path' => $path,
        'csv_imported' => true,
    ]);

    $admin = Auth::guard('admin')->user();

    // Create ImportLog immediately
    $importLog = ImportLog::create([
        'file_name' => $filename,
        'admin_id' => $admin->admin_id ?? null,
        'total_records' => 0,
        'valid_count' => 0,
        'invalid_count' => 0,
        'duplicate_count' => 0,
        'status' => 'Pending',
    ]);

    session(['import_log_id' => $importLog->import_id]);

    // Read CSV
    $rows = array_map('str_getcsv', file($file->getRealPath()));
    if (empty($rows)) {
        $importLog->update(['status' => 'Failed']);
        return back()->with('error', 'CSV file is empty.');
    }

    $header = array_map('trim', array_shift($rows));
    $header = array_map('strtolower', $header);

    $valid = [];
    $invalid = [];
    $duplicates = [];

    $seenKeys = []; // For duplicate detection within file

    foreach ($rows as $i => $row) {
        $data = $this->normalizeRow($row, $header);
        $errors = $this->validateRow($data);

        // --- CUSTOM BARANGAY + DISTRICT VALIDATION ---
        // Barangay: required, must exist in locations table for the given district
        $barangay = trim($data['barangay']);
                // District: required, must be a valid string (e.g., 'West', 'East')
        $district = trim($data['district']);
        if (empty($district)) {
            $errors['district'] = 'No district selected.';
        }

        // Barangay: required, must exist in locations table for the given district
        $barangay = trim($data['barangay']); // <-- define first
        if (empty($barangay)) {
            $errors['barangay'] = 'No barangay selected.';
        } elseif (!empty($district)) {
            $exists = DB::table('locations')
                        ->where('barangay', $barangay)   // now $barangay is defined
                        ->where('district', $district)   // match string values
                        ->exists();
            if (!$exists) {
                $errors['barangay'] = 'Barangay "' . $barangay . '" not found in District ' . $district . '.';
            }
        }



        // Column count mismatch
        if (count($row) !== count($header)) {
            $errors = array_fill_keys(array_keys($data), true);
        }

        $data['row_number'] = $i + 2;

        // Create a unique key for duplicates (e.g., email or full_name)
        $uniqueKey = strtolower($data['email'] ?? $data['full_name'] ?? 'row_' . $i);

        if (in_array($uniqueKey, $seenKeys)) {
            $duplicates[] = $data;
        } elseif ($errors) {
            $data['errors'] = $errors;
            $invalid[] = $data;
            $seenKeys[] = $uniqueKey;
        } else {
            $valid[] = $data;
            $seenKeys[] = $uniqueKey;
        }
    }

    // Save sessions
    session([
        'validEntries' => $valid,
        'invalidEntries' => $invalid,
        'duplicateEntries' => $duplicates,
    ]);

    // Update import log
    $importLog->update([
        'total_records' => count($rows),
        'valid_count' => count($valid),
        'invalid_count' => count($invalid),
        'duplicate_count' => count($duplicates),
        'status' => count($valid) > 0 ? 'Pending' : 'Failed',
    ]);

    $message = "<div style='display:flex; flex-wrap: wrap; gap:10px; align-items:center;'>";
    $message .= "<span style='color:blue;'>✅ " . count($valid) . " valid</span>";
    $message .= "<span style='color:red;'>❌ " . count($invalid) . " invalid</span>";
    $message .= "<span style='color:orange;'>⚠️ " . count($duplicates) . " duplicates</span>";
    $message .= "</div>";

    if (count($invalid) === 0 && count($valid) > 0) {
        $message .= "<div style='margin-top:5px; color:green;'>
                        All entries are valid. You may proceed to submit them. 
                        <a href='#valid-entries-section' style='text-decoration:underline;'>View valid entries</a>
                    </div>";
    } elseif (count($valid) === 0 && count($invalid) > 0) {
        $message .= "<div style='margin-top:5px; color:red;'>
                        All entries are invalid. Please correct the invalid entries before submitting any data.
                    </div>";
    } else {
        $message .= "<div style='margin-top:5px; color:orange;'>
                        Some entries are invalid. Please fix the invalid entries before submitting the valid ones.
                    </div>";
    }

    return back()->with(['success' => $message]);
}

/**
 * Normalize row: strip comments, format numbers, ensure all keys exist
 */
private function normalizeRow(array $row, array $header): array
{
    $mapping = [
        'name' => 'full_name',
        'full_name' => 'full_name',
        'full name' => 'full_name',
        'id number' => 'id_number',
        'school id' => 'id_number',
        'id num' => 'id_number',
        'id' => 'id_number',
        'email address' => 'email',
        'email' => 'email',
        'phone' => 'contact_number',
        'contact number' => 'contact_number',
        'contact' => 'contact_number',
        'emergency' => 'emergency_contact',
        'emergency contact' => 'emergency_contact',
        'fb' => 'fb_messenger',
        'fb/messenger' => 'fb_messenger',
        'messenger' => 'fb_messenger',
        'barangay' => 'barangay',
        'district' => 'district',
        'course' => 'course',
        'year' => 'year_level',
        'year level' => 'year_level',
    ];

    $normalized = [];

    foreach ($header as $index => $col) {
        $key = strtolower(trim($col));
        $key = str_replace([' ', '-'], '_', $key);
        if (isset($mapping[$key])) $key = $mapping[$key];

        // Strip inline comments
        $value = (string)($row[$index] ?? '');
        $value = preg_replace('/#.*/', '', $value);
        $value = trim($value);
        $value = in_array($value, ['-', 'N/A']) ? '' : $value;

        if ($key === 'id_number') $value = strtoupper($value);
        if (in_array($key, ['contact_number', 'emergency_contact'])) {
            $value = preg_replace('/[^\d+]/', '', $value);
        }

        // Trim barangay/district to avoid mismatch
        if ($key === 'barangay' || $key === 'district') {
            $value = trim($value);
        }

        $normalized[$key] = $value;
    }

    // Ensure all expected keys exist
    $defaults = [
        'full_name', 'id_number', 'email', 'contact_number', 'emergency_contact',
        'fb_messenger', 'barangay', 'district', 'course', 'year_level'
    ];

    foreach ($defaults as $key) {
        if (!isset($normalized[$key])) $normalized[$key] = '';
    }

    return $normalized;
}
/**
 * Validate each row's fields according to new rules
 */
private function validateRow(array $data)
{
    $errors = [];

    // Full name: required, letters, spaces, dots, hyphens, apostrophes only
    if (empty($data['full_name']) || !preg_match("/^[A-Za-zÑñ\s\.\'-]+$/u", $data['full_name'])) {
        $errors['full_name'] = 'Full Name is required and can only contain letters, spaces, dots, hyphens, or apostrophes.';
    }

    // School ID: 6–7 digits
    if (empty($data['id_number']) || !preg_match('/^\d{6,7}$/', $data['id_number'])) {
        $errors['id_number'] = 'School ID must be 6 or 7 digits.';
    }

    // Course: required, letters and spaces only
    if (empty($data['course']) || !preg_match('/^[A-Za-z\s]+$/', $data['course'])) {
        $errors['course'] = 'Course is required and can only contain letters and spaces.';
    }

    // Year level: 1–4 only
    if (empty($data['year_level']) || !in_array($data['year_level'], ['1','2','3','4'])) {
        $errors['year_level'] = 'Year must be 1, 2, 3, or 4.';
    }

    // Contact Number: Philippine numbers only
    if (empty($data['contact_number']) || !preg_match('/^(09\d{9}|\+639\d{9})$/', $data['contact_number'])) {
        $errors['contact_number'] = 'Contact Number must be a valid Philippine mobile number.';
    }

    // Emergency contact: Philippine numbers only
    if (empty($data['emergency_contact']) || !preg_match('/^(09\d{9}|\+639\d{9})$/', $data['emergency_contact'])) {
        $errors['emergency_contact'] = 'Emergency Contact must be a valid Philippine mobile number.';
    }

    // Email: Gmail or adzu.edu.ph only
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) 
        || !preg_match('/^[A-Za-z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/i', $data['email'])) {
        $errors['email'] = 'Email must be valid and end with @gmail.com or @adzu.edu.ph.';
    }

    // FB/Messenger: optional, must be a valid Facebook URL if provided
    $fb = trim($data['fb_messenger'] ?? '');
    if (!empty($fb)) {
        if (!filter_var($fb, FILTER_VALIDATE_URL)) {
            $errors['fb_messenger'] = 'FB/Messenger must be a valid URL like https://www.facebook.com/username';
        } else {
            $host = parse_url($fb, PHP_URL_HOST);
            if ($host === false || stripos($host, 'facebook.com') === false) {
                $errors['fb_messenger'] = 'FB/Messenger must be a Facebook link';
            }
        }
    }

    // District: required, string matching your DB ('West', 'East', 'North', 'South', 'Poblacion')
    $district = trim($data['district'] ?? '');
    if (empty($district)) {
        $errors['district'] = 'No district selected.';
    }

    // Barangay: required, must exist in locations table for the given district
    $barangay = trim($data['barangay'] ?? '');
    if (empty($barangay)) {
        $errors['barangay'] = 'No barangay selected.';
    } elseif (!empty($district)) {
        $exists = DB::table('locations')
                    ->where('barangay', $barangay)
                    ->where('district', $district)
                    ->exists();
        if (!$exists) {
            $errors['barangay'] = 'Barangay "' . $barangay . '" not found in District ' . $district . '.';
        }
    }

    return empty($errors) ? null : $errors;
}

public function updateVolunteerEntry(Request $request, $index, $type)
{
    // Get session entries
    $entries = session($type . 'Entries', []);
    if (!isset($entries[$index])) {
        return back()->with('error', '⚠️ Entry not found.');
    }

    $entry = $entries[$index];

    // Normalize inputs
    $input = array_map('trim', $request->all());

    if (isset($input['contact_number'])) {
        $input['contact_number'] = preg_replace('/[^\d+]/', '', $input['contact_number']);
    }
    if (isset($input['emergency_contact'])) {
        $input['emergency_contact'] = preg_replace('/[^\d+]/', '', $input['emergency_contact']);
    }
    if (isset($input['id_number'])) {
        $input['id_number'] = strtoupper($input['id_number']);
    }

    // Validation rules
    $rules = [
        'full_name' => ['required', 'regex:/^[A-Za-zÑñ\s\.\'-]+$/u', 'max:255'],
        'id_number' => ['required', 'regex:/^[0-9]{6,7}$/'],
        'course' => 'required|string|max:100',
        'year_level' => ['required', 'in:1,2,3,4'],
        'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
        'emergency_contact' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
        'email' => ['required','email','regex:/^[A-Za-z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/i'],
        'fb_messenger' => ['nullable'],
        'barangay' => ['required'],
        'district' => ['required'], // now string validation
    ];

    $messages = [
        'year_level.in' => 'Year must be 1, 2, 3, or 4.',
        'district.required' => 'No district selected.',
        'barangay.required' => 'No barangay selected.',
    ];

    $validator = \Validator::make($input, $rules, $messages);
    $errors = $validator->fails() ? $validator->errors()->toArray() : [];

    // --- CUSTOM FB/MESSENGER VALIDATION ---
    if (!empty($input['fb_messenger'])) {
        $fb = $input['fb_messenger'];
        if (!filter_var($fb, FILTER_VALIDATE_URL)) {
            $errors['fb_messenger'] = ['FB/Messenger must be a valid URL like https://www.facebook.com/username'];
        } else {
            $host = parse_url($fb, PHP_URL_HOST);
            if (!$host || stripos($host, 'facebook.com') === false) {
                $errors['fb_messenger'] = ['FB/Messenger must be a Facebook link'];
            }
        }
    }

    // --- CUSTOM BARANGAY + DISTRICT VALIDATION ---
    if (!empty($input['barangay']) && !empty($input['district'])) {
        $exists = DB::table('locations')
            ->where('barangay', $input['barangay'])
            ->where('district', $input['district'])
            ->exists();
        if (!$exists) {
            $errors['barangay'] = ['Barangay "' . $input['barangay'] . '" and District "' . $input['district'] . '" not found.'];
        }
    }

    $updatedFields = [];

    // Track valid updates only
    foreach ($input as $field => $value) {
        if (!isset($errors[$field])) {
            $entries[$index][$field] = $value;
            $updatedFields[$field] = $value;
        }
    }

    // Save errors in session for Blade
    $entries[$index]['errors'] = $errors;
    session([$type . 'Entries' => $entries]);

    // Update DB if volunteer exists and there are valid changes
    $validUpdates = array_diff_key($updatedFields, $errors);
    if (!empty($entries[$index]['volunteer_id']) && !empty($validUpdates)) {
        $volunteer = Volunteer::find($entries[$index]['volunteer_id']);
        if ($volunteer) {
            $dataToUpdate = $validUpdates;
            $dataToUpdate['status'] = 'Active';
            $volunteer->update($dataToUpdate);
        }
    }

    // Human-readable labels
    $labels = [
        'full_name' => 'Full Name',
        'id_number' => 'School ID',
        'course' => 'Course',
        'year_level' => 'Year',
        'contact_number' => 'Contact #',
        'emergency_contact' => 'Emergency #',
        'email' => 'Email',
        'fb_messenger' => 'FB/Messenger',
        'barangay' => 'Barangay',
        'district' => 'District',
    ];

    // Build message
    $rowNumber = $index + 1;
    $validParts = [];
    $invalidParts = [];

    foreach ($validUpdates as $field => $value) {
        if (isset($labels[$field])) {
            $validParts[] = "{$labels[$field]}: {$value}";
        }
    }

    foreach ($errors as $field => $msgs) {
        if (isset($labels[$field])) {
            $submittedValue = $input[$field] ?? '';
            $invalidParts[] = "{$labels[$field]}: {$submittedValue} (" . implode(', ', (array)$msgs) . ")";
        }
    }

    $message = "Row #{$rowNumber}<br>";

    if (!empty($validParts)) {
        $message .= "✅ Valid: <span style='color:#007bff;'>" . implode(' <span style=\"color:#555;font-weight:normal;\">|</span> ', $validParts) . "</span>";
    }

    if (!empty($invalidParts)) {
        if (!empty($validParts)) {
            $message .= "<hr style='margin:4px 0; padding:0; border:none; border-top:1px solid #555;'>"; 
        }
        $message .= "⚠️ Invalid: <span style='color:red;'>" . implode(' <span style=\"color:#555;font-weight:normal;\">|</span> ', $invalidParts) . "</span>";
    }

    if (empty($validParts) && empty($invalidParts)) {
        $message .= "ℹ️ No changes were made.";
    }

    $flashType = !empty($validParts) ? 'success' : 'error';

    return redirect()->route('volunteer.import.index')
        ->with($flashType, $message)
        ->with('last_updated_table', $type)
        ->with('last_updated_index', $index);
}


  public function moveInvalidToValid(Request $request)
{
    $invalid = session('invalidEntries', []);
    $valid = session('validEntries', []);
    $movedNames = [];
    $skippedNames = [];

    if ($request->has('selected_invalid')) {
        foreach ($request->input('selected_invalid') as $index) {
            if (!isset($invalid[$index])) continue;

            $entry = $invalid[$index];
            $errors = $this->validateRow($entry); // re-validate

            if ($errors) {
                // Skip invalid entries
                $skippedNames[] = $entry['full_name'] ?? 'N/A';
                continue;
            }

            // Store original index to restore order if moved back
            $entry['original_index'] = $index;

            // Remove old error info
            unset($entry['errors'], $entry['error_message']);

            // Move to valid
            $valid[] = $entry;
            $movedNames[] = $entry['full_name'] ?? 'N/A';

            // Remove from invalid
            unset($invalid[$index]);
        }

        // Reindex arrays
        $invalid = array_values($invalid);
        $valid = array_values($valid);

        session([
            'validEntries' => $valid,
            'invalidEntries' => $invalid,
        ]);
    }

    $messageParts = [];
    if ($movedNames) {
        $messageParts[] = "✅ Moved to verified: <span style='color:#007bff;'>" . implode(', ', $movedNames) . "</span>.";
    }
    if ($skippedNames) {
        $messageParts[] = "⚠️ Could not move (missing/invalid fields): <span style='color:red;'>" . implode(', ', $skippedNames) . "</span>.";
    }
    if (empty($messageParts)) {
        $messageParts[] = "ℹ️ No invalid entries selected to move.";
    }

    return back()->with('success', implode(' ', $messageParts));
}


public function moveValidToInvalid(Request $request, $index)
{
    $valid = session('validEntries', []);
    $invalid = session('invalidEntries', []);

    if (isset($valid[$index])) {
        $entry = $valid[$index];
        unset($valid[$index]);

        $entry['errors'] = $this->validateRow($entry);

        if (isset($entry['original_index'])) {
            $invalid[$entry['original_index']] = $entry;
        } else {
            $invalid[] = $entry;
        }

        ksort($invalid);
        $invalid = array_values($invalid);

        session([
            'validEntries' => array_values($valid),
            'invalidEntries' => $invalid,
            'last_updated_table' => 'invalid',
            'last_updated_index' => isset($entry['original_index']) ? $entry['original_index'] : count($invalid) - 1,
            'invalid_success' => "⚠️ Moved back to invalid: <span style='color:red;'>{$entry['full_name']}</span>."
        ]);
    } else {
        session(['invalid_error' => "ℹ️ No valid entry selected to move back."]);
    }

    return back()->withFragment('invalid-entries-table');
}
// DELETE ENTRIES
public function deleteEntries(Request $request)
{
    $tableType = $request->input('table_type'); // invalid / valid / logs
    $selected = $request->input('selected', []);

    if (empty($selected)) {
        return back()->with('error', 'No entries selected for deletion.');
    }

    $deletedData = []; // store deleted entries for undo

    switch ($tableType) {
        case 'invalid':
            $entries = session('invalidEntries', []);
            foreach ($selected as $index) {
                if (isset($entries[$index])) {
                    $deletedData[$index] = $entries[$index];
                    unset($entries[$index]);
                }
            }
            session(['invalidEntries' => array_values($entries)]);
            break;

        case 'valid':
            $entries = session('validEntries', []);
            foreach ($selected as $index) {
                if (isset($entries[$index])) {
                    $deletedData[$index] = $entries[$index];
                    unset($entries[$index]);
                }
            }
            session(['validEntries' => array_values($entries)]);
            break;

        case 'logs':
            $deletedEntries = ImportLog::whereIn('import_id', $selected)->get();
            foreach ($deletedEntries as $entry) {
                $deletedData[] = $entry->toArray();
            }
            ImportLog::whereIn('import_id', $selected)->delete();
            break;

        default:
            return back()->with('error', 'Invalid table type.');
    }

    if (!empty($deletedData)) {
        session(['deletedEntriesUndo' => [
            'tableType' => $tableType,
            'data' => $deletedData,
            'timestamp' => now()
        ]]);

        $deletedList = [];
        foreach ($deletedData as $index => $item) {
            $name = $item['full_name'] ?? $item['file_name'] ?? 'Unknown';
            $deletedList[] = "#".($index+1)." ".$name; // include row number
        }

        $message = "<div style='display:flex; flex-wrap:wrap; gap:5px; align-items:center;'>
                        ✅ Deleted: " . implode(', ', $deletedList) . "
                        <a href='" . route('volunteer.import.undo-delete') . "' 
                           style='margin-left:10px; padding:4px 10px; font-size:0.9em; background:#007bff; color:white; text-decoration:none; border-radius:4px; cursor:pointer;'>
                           Undo
                        </a>
                        <span style='margin-left:5px; color:#555;'>to restore the deleted entries</span>
                    </div>";
    } else {
        $message = "ℹ️ No entries were deleted.";
    }

    return back()->with('success', $message);
}

// UNDO DELETE
public function undoDelete(Request $request)
{
    $deleted = session('deletedEntriesUndo');

    if (!$deleted || empty($deleted['data']) || !isset($deleted['tableType'])) {
        return back()->with('error', 'Nothing to undo.');
    }

    $tableType = $deleted['tableType'];
    $data = $deleted['data'];

    switch ($tableType) {
        case 'invalid':
            $entries = session('invalidEntries', []);
            $entries = array_merge($entries, $data);
            session(['invalidEntries' => array_values($entries)]);
            break;

        case 'valid':
            $entries = session('validEntries', []);
            $entries = array_merge($entries, $data);
            session(['validEntries' => array_values($entries)]);
            break;

        case 'logs':
            foreach ($data as $entry) {
                if (!ImportLog::where('import_id', $entry['import_id'])->exists()) {
                    ImportLog::create($entry);
                }
            }
            break;

        default:
            return back()->with('error', 'Invalid table type for undo.');
    }

    session()->forget('deletedEntriesUndo');

    $restoredList = [];
    foreach ($data as $index => $item) {
        $name = $item['full_name'] ?? $item['file_name'] ?? 'Unknown';
        $restoredList[] = "#".($index+1)." ".$name; // include row number
    }

    $message = "<div style='display:flex; flex-wrap:wrap; gap:5px; align-items:center;'>
                    ✅ Restored: " . implode(', ', $restoredList) . "
                    <span style='margin-left:5px; color:#555;'>– your deleted entries have been successfully restored.</span>
                </div>";

    return back()->with('success', $message);
}



    public function validateAndSave(Request $request)
    {
        $selectedIndexes = $request->input('selected_valid', []);
        $validEntries = session('validEntries', []);
        $invalidEntries = session('invalidEntries', []);
        $admin = Auth::guard('admin')->user();
        if (!$admin) return back()->with('error_modal', 'Admin not authenticated.');
        $adminId = $admin->admin_id;

        if (empty($validEntries)) {
            $failedFactType = FactType::firstOrCreate(['type_name' => 'Failed Import'], ['description' => 'Log for failed volunteer imports']);
            FactLog::create([
                'fact_type_id' => $failedFactType->fact_type_id,
                'import_id' => null,
                'admin_id' => $adminId,
                'entity_type' => 'Volunteer Import',
                'entity_id' => null,
                'action' => 'Failed',
                'details' => json_encode(['reason' => 'No valid entries to import']),
                'logged_at' => now(),
            ]);
            return back()->with('error_modal', 'No verified entries available.');
        }

        if (!empty($invalidEntries)) {
            $invalidRows = implode(', ', array_keys($invalidEntries));
            $failedFactType = FactType::firstOrCreate(['type_name' => 'Failed Import'], ['description' => 'Log for failed volunteer imports']);
            FactLog::create([
                'fact_type_id' => $failedFactType->fact_type_id,
                'import_id' => null,
                'admin_id' => $adminId,
                'entity_type' => 'Volunteer Import',
                'entity_id' => null,
                'action' => 'Failed',
                'details' => json_encode([
                    'invalid_rows' => array_keys($invalidEntries),
                    'invalid_count' => count($invalidEntries),
                    'reason' => 'There are invalid entries preventing import'
                ]),
                'logged_at' => now(),
            ]);
            return back()->with('error_modal', "Cannot upload. Invalid entries found in row(s): $invalidRows. Please fix them first.");
        }

        if (empty($selectedIndexes)) return back()->with('error_modal', 'No verified entries selected to save.');

        $entriesToSave = [];
        foreach ($selectedIndexes as $index) if (isset($validEntries[$index])) $entriesToSave[] = $validEntries[$index];
        if (empty($entriesToSave)) return back()->with('error_modal', 'Selected entries not found.');

        try {
            DB::transaction(function () use ($entriesToSave, $adminId) {

                $importLog = ImportLog::create([
                    'file_name' => session('uploaded_file_name') ?? 'CSV Upload',
                    'admin_id' => $adminId,
                    'total_records' => count($entriesToSave),
                    'valid_count' => count($entriesToSave),
                    'invalid_count' => 0,
                    'duplicate_count' => 0,
                    'status' => 'Completed',
                ]);

                $duplicates = 0;

                $factType = FactType::firstOrCreate(['type_name' => 'Import Verified'], ['description' => 'Log for imported verified volunteers']);

                foreach ($entriesToSave as $entry) {
                    $volunteer = Volunteer::firstOrCreate(
                        ['volunteer_code' => $entry['id_number'] ?? 'TEMP-' . uniqid()],
                        [
                            'full_name' => $entry['full_name'] ?? null,
                            'email' => $entry['email'] ?? null,
                            'contact_number' => $entry['contact_number'] ?? null,
                            'status' => 'Active',
                        ]
                    );

                    if (!$volunteer->wasRecentlyCreated) $duplicates++;

                    VolunteerProfile::updateOrCreate(
                        ['volunteer_id' => $volunteer->id],
                        [
                            'import_id' => $importLog->import_id,
                            'full_name' => $entry['full_name'] ?? null,
                            'id_number' => $entry['id_number'] ?? null,
                            'course' => $entry['course'] ?? null,
                            'year_level' => $entry['year_level'] ?? null,
                            'contact_number' => $entry['contact_number'] ?? null,
                            'emergency_contact' => $entry['emergency_contact'] ?? null,
                            'email' => $entry['email'] ?? null,
                            'fb_messenger' => $entry['fb_messenger'] ?? null,
                            'barangay' => $entry['barangay'] ?? null,
                            'district' => $entry['district'] ?? null,
                            'status' => 'Active',
                        ]
                    );

                    FactLog::create([
                        'fact_type_id' => $factType->fact_type_id,
                        'import_id' => $importLog->import_id,
                        'admin_id' => $adminId,
                        'entity_type' => 'Volunteer',
                        'entity_id' => $volunteer->id,
                        'action' => 'Imported',
                        'details' => json_encode([
                            'full_name' => $volunteer->full_name,
                            'email' => $volunteer->email,
                            'id_number' => $volunteer->volunteer_code
                        ]),
                        'logged_at' => now(),
                    ]);
                }

                $importLog->update(['duplicate_count' => $duplicates]);
            });

            session()->forget(['validEntries', 'invalidEntries', 'uploaded_file_name', 'uploaded_file_path', 'csv_imported']);
            return back()->with('success_modal', 'Selected verified entries saved successfully.');

        } catch (\Exception $e) {
            Log::error('Import failed: ' . $e->getMessage());
            return back()->with('error_modal', 'Failed to save entries. Check logs.');
        }
    }

    public function clearInvalid(Request $request)
    {
        session()->forget('invalidEntries');
        return back()->with('success', 'Invalid entries cleared from preview.');
    }

    public function resetImports(Request $request)
    {
        $validCount = session()->has('validEntries') ? count(session('validEntries')) : 0;
        $invalidCount = session()->has('invalidEntries') ? count(session('invalidEntries')) : 0;
        $totalCleared = $validCount + $invalidCount;
        $fileName = session('uploaded_file_name', 'N/A');
        $originalImportId = session('import_log_id');
        $currentAdminId = auth()->guard('admin')->id() ?? null;
        $originalLog = null;

        if ($originalImportId) {
            $originalLog = ImportLog::find($originalImportId);
            if ($originalLog) {
                $originalLog->update([
                    'admin_id'     => $originalLog->admin_id ?? $currentAdminId,
                    'total_records'=> $originalLog->total_records ?: $totalCleared,
                    'valid_count'  => $originalLog->valid_count ?: $validCount,
                    'invalid_count'=> $originalLog->invalid_count ?: $invalidCount,
                    'status'       => 'Cancelled',
                    'remarks'      => "This import was reset on "
                                      . now()->format('M d, Y h:i A')
                                      . " by Admin ID: {$currentAdminId}",
                ]);

                $this->logFact(
                    'Import Cancelled',
                    $originalLog->admin_id,
                    'import_logs',
                    $originalLog->import_id,
                    'Cancelled',
                    "Original import was reset by Admin ID: {$currentAdminId}"
                );
            }
        }

        $resetLog = ImportLog::create([
            'file_name'       => $fileName,
            'admin_id'        => $currentAdminId,
            'total_records'   => $totalCleared,
            'valid_count'     => $validCount,
            'invalid_count'   => $invalidCount,
            'duplicate_count' => 0,
            'status'          => 'Reset',
            'remarks'         => "Reset import preview cleared $totalCleared row(s) on "
                                 . now()->format('M d, Y h:i A')
                                 . " by Admin ID: {$currentAdminId}",
        ]);

        if (!empty($originalLog)) {
            $originalLog->update([
                'remarks' => "This import was reset on "
                             . now()->format('M d, Y h:i A')
                             . " by Admin ID: {$currentAdminId} (Reset Log ID: {$resetLog->import_id})",
            ]);

            $this->logFact(
                'Import Cancelled',
                $originalLog->admin_id,
                'import_logs',
                $originalLog->import_id,
                'Cancelled',
                "Original import was reset by Admin ID: {$currentAdminId}. Reset Log ID: {$resetLog->import_id}"
            );
        }

        $this->logFact(
            'Import Reset',
            $currentAdminId,
            'import_logs',
            $resetLog->import_id,
            'Reset',
            "Reset import preview cleared $totalCleared rows by Admin ID: {$currentAdminId}"
        );

        session()->forget([
            'validEntries',
            'invalidEntries',
            'uploaded_file_name',
            'uploaded_file_path',
            'csv_imported',
            'import_log_id',
        ]);

        session()->forget('lastUsedTable');
        session()->flash('clearLastUsedTable', true);

        $message = "♻️ Cleared all imported data. Original import updated and reset log created "
                   . "(<span style='color:#B2000C;'>ID: {$resetLog->import_id}</span>).";

        return back()->with('success', $message);
    }

    private function logFact($factTypeName, $adminId, $entityType, $entityId, $action, $details = null)
    {
        $factType = FactType::firstOrCreate(['type_name' => $factTypeName], ['description' => $factTypeName]);

        FactLog::create([
            'fact_type_id' => $factType->fact_type_id,
            'admin_id' => $adminId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'details' => $details,
            'timestamp' => now(),
        ]);
    }
}
