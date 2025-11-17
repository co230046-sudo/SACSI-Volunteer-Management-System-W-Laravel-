<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Models\VolunteerProfile;
use App\Models\ImportLog;
use App\Models\FactLog;
use App\Models\Course;
use App\Models\Location;

class VolunteerImportController extends Controller
{
    public function index()
    {
        $validEntries = session('validEntries', []);
        $invalidEntries = session('invalidEntries', []);
        $uploadedFileName = session('uploaded_file_name', null);
        $uploadedFilePath = session('uploaded_file_path', null);

        // Ensure class_schedule exists in all entries
        foreach ($validEntries as &$entry) {
            if (!isset($entry['class_schedule'])) $entry['class_schedule'] = 'No class schedule';
        }
        foreach ($invalidEntries as &$entry) {
            if (!isset($entry['class_schedule'])) $entry['class_schedule'] = 'No class schedule';
        }

        $importLogs = ImportLog::orderBy('created_at', 'desc')->get();

        return view('volunteer_import.volunteer_import', compact(
            'validEntries', 'invalidEntries', 'uploadedFileName', 'uploadedFilePath', 'importLogs'
        ));
    }

    /**
     * Preview CSV import
     */
    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $filename = $file->getClientOriginalName();
        $path = $file->store('uploads', 'public');

        session([
            'uploaded_file_name' => $filename,
            'uploaded_file_path' => $path,
            'csv_imported'       => true,
        ]);

        $admin = Auth::guard('admin')->user();

        $importLog = ImportLog::create([
            'file_name'       => $filename,
            'admin_id'        => $admin->admin_id ?? null,
            'total_records'   => 0,
            'valid_count'     => 0,
            'invalid_count'   => 0,
            'duplicate_count' => 0,
            'status'          => 'Pending',
            'remarks'         => null,
        ]);

        session(['import_log_id' => $importLog->import_id]);

        $rows = array_map('str_getcsv', file($file->getRealPath()));
        if (empty($rows)) {
            $importLog->update(['remarks' => 'Preview completed: 0 rows found.', 'total_records' => 0]);
            return back()->with('error', 'CSV file is empty.');
        }

        $header = array_map('strtolower', array_map('trim', array_shift($rows)));

        $valid = [];
        $invalid = [];
        $duplicates = [];
        $seenKeys = [];

        foreach ($rows as $i => $row) {
            $data = $this->normalizeRow($row, $header);
            $errors = $this->validateRow($data);

            if (count($row) !== count($header)) {
                $errors = array_fill_keys(array_keys($data), true);
            }

            $data['row_number'] = $i + 2;
            $uniqueKey = strtolower($data['email'] ?? $data['full_name'] ?? 'row_' . $i);

            if (in_array($uniqueKey, $seenKeys)) {
                $duplicates[] = $data;
            } elseif (!empty($errors)) {
                $data['errors'] = $errors;
                $invalid[] = $data;
                $seenKeys[] = $uniqueKey;
            } else {
                $valid[] = $data;
                $seenKeys[] = $uniqueKey;
            }
        }

        session([
            'validEntries'     => $valid,
            'invalidEntries'   => $invalid,
            'duplicateEntries' => $duplicates,
        ]);

        $importLog->update([
            'total_records'   => count($rows),
            'valid_count'     => count($valid),
            'invalid_count'   => count($invalid),
            'duplicate_count' => count($duplicates),
            'status'          => 'Pending',
            'remarks'         => sprintf(
                'Preview completed: %d valid, %d invalid, %d duplicates.',
                count($valid), count($invalid), count($duplicates)
            ),
        ]);

        if ($admin) {
            $this->logFact(
                'Preview Import',
                $admin->admin_id,
                'Volunteer Import',
                $importLog->import_id,
                'Previewed',
                [
                    'total'      => count($rows),
                    'valid'      => count($valid),
                    'invalid'    => count($invalid),
                    'duplicates' => count($duplicates),
                ]
            );
        }

        // --- Build preview message ---
        $message = "<div style='display:flex; flex-wrap: wrap; gap:10px; align-items:center;'>
                        <span style='color:blue;'>✅ " . count($valid) . " valid</span>
                        <span style='color:red;'>❌ " . count($invalid) . " invalid</span>
                        <span style='color:orange;'>⚠️ " . count($duplicates) . " duplicates</span>
                    </div>";

                    // --- Determine redirect anchor based on entry counts ---
                    if (count($invalid) === 0 && count($valid) > 0) {
                        // All valid
                        $redirectAnchor = '#import-Section-valid';
                        $message .= "<div style='margin-top:5px; color:green;'>
                                        All entries are valid. 
                                        <a href='#import-Section-valid' style='text-decoration:underline;'>View valid entries</a>
                                    </div>";
                    } elseif (count($valid) === 0 && count($invalid) > 0) {
                        // All invalid
                        $redirectAnchor = '#import-Section-invalid';
                        $message .= "<div style='margin-top:5px; color:red;'>
                                        All entries are invalid. Please correct the invalid entries before submitting any data. 
                                        <a href='#import-Section-invalid' style='text-decoration:underline;'>View invalid entries</a>
                                    </div>";
                    } else {
                        // Mixed entries -> go to invalid section for corrections
                        $redirectAnchor = '#import-Section-invalid';
                        $message .= "<div style='margin-top:5px; color:orange;'>
                                        Some entries are invalid. Please fix them before submitting the valid ones. 
                                        <a href='#import-Section-invalid' style='text-decoration:underline;'>Go to invalid entries</a>
                                    </div>";
                    }


        // --- Redirect back to the correct section ---
        return redirect(url()->previous() . $redirectAnchor)
            ->with('success', $message);
    }


    /**
     * Normalize row: strip comments, format numbers, ensure all keys exist
     */
    private function normalizeRow(array $row, array $header): array
    {
        $mapping = [
            'name'=>'full_name','full_name'=>'full_name','full name'=>'full_name',
            'id number'=>'id_number','school id'=>'id_number','id num'=>'id_number','id'=>'id_number',
            'email address'=>'email','email'=>'email',
            'phone'=>'contact_number','contact number'=>'contact_number','contact'=>'contact_number',
            'emergency'=>'emergency_contact','emergency contact'=>'emergency_contact',
            'fb'=>'fb_messenger','fb/messenger'=>'fb_messenger','messenger'=>'fb_messenger',
            'barangay'=>'barangay','district'=>'district','course'=>'course','year'=>'year_level','year level'=>'year_level',
            'class schedule'=>'class_schedule','class_schedule'=>'class_schedule',
        ];

        $normalized = [];

        foreach ($header as $index => $col) {
            $key = strtolower(trim($col));
            $key = str_replace([' ', '-'], '_', $key);
            $key = $mapping[$key] ?? $key;

            $value = (string)($row[$index] ?? '');

            /**
             * CLASS SCHEDULE — SPECIAL HANDLING
             * Accept breaklines (\n, \r\n), slashes (/), dashes (-), colons (:)
             * Flatten into a single clean line but preserve everything else.
             */
            if ($key === 'class_schedule') {

                // Replace line breaks and tabs with a space
                $value = str_replace(["\r\n", "\n", "\r", "\t"], ' ', $value);

                // Remove weird spacing
                $value = preg_replace('/\s+/', ' ', $value);

                // Final clean trim
                $normalized[$key] = trim($value);
                continue;
            }

            // Remove #comments
            $value = preg_replace('/#.*/', '', $value);

            $value = trim($value);

            if (in_array($value, ['-', 'N/A'])) {
                $value = '';
            }

            if ($key === 'id_number') {
                $value = strtoupper($value);
            }

            if (in_array($key, ['contact_number','emergency_contact'])) {
                $value = preg_replace('/[^\d+]/', '', $value);
            }

            $normalized[$key] = $value;
        }

        // Ensure required keys exist
        foreach ([
            'full_name','id_number','email','contact_number','emergency_contact',
            'fb_messenger','barangay','district','course','year_level','class_schedule'
        ] as $key) {
            if (!isset($normalized[$key])) $normalized[$key] = '';
        }

        return $normalized;
    }

    /**
     * Validate each row's fields
     */
    private function validateRow(array $data)
    {
        $errors = [];

        if (empty($data['full_name']) || !preg_match("/^[A-Za-zÑñ\s\.\'-]+$/u",$data['full_name']))
            $errors['full_name'] = 'Full Name is required and can only contain letters, spaces, dots, hyphens, or apostrophes.';

        if (empty($data['id_number']) || !preg_match('/^\d{6,7}$/',$data['id_number']))
            $errors['id_number'] = 'School ID must be 6 or 7 digits.';

        if (empty($data['course']) || !preg_match('/^[A-Za-z\s]+$/',$data['course']))
            $errors['course'] = 'Course is required and can only contain letters and spaces.';

        if (empty($data['year_level']) || !in_array($data['year_level'],['1','2','3','4']))
            $errors['year_level'] = 'Year must be 1, 2, 3, or 4.';

        foreach (['contact_number','emergency_contact'] as $field) {
            if (empty($data[$field]) || !preg_match('/^(09\d{9}|\+639\d{9})$/',$data[$field]))
                $errors[$field] = ucfirst(str_replace('_',' ',$field)).' must be a valid Philippine mobile number.';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-Za-z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/i',$data['email']))
            $errors['email'] = 'Email must be valid and end with @gmail.com or @adzu.edu.ph.';

        if (!empty($data['fb_messenger'])) {
            $fb = $data['fb_messenger'];
            $host = strtolower(parse_url($fb, PHP_URL_HOST) ?? '');
            if (!filter_var($fb,FILTER_VALIDATE_URL) || strpos($host,'facebook.com')===false)
                $errors['fb_messenger'] = 'FB/Messenger must be a valid Facebook link.';
        }

        $district = trim($data['district'] ?? '');
        $barangay = trim($data['barangay'] ?? '');
        if (empty($district)) $errors['district'] = 'No district selected.';
        if (empty($barangay)) $errors['barangay'] = 'No barangay selected.';
        elseif (!empty($district)) {
            $exists = DB::table('locations')->where('barangay',$barangay)->where('district_id',$district)->exists();
            if (!$exists) $errors['barangay'] = "Barangay \"$barangay\" not found in District \"$district\".";
        }

        // ✅ Validate class_schedule (required + optional format check)
        $classSchedule = trim($data['class_schedule'] ?? '');
        if ($classSchedule === '') {
            $errors['class_schedule'] = 'Class schedule is required.';
        } elseif (!preg_match('/^[A-Za-z0-9\s,:-]+$/', $classSchedule)) { // letters, numbers, spaces, commas, colon, dash
            $errors['class_schedule'] = 'Class schedule contains invalid characters.';
        }

        return empty($errors) ? null : $errors;
    }

    public function updateVolunteerEntry(Request $request, $index, $type)
    {
        $entries = session($type . 'Entries', []);
        if (!isset($entries[$index])) {
            return back()->with('error', '⚠️ Entry not found.');
        }

        $entry = $entries[$index];
        $input = array_map('trim', $request->all());

        // Normalize contact numbers and ID
        foreach (['contact_number', 'emergency_contact'] as $field) {
            if (!empty($input[$field])) {
                $input[$field] = preg_replace('/[^\d+]/', '', $input[$field]);
            }
        }
        if (!empty($input['id_number'])) {
            $input['id_number'] = strtoupper($input['id_number']);
        }

        // Validation rules
        $rules = [
            'full_name' => ['required','regex:/^[A-Za-zÑñ\s\.\'-]+$/u','max:255'],
            'id_number' => ['required','regex:/^\d{6,7}$/'],
            'course' => 'required|string|max:100',
            'year_level' => ['required','in:1,2,3,4'],
            'contact_number' => ['required','regex:/^(09\d{9}|\+639\d{9})$/'],
            'emergency_contact' => ['required','regex:/^(09\d{9}|\+639\d{9})$/'],
            'email' => ['required','email','regex:/^[A-Za-z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/i'],
            'fb_messenger' => ['nullable'],
            'barangay' => ['required'],
            'district' => ['required'],
            'class_schedule' => ['required','string','regex:/^[\w\s,:()\.\-\/]+$/']
        ];

        $messages = [
            'year_level.in' => 'Year must be 1, 2, 3, or 4.',
            'district.required' => 'No district selected.',
            'barangay.required' => 'No barangay selected.',
            'class_schedule.required' => 'Class schedule is required.',
            'class_schedule.regex' => 'Class schedule contains invalid characters.',
        ];

        $validator = \Validator::make($input, $rules, $messages);
        $errors = $validator->fails() ? $validator->errors()->toArray() : [];

        // FB/Messenger validation
        if (!empty($input['fb_messenger'])) {
            $fb = $input['fb_messenger'];
            if (!filter_var($fb, FILTER_VALIDATE_URL) || stripos(parse_url($fb, PHP_URL_HOST) ?: '', 'facebook.com') === false) {
                $errors['fb_messenger'] = ['FB/Messenger must be a valid Facebook link'];
            }
        }

        // Barangay + District validation
        if (!empty($input['barangay'])) {
            $districtId = $input['district_id'] ?? null;
            if (!$districtId) {
                $errors['district'] = ['No district selected.'];
            } else {
                $exists = DB::table('locations')
                    ->where('barangay', $input['barangay'])
                    ->where('district_id', $districtId)
                    ->exists();
                if (!$exists) {
                    $errors['barangay'] = ["Barangay \"{$input['barangay']}\" and District ID \"{$districtId}\" not found."];
                }
            }
        }

        // Update session entries
        $updatedFields = [];
        foreach ($input as $field => $value) {
            if (!isset($errors[$field])) {
                $entries[$index][$field] = $value;
                $updatedFields[$field] = $value;
            }
        }
        $entries[$index]['errors'] = $errors;
        session([$type . 'Entries' => $entries]);

        // Update DB if volunteer exists
        $volunteerId = $entries[$index]['volunteer_id'] ?? null;
        if ($volunteerId && !empty($updatedFields)) {
            $volunteer = VolunteerProfile::find($volunteerId);
            if ($volunteer) {
                $volunteer->update(array_merge($updatedFields, ['status' => 'active']));
            }
        }

        // Log changes
        $adminId = Auth::guard('admin')->id();
        $labels = [
            'full_name'=>'Full Name','id_number'=>'School ID','course'=>'Course','year_level'=>'Year',
            'contact_number'=>'Contact #','emergency_contact'=>'Emergency #','email'=>'Email',
            'fb_messenger'=>'FB/Messenger','barangay'=>'Barangay','district'=>'District',
            'class_schedule'=>'Class Schedule'
        ];

        if ($adminId && !empty($updatedFields)) {
            $fieldDetails = [];
            foreach ($updatedFields as $field => $value) {
                if (isset($labels[$field])) $fieldDetails[] = "{$labels[$field]}='{$value}'";
            }
            $fullName = $updatedFields['full_name'] ?? $entry['full_name'] ?? 'Unknown';
            $this->logFact('Update Entry', $adminId, 'Volunteer Import', $volunteerId ?? null, 'Updated', "Updated entry #".($index+1)." '{$fullName}': ".implode(', ', $fieldDetails).".");
        }

        // Build flash message
        $rowNumber = $index + 1;
        $message = "<strong>Updated Volunteer Entry (Row #{$rowNumber})</strong><br>";
        $changesMade = false;

        foreach ($labels as $field => $label) {
            if (isset($updatedFields[$field])) {
                $changesMade = true;
                $oldValue = $entry[$field] ?? '';
                $newValue = $updatedFields[$field];
                $message .= ($oldValue !== $newValue)
                    ? "✅ <strong>{$label}:</strong> <span style='color:#007bff;'>{$newValue}</span><br>"
                    : "✅ <strong>{$label}:</strong> {$newValue}<br>";
            }
        }

        if (!empty($errors)) {
            $changesMade = true;
            foreach ($errors as $field => $msgs) {
                if (isset($labels[$field])) {
                    $val = $input[$field] ?? '';
                    $message .= "⚠️ <strong>{$labels[$field]}:</strong> {$val} (".implode(', ', (array)$msgs).")<br>";
                }
            }
        }

        if (!$changesMade) $message .= "ℹ️ No changes were made.<br>";

        $flashType = !empty($updatedFields) ? 'success' : 'error';

        // ✅ Pass both table (valid/invalid) and index back to the view
        return redirect()->route('volunteer.import.index')
            ->with($flashType, $message)
            ->with('last_updated_table', $type)
            ->with('last_updated_index', $index);
    }

   /**
 * Move from Invalid -> Valid
 */
public function moveInvalidToValid(Request $request)
{
    $invalid = session('invalidEntries', []);
    $valid = session('validEntries', []);
    $movedEntries = [];
    $skippedEntries = [];
    $adminId = auth()->guard('admin')->id();

    $selectedIndices = $request->input('selected_invalid', []); // array of indexes in $invalid

    if (!empty($selectedIndices)) {
        foreach ($selectedIndices as $index) {
            if (!isset($invalid[$index])) continue;

            $entry = $invalid[$index];

            // Skip if it has errors
            if (!empty($entry['errors'] ?? [])) {
                $skippedEntries[] = [
                    'name' => $entry['full_name'] ?? 'N/A',
                    'index' => $index
                ];
                continue;
            }

            // Move entry
            unset($entry['errors'], $entry['error_message']);
            $valid[] = $entry;
            $movedEntries[] = [
                'name' => $entry['full_name'] ?? 'N/A',
                'index' => $index
            ];

            unset($invalid[$index]);

            // Log the move
            $this->logFact(
                'Move to Verified',
                $adminId,
                'Volunteer Import',
                $entry['volunteer_id'] ?? $entry['row_number'] ?? null,
                'Moved',
                "Moved Volunteer Entry #".($index+1)." {$entry['full_name']} from invalid to valid."
            );
        }

        // Reindex arrays
        $invalid = array_values($invalid);
        $valid = array_values($valid);

        session([
            'invalidEntries' => $invalid,
            'validEntries' => $valid,
            'last_updated_table' => 'valid',
            'last_updated_index' => count($valid) - 1
        ]);
    }

    // Build message
    $messageParts = [];
    if ($movedEntries) {
        $movedList = array_map(fn($e) => "Moved Volunteer Entry #".($e['index']+1)." {$e['name']}", $movedEntries);
        $messageParts[] = "✅ " . implode(', ', $movedList) . " to valid.";
    }
    if ($skippedEntries) {
        $skippedList = array_map(fn($e) => $e['name'], $skippedEntries);
        $messageParts[] = "⚠️ Could not move: " . implode(', ', $skippedList) . ".";
    }
    if (!$movedEntries && !$skippedEntries) {
        $messageParts[] = "ℹ️ No invalid entries selected to move.";
    }

    return back()
        ->withFragment('valid-entries-table')
        ->with('success', implode(' ', $messageParts));
}

/**
 * Move from Valid -> Invalid
 */
public function moveValidToInvalid(Request $request, $index)
{
    $valid = session('validEntries', []);
    $invalid = session('invalidEntries', []);
    $adminId = auth()->guard('admin')->id();

    if (!isset($valid[$index])) {
        return back()
            ->withFragment('invalid-entries-table')
            ->with('success', "ℹ️ No valid entry selected to move.");
    }

    $entry = $valid[$index];
    unset($valid[$index]);

    // Restore to original index if available
    if (isset($entry['original_index'])) {
        $invalid[$entry['original_index']] = $entry;
    } else {
        $invalid[] = $entry;
    }

    // Sort by index
    ksort($invalid);
    $invalid = array_values($invalid);

    session([
        'validEntries' => array_values($valid),
        'invalidEntries' => $invalid,
        'last_updated_table' => 'invalid',
        'last_updated_index' => isset($entry['original_index']) ? $entry['original_index'] : count($invalid) - 1,
    ]);

    // Log action
    $this->logFact(
        'Move to Invalid',
        $adminId,
        'Volunteer Import',
        $entry['volunteer_id'] ?? $entry['row_number'] ?? null,
        'Moved Back',
        "Moved Volunteer Entry #".($index+1)." {$entry['full_name']} from valid to invalid."
    );

    return back()
        ->withFragment('invalid-entries-table')
        ->with('success', "⚠️ Moved Volunteer Entry #".($index+1)." {$entry['full_name']} back to invalid.");
}


    /**
     * Delete Entries
     */
    public function deleteEntries(Request $request)
    {
        $tableType = $request->input('table_type'); // invalid / valid / logs
        $selected = $request->input('selected', []);
        $adminId = auth()->guard('admin')->id();

        if (empty($selected)) {
            return back()->with('error', 'ℹ️ No entries selected for deletion.');
        }

        $deletedData = [];

        switch ($tableType) {
            case 'invalid':
            case 'valid':
                $entries = session($tableType . 'Entries', []);
                foreach ($selected as $index) {
                    if (isset($entries[$index])) {
                        $deletedData[$index] = $entries[$index];
                        unset($entries[$index]);

                        $volunteerId = $deletedData[$index]['volunteer_id'] ?? $deletedData[$index]['row_number'] ?? null;
                        $name = $deletedData[$index]['full_name'] ?? 'No Name';

                        $this->logFact(
                            'Delete Entry',
                            $adminId,
                            'Volunteer Import',
                            $volunteerId,
                            'Deleted',
                            "Deleted Volunteer Entry #".($index+1)." {$name}" 
                        );
                    }
                }
                session([$tableType . 'Entries' => array_values($entries)]);
                break;

            case 'logs':
                $deletedEntries = ImportLog::whereIn('import_id', $selected)->get();
                foreach ($deletedEntries as $entry) {
                    $deletedData[] = $entry->toArray();
                    $name = $entry->file_name ?? 'No Name';

                    $this->logFact(
                        'Delete Import Log',
                        $adminId,
                        'Volunteer Import',
                        $entry->import_id,
                        'Deleted',
                        "Deleted Import Log '{$name}' (ID {$entry->import_id})"
                    );
                }
                ImportLog::whereIn('import_id', $selected)->delete();
                break;

            default:
                return back()->with('error', '⚠️ Invalid table type.');
        }

        if (!empty($deletedData)) {
            session(['deletedEntriesUndo' => [
                'tableType' => $tableType,
                'data' => $deletedData,
                'timestamp' => now()
            ]]);

            $deletedList = [];
            foreach ($deletedData as $index => $item) {
                $name = $item['full_name'] ?? ($item['file_name'] ?? 'No Name');
                $deletedList[] = "Volunteer Entry #".($index+1)." ".$name;
            }

            $message = "<div style='display:flex; flex-wrap:wrap; gap:5px; align-items:center;' >
                            ✅ Deleted Entry: " . implode(', ', $deletedList) . "
                            <a href='" . route('volunteer.import.undo-delete') . "' 
                            style='margin-left:10px; padding:4px 10px; font-size:0.9em; background:#007bff; color:white; text-decoration:none; border-radius:4px; cursor:pointer;' >
                            Undo
                            </a>
                        </div>";
        } else {
            $message = "ℹ️ No entries were deleted.";
        }

        // ✅ Pass table and selected indices to session for remembering
        return back()->with('success', $message)
                    ->with('last_updated_table', $tableType)
                    ->with('last_updated_indices', $selected);
    }


    /**
     * Undo Deleted Entries
     */
    public function undoDelete(Request $request)
    {
        $deleted = session('deletedEntriesUndo');
        $adminId = auth()->guard('admin')->id();

        if (!$deleted || empty($deleted['data']) || !isset($deleted['tableType'])) {
            return back()->with('error', 'ℹ️ Nothing to undo.');
        }

        $tableType = $deleted['tableType'];
        $data = $deleted['data'];

        switch ($tableType) {
            case 'invalid':
            case 'valid':
                $entries = session($tableType . 'Entries', []);
                foreach ($data as $index => $item) {
                    $entries[$index] = $item;

                    $volunteerId = $item['volunteer_id'] ?? $item['row_number'] ?? null;
                    $name = $item['full_name'] ?? 'No Name';

                    $this->logFact(
                        'Restore Entry',
                        $adminId,
                        'Volunteer Import',
                        $volunteerId,
                        'Restored',
                        "Restored Volunteer Entry #".($index+1)." {$name}"
                    );
                }
                session([$tableType . 'Entries' => array_values($entries)]);
                break;

            case 'logs':
                foreach ($data as $index => $item) {
                    if (!ImportLog::where('import_id', $item['import_id'])->exists()) {
                        ImportLog::create($item);

                        $entityId = $item['import_id'] ?? null;
                        $name = $item['file_name'] ?? 'No Name';

                        $this->logFact(
                            'Restore Import Log',
                            $adminId,
                            'Volunteer Import',
                            $entityId,
                            'Restored',
                            "Restored Import Log '{$name}' (ID {$entityId})"
                        );
                    }
                }
                break;

            default:
                return back()->with('error', '⚠️ Invalid table type for undo.');
        }

        session()->forget('deletedEntriesUndo');

        $restoredList = [];
        foreach ($data as $index => $item) {
            $name = $item['full_name'] ?? ($item['file_name'] ?? 'No Name');
            $restoredList[] = "Volunteer Entry #".($index+1)." ".$name;
        }

        $message = "<div style='display:flex; flex-wrap:wrap; gap:5px; align-items:center;' >
                        ✅ Restored Entry: " . implode(', ', $restoredList) . "
                    </div>";

        // ✅ Pass table and indices back so frontend knows which rows were restored
        return back()->with('success', $message)
                    ->with('last_updated_table', $tableType)
                    ->with('last_updated_indices', array_keys($data));
    }

    /**
     * Validate and Save Selected Valid Entries
     */
    public function validateAndSave(Request $request)
    {
        // --- Debug logs (helpful while testing) ---
        Log::info('DEBUG_SUBMIT: raw selected_valid input', ['raw' => $request->input('selected_valid', [])]);
        Log::info('DEBUG_SUBMIT: session validEntries count', ['count' => count(session('validEntries', []))]);
        Log::info('DEBUG_SUBMIT: session invalidEntries count', ['count' => count(session('invalidEntries', []))]);

        // --- Normalize + deduplicate selected indexes (defensive) ---
        $selectedIndexes = $request->input('selected_valid', []);
        $selectedIndexes = array_values(array_unique(array_map('intval', (array)$selectedIndexes)));
        Log::info('DEBUG_SUBMIT: selected_valid normalized', ['selected' => $selectedIndexes]);

        $validEntries = session('validEntries', []);
        $invalidEntries = session('invalidEntries', []);
        $admin = Auth::guard('admin')->user();
        $fileName = session('uploaded_file_name', 'N/A');

        if (!$admin) {
            $message = 'Admin not authenticated.';
            return $request->ajax()
                ? response()->json(['error_modal' => $message])
                : back()->with('error_modal', $message);
        }

        $adminId = $admin->admin_id;

        // Block save if there are still invalid entries
        if (!empty($invalidEntries)) {
            $invalidRows = implode(', ', array_column($invalidEntries, 'row_number'));
            $this->logFact(
                'Failed Import',
                $adminId,
                'Volunteer Import',
                null,
                'Failed',
                [
                    'invalid_rows' => $invalidRows,
                    'invalid_count' => count($invalidEntries),
                    'reason' => 'Invalid entries prevent import'
                ]
            );

            $message = "❌ Cannot upload. Invalid entries found in row(s): <strong>{$invalidRows}</strong>. Please fix them first.";
            return $request->ajax()
                ? response()->json(['error_modal' => $message])
                : back()->with('error_modal', $message);
        }

        // After dedupe, if nothing left, bail
        if (empty($selectedIndexes)) {
            $message = '❌ No verified entries selected to save.';
            return $request->ajax()
                ? response()->json(['error_modal' => $message])
                : back()->with('error_modal', $message);
        }

        // Build entriesToSave from validEntries
        $entriesToSave = [];
        foreach ($selectedIndexes as $index) {
            if (!isset($validEntries[$index])) {
                Log::warning('validateAndSave: selected index not found in validEntries', ['index' => $index]);
                continue;
            }

            $entry = $validEntries[$index];

            // run your row-level validation method
            $errors = $this->validateRow($entry);

            if ($errors) {
                $rowNumber = $entry['row_number'] ?? $index;
                $message = "❌ Validation failed for row <strong>{$rowNumber}</strong>. No entries were saved.";
                return $request->ajax()
                    ? response()->json(['error_modal' => $message])
                    : back()->with('error_modal', $message);
            }

            $entriesToSave[] = [
                'index' => $index,
                'data'  => $entry
            ];
        }

        if (empty($entriesToSave)) {
            $message = '❌ No valid entries found to save (they may have been removed).';
            return $request->ajax()
                ? response()->json(['error_modal' => $message])
                : back()->with('error_modal', $message);
        }

        try {
            $savedEntries = [];

            DB::transaction(function () use ($entriesToSave, $adminId, &$savedEntries) {
                // Create import log
                $importLog = ImportLog::create([
                    'file_name'       => session('uploaded_file_name') ?? 'CSV Upload',
                    'admin_id'        => $adminId,
                    'total_records'   => count($entriesToSave),
                    'valid_count'     => count($entriesToSave),
                    'invalid_count'   => 0,
                    'duplicate_count' => 0,
                    'status'          => 'Completed',
                    'remarks'         => "Successfully imported " . count($entriesToSave) . " row(s) by Admin ID: {$adminId}.",
                ]);

                foreach ($entriesToSave as $entryData) {
                    $entry = $entryData['data'];
                    $index = $entryData['index'];
                    $idNumber = $entry['id_number'] ?? null;

                    // --- Map course_name to course_id ---
                    $courseName = preg_replace('/\s+/', ' ', trim($entry['course'] ?? ''));
                    $courseId = null;
                    if ($courseName) {
                        $courseId = \App\Models\Course::whereRaw('LOWER(TRIM(course_name)) = ?', [strtolower($courseName)])
                            ->value('course_id');
                    }

                    // --- Map location_id and auto-fill barangay/district ---
                    $barangay = $entry['barangay'] ?? null;
                    $locationId = $barangay ? \App\Models\Location::where('barangay', $barangay)->value('location_id') : null;

                    $location = $locationId ? \App\Models\Location::find($locationId) : null;
                    $resolvedBarangay = $location->barangay ?? null;
                    $resolvedDistrict = $location->district_id ?? null; // <-- numeric district_id


                    // DEBUG: check course and location lookup
                    Log::info('DEBUG_COURSE_LOOKUP', [
                        'entry_index' => $index,
                        'entry_course_name' => $courseName,
                        'matched_course_id' => $courseId,
                        'all_courses' => \App\Models\Course::pluck('course_name')->toArray()
                    ]);

                    Log::info('DEBUG_LOCATION_LOOKUP', [
                        'entry_index' => $index,
                        'entry_barangay' => $barangay,
                        'location_id' => $locationId,
                        'resolved_barangay' => $resolvedBarangay,
                        'resolved_district' => $resolvedDistrict
                    ]);

                    // Save to volunteer_profile
                    $volunteer = \App\Models\VolunteerProfile::create([
                        'import_id'         => $importLog->import_id,
                        'full_name'         => $entry['full_name'] ?? null,
                        'id_number'         => $idNumber ?? 'TEMP-' . uniqid(),
                        'course_id'         => $courseId,
                        'year_level'        => $entry['year_level'] ?? null,
                        'contact_number'    => $entry['contact_number'] ?? null,
                        'emergency_contact' => $entry['emergency_contact'] ?? null,
                        'email'             => $entry['email'] ?? null,
                        'fb_messenger'      => $entry['fb_messenger'] ?? null,
                        'location_id'       => $locationId,
                        'barangay'          => $resolvedBarangay,
                        'district'          => $resolvedDistrict,
                        'class_schedule'    => $entry['class_schedule'] ?? null,
                        'status'            => 'active',
                    ]);

                    // log saved entry
                    $savedEntries[] = [
                        'index'     => $index,
                        'name'      => $entry['full_name'] ?? 'N/A',
                        'entity_id' => $volunteer->volunteer_id
                    ];

                    $this->logFact(
                        'Import Verified',
                        $adminId,
                        'VolunteerProfile',
                        $volunteer->volunteer_id,
                        'Imported',
                        "Saved Volunteer Entry #".($index+1)." {$entry['full_name']}"
                    );
                }
            });

            // Clear sessions
            session()->forget([
                'validEntries',
                'invalidEntries',
                'uploaded_file_name',
                'uploaded_file_path',
                'csv_imported',
                'import_log_id'
            ]);

            $savedList = array_map(fn($e) => "Saved Volunteer Entry #".($e['index']+1)." {$e['name']}", $savedEntries);
            $message = "✅ Successfully saved " . count($savedEntries) . " entries: " . implode(', ', $savedList) . " from file '<strong>{$fileName}</strong>' on " . now()->format('M d, Y h:i A') . ".";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Import failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $message = "❌ Import failed: {$e->getMessage()}";
            return $request->ajax()
                ? response()->json(['error_modal' => $message])
                : back()->with('error_modal', $message);
        }
    }



    /**
     * Reset Import Preview / Remove CSV Import
     */
    public function resetImports(Request $request)
    {
        $validCount = session()->has('validEntries') ? count(session('validEntries')) : 0;
        $invalidCount = session()->has('invalidEntries') ? count(session('invalidEntries')) : 0;
        $totalCleared = $validCount + $invalidCount;
        $fileName = session('uploaded_file_name', 'N/A');
        $originalImportId = session('import_log_id');
        $currentAdminId = auth()->guard('admin')->id() ?? null;
        $formattedTime = now()->format('M d, Y h:i A'); // concise format

        // Cancel original import log
        if ($originalImportId) {
            $originalLog = ImportLog::find($originalImportId);
            if ($originalLog) {
                $originalLog->update([
                    'admin_id'      => $originalLog->admin_id ?? $currentAdminId,
                    'total_records' => $originalLog->total_records ?: $totalCleared,
                    'valid_count'   => $originalLog->valid_count ?: $validCount,
                    'invalid_count' => $originalLog->invalid_count ?: $invalidCount,
                    'status'        => 'Cancelled',
                    'remarks'       => "Reset import preview cleared {$totalCleared} row(s) on {$formattedTime} by Admin ID: {$currentAdminId}",
                ]);

                $this->logFact(
                    'Import Cancelled',
                    $originalLog->admin_id,
                    'ImportLog',
                    $originalLog->import_id,
                    'Cancelled',
                    "Original import reset. {$totalCleared} row(s) cleared."
                );
            }
        }

        // Create new reset import log
        $resetLog = ImportLog::create([
            'file_name'       => $fileName,
            'admin_id'        => $currentAdminId,
            'total_records'   => $totalCleared,
            'valid_count'     => $validCount,
            'invalid_count'   => $invalidCount,
            'duplicate_count' => 0,
            'status'          => 'Reset',
            'remarks'         => "Reset import preview cleared {$totalCleared} row(s) on {$formattedTime} by Admin ID: {$currentAdminId}",
        ]);

        $this->logFact(
            'Reset Import Preview',
            $currentAdminId,
            'ImportLog',
            $resetLog->import_id,
            'Success',
            "Import preview for file '{$resetLog->file_name}' was reset successfully. Total rows cleared: {$totalCleared}"
        );

        // Clear relevant sessions
        session()->forget([
            'validEntries', 
            'invalidEntries', 
            'uploaded_file_name',
            'uploaded_file_path', 
            'csv_imported', 
            'import_log_id', 
            'lastUsedTable'
        ]);
        session()->flash('clearLastUsedTable', true);

        // Flash human-readable message for UI
        $message = "♻️ Import preview reset successfully by Admin ID: {$currentAdminId} on {$formattedTime}.<br>" .
                "Cleared rows: <strong>{$totalCleared}</strong> (Valid: <strong>{$validCount}</strong>, Invalid: <strong>{$invalidCount}</strong>).<br>" .
                "Reset log created: <span style='color:#B2000C;'>ID: {$resetLog->import_id}</span>.";

        return back()->with('success', $message);
    }

    public function checkDuplicates(Request $request)
{
    $ids = $request->input('ids', []);

    \Log::info('🟦 checkDuplicates() - Received IDs:', $ids);

    if (empty($ids)) {
        \Log::warning('🟨 checkDuplicates() - No IDs received.');
        return response()->json([
            'duplicates' => [],
            'message' => 'No IDs provided for duplicate check.'
        ]);
    }

    \Log::info('🟦 checkDuplicates() - Querying DB for these id_numbers:', $ids);

    // Find existing duplicates in the DB
    $existing = VolunteerProfile::whereIn('id_number', $ids)
                                ->pluck('id_number')
                                ->toArray();

    \Log::info('🟥 checkDuplicates() - Existing duplicates in DB:', $existing);

    if (!empty($existing)) {
        $message = "⚠️ Cannot submit. The following ID(s) already exist in the database: <strong>"
                   . implode(', ', $existing) . "</strong>.";
        return response()->json([
            'duplicates' => $existing,
            'message' => $message
        ]);
    }

    // No duplicates found
    return response()->json([
        'duplicates' => [],
        'message' => null
    ]);
}



    public function updateSchedule(Request $request, $id)
    {
        try {
            $scheduleString = $request->input('schedule');
            $type = $request->input('type', 'valid'); // 'valid' or 'invalid'

            if (!$scheduleString || !is_string($scheduleString)) {
                return redirect()->back()->with('error', 'Invalid schedule data.');
            }

            // Choose session array based on type
            $entries = session($type . 'Entries', []);
            if (!isset($entries[$id])) {
                return redirect()->back()->with('error', 'Entry not found in session.');
            }

            $entry = $entries[$id];
            $oldSchedule = $entry['class_schedule'] ?? '';

            $days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

            $normalize = function($schedule) use ($days) {
                $result = [];
                foreach ($days as $day) {
                    if (preg_match("/{$day}:\s*(.*?)(?=(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$))/is", $schedule, $match)) {
                        $raw = trim($match[1]);
                        $raw = str_ireplace('No Class', '', $raw);
                        $raw = preg_replace('/\s+/', ' ', $raw);
                        $result[$day] = $raw ? explode(' ', $raw) : [];
                    } else {
                        $result[$day] = [];
                    }
                }
                return $result;
            };

            $oldParts = $normalize($oldSchedule);
            $newPartsRaw = $normalize($scheduleString);

            // Normalize new values to HH:MM-HH:MM
            $newParts = [];
            $reformattedCells = [];
            foreach ($days as $day) {
                $newParts[$day] = [];
                foreach ($newPartsRaw[$day] as $idx => $val) {
                    $parts = explode('-', $val);
                    if (count($parts) === 2) {
                        $parts = array_map(fn($p) => preg_match('/^\d{1,2}$/', $p) ? $p.':00' : $p, $parts);
                        $norm = implode('-', $parts);
                    } else {
                        $norm = $val;
                    }
                    $newParts[$day][$idx] = $norm;

                    $oldVal = $oldParts[$day][$idx] ?? null;
                    if ($oldVal && $oldVal !== $norm && !in_array($norm, $oldParts[$day] ?? [])) {
                        $reformattedCells[$day][] = ['from' => $oldVal, 'to' => $norm];
                    }
                }
            }

            $dayChanges = [];
            $changesMade = false;

            foreach ($days as $day) {
                $addedDay = array_diff($newParts[$day] ?? [], $oldParts[$day] ?? []);
                $removedDay = array_diff($oldParts[$day] ?? [], $newParts[$day] ?? []);
                $dayChanges[$day] = ['added' => $addedDay, 'removed' => $removedDay];

                if (count($addedDay) > 0 || count($removedDay) > 0 || !empty($reformattedCells[$day] ?? [])) {
                    $changesMade = true;
                }
            }

            // Update session
            $entries[$id]['class_schedule'] = trim($scheduleString);
            session([$type . 'Entries' => $entries]);

            // Build flash message
            $rowNumber = $entry['row_number'] ?? ($id + 1);

            if (!$changesMade) {
                $message = "<strong>Row #{$rowNumber} for {$entry['full_name']}</strong><br>ℹ️ No changes made";
            } else {
                $message = "<strong>Updated Class Schedule (Row #{$rowNumber}) for {$entry['full_name']}</strong><br>";
                foreach ($days as $day) {
                    $added = $dayChanges[$day]['added'];
                    $removed = $dayChanges[$day]['removed'];
                    $reformatted = $reformattedCells[$day] ?? [];

                    $parts = [];
                    if (!empty($added)) $parts[] = "✅ <span style='color:#007bff;'>Added: ".implode(', ', $added)."</span>";
                    if (!empty($removed)) $parts[] = "⚠️ <span style='color:red;'>Removed: ".implode(', ', $removed)."</span>";
                    if (!empty($reformatted)) {
                        $parts[] = "ℹ️ <span style='color:orange;'>Reformatted: " . implode(', ', array_map(fn($c) => "{$c['from']} → {$c['to']}", $reformatted)) . "</span>";
                    }

                    $message .= "<strong>$day:</strong> " . ($parts ? implode(' | ', $parts) : "ℹ️ No change") . "<br>";
                }
            }

            // Log facts
            $adminId = auth()->guard('admin')->id() ?? null;
            $factMessage = ($changesMade ? "Updated" : "No changes made") . " Class Schedule for Volunteer #".($id+1)." {$entry['full_name']}. ";
            foreach ($days as $day) {
                $added = $dayChanges[$day]['added'];
                $removed = $dayChanges[$day]['removed'];
                $reformatted = $reformattedCells[$day] ?? [];
                $parts = [];
                if (!empty($added)) $parts[] = "Added: ".implode(', ', $added);
                if (!empty($removed)) $parts[] = "Removed: ".implode(', ', $removed);
                foreach ($reformatted as $cell) $parts[] = "Reformatted: {$cell['from']} → {$cell['to']}";
                if (!empty($parts)) $factMessage .= "$day [".implode('; ', $parts)."]; ";
            }

            $this->logFact(
                ($changesMade ? 'Update Schedule' : 'No Change'),
                $adminId,
                'Volunteer Import',
                $entry['volunteer_id'] ?? $entry['row_number'] ?? null,
                ($changesMade ? 'Updated' : 'No Change'),
                $factMessage
            );

            return redirect()->back()
                ->with('success', $message)
                ->with('last_updated_table', $type)
                ->with('last_updated_index', $id);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    
    /**
     * Centralized FactLog helper with auto entity type inference
    */
    private function logFact(
        string $factType,
        $adminId = null,
        $entity = null,
        ?int $entityId = null,
        ?string $action = null,
        $details = null,
        ?int $importId = null
        ): FactLog {
        // Resolve current admin safely
        $admin = Auth::guard('admin')->user();
        $adminId = is_numeric($adminId) ? (int) $adminId : ($admin->admin_id ?? null);

        // Encode details safely
        $encodedDetails = is_array($details) || is_object($details)
            ? json_encode($details, JSON_UNESCAPED_UNICODE)
            : (string) $details;

        // Infer entity_type
        if (is_object($entity)) {
            // If a model instance is passed, use class basename
            $entityType = class_basename($entity);
            $entityId = $entityId ?? ($entity->id ?? null);
        } elseif (is_string($entity)) {
            $entityType = $entity;
        } else {
            $entityType = 'Unknown';
        }

        return FactLog::create([
            'fact_type'   => $factType,
            'admin_id'    => $adminId,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'action'      => $action,
            'details'     => $encodedDetails,
            'import_id'   => $importId,
        ]);
    }
}
