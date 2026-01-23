<?php

namespace App\Http\Controllers;

use App\Models\VolunteerProfile;
use App\Models\Course;
use App\Models\Location;
use App\Models\EventAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\FactLog;
use App\Services\FactLogger;

class VolunteerProfileController extends Controller
{
    protected FactLogger $factLogger;

    public function __construct(FactLogger $factLogger)
    {
        $this->factLogger = $factLogger;
    }

    // Fact log helper
    private function logFact(
        string $type,
        ?string $action = null,
        $entity = null,
        ?int $entityId = null,
        $details = null,
        ?int $adminId = null
    ): FactLog {
        return $this->factLogger->log(
            $type,
            $action,
            $entity,
            $entityId,
            $details,
            is_numeric($adminId) ? (int) $adminId : null
        );
    }

    // Show volunteer profile
    public function show($id)
    {
        $volunteer = VolunteerProfile::with(['course', 'location'])->findOrFail($id);

        $courses = Course::orderBy('course_name')->get();

        $locations = Location::query()
            ->select('barangay', 'district_id')
            ->whereNotNull('barangay')
            ->where('barangay', '!=', '')
            ->whereNotNull('district_id')
            ->distinct()
            ->orderBy('barangay')
            ->get();

        $barangays = Location::query()
            ->select('barangay')
            ->whereNotNull('barangay')
            ->where('barangay', '!=', '')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay')
            ->values();

        $districts = Location::query()
            ->select('district_id')
            ->whereNotNull('district_id')
            ->distinct()
            ->orderBy('district_id')
            ->pluck('district_id')
            ->values();

        $eventHistory = EventAttendance::query()
            ->where('volunteer_id', $volunteer->volunteer_id)
            ->whereNotNull('event_id')
            ->with([
                'event' => function ($q) {
                    $q->select([
                        'event_id',
                        'event_code',
                        'title',
                        'venue',
                        'start_datetime',
                        'end_datetime',
                        'status',
                    ]);
                }
            ])
            ->orderByDesc('attendance_time')
            ->orderByDesc('attendance_id')
            ->paginate(8)
            ->withQueryString();

        $eventHistoryCount = EventAttendance::query()
            ->where('volunteer_id', $volunteer->volunteer_id)
            ->whereNotNull('event_id')
            ->distinct()
            ->count('event_id');

        $latestImportBatch = EventAttendance::query()
            ->where('volunteer_id', $volunteer->volunteer_id)
            ->whereNotNull('import_batch')
            ->orderByDesc('attendance_time')
            ->orderByDesc('attendance_id')
            ->value('import_batch');

        return view('volunteer_profile.volunteer_profile', compact(
            'volunteer',
            'courses',
            'locations',
            'barangays',
            'districts',
            'eventHistory',
            'eventHistoryCount',
            'latestImportBatch'
        ));
    }

    // AJAX unique checks for edit modal
    public function checkUnique(Request $request)
    {
        $volunteerId = $request->input('volunteer_id');

        $email = trim((string) $request->input('email'));
        $fb    = trim((string) $request->input('fb_messenger'));
        $idNo  = trim((string) $request->input('id_number'));

        $out = [
            'email'        => true,
            'fb_messenger' => true,
            'id_number'    => true,
        ];

        if ($email !== '') {
            $out['email'] = !VolunteerProfile::where('email', $email)
                ->when($volunteerId, fn ($q) => $q->where('volunteer_id', '!=', $volunteerId))
                ->exists();
        }

        if ($fb !== '') {
            $out['fb_messenger'] = !VolunteerProfile::where('fb_messenger', $fb)
                ->when($volunteerId, fn ($q) => $q->where('volunteer_id', '!=', $volunteerId))
                ->exists();
        }

        if ($idNo !== '') {
            $out['id_number'] = !VolunteerProfile::where('id_number', $idNo)
                ->when($volunteerId, fn ($q) => $q->where('volunteer_id', '!=', $volunteerId))
                ->exists();
        }

        return response()->json([
            'ok'     => true,
            'unique' => $out,
        ]);
    }

    // Update profile
    public function update(Request $request, $id)
    {
        $request->validate([
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'full_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-zÑñ\s\.\'-]+$/'
            ],
            'id_number' => [
                'required',
                'regex:/^\d{6,7}$/'
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'regex:/^[A-Za-z0-9._%+\-]+@(adzu\.edu\.ph|gmail\.com)$/'
            ],
            'contact_number' => [
                'required',
                'regex:/^(09\d{9}|\+639\d{9})$/'
            ],
            'emergency_contact' => [
                'required',
                'regex:/^(09\d{9}|\+639\d{9})$/'
            ],
            'fb_messenger' => [
                'required',
                'string',
                'max:255'
            ],
            'course_id' => 'required|integer|exists:courses,course_id',
            'year_level' => 'required|integer|min:1|max:10',
            'batch_number' => 'required|integer|min:1|max:999',
            'barangay' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'class_schedule' => 'nullable|string',
        ], [
            'full_name.regex' => 'Full name must not contain numbers or special symbols (only letters, spaces, dot, apostrophe, hyphen).',
            'id_number.regex' => 'School ID must be 6–7 digits only.',
            'email.regex' => 'Email must end with @adzu.edu.ph or @gmail.com.',
            'contact_number.regex' => 'Contact number must be 09XXXXXXXXX or +639XXXXXXXXX.',
            'emergency_contact.regex' => 'Emergency number must be 09XXXXXXXXX or +639XXXXXXXXX.',
        ]);

        $volunteer = VolunteerProfile::findOrFail($id);

        $photoChanged = false;

        // ✅ Handle profile picture upload
        if ($request->hasFile('profile_picture')) {

            $photoChanged = true;

            // Delete old local image if it exists
            if (
                $volunteer->profile_picture_path &&
                \Storage::disk('public')->exists($volunteer->profile_picture_path)
            ) {
                \Storage::disk('public')->delete($volunteer->profile_picture_path);
            }

            // Store new image
            $path = $request->file('profile_picture')
                ->store('volunteer_profiles', 'public');

            // Save new path
            $volunteer->profile_picture_path = $path;

            // Clear external URL
            $volunteer->profile_picture_url = null;
        }

        $before = $volunteer->only([
            'full_name',
            'id_number',
            'email',
            'contact_number',
            'emergency_contact',
            'fb_messenger',
            'course_id',
            'year_level',
            'batch_number',
            'barangay',
            'district',
            'status',
            'class_schedule',
        ]);

        $payload = $request->only([
            'full_name',
            'id_number',
            'email',
            'contact_number',
            'emergency_contact',
            'fb_messenger',
            'course_id',
            'year_level',
            'batch_number',
            'barangay',
            'district',
            'status',
            'class_schedule',
        ]);

        $volunteer->fill($payload);
        $volunteer->save();


        $after = $volunteer->only(array_keys($before));

        $labels = [
            'full_name'         => 'Full Name',
            'id_number'         => 'School ID',
            'email'             => 'Email',
            'contact_number'    => 'Contact #',
            'emergency_contact' => 'Emergency #',
            'fb_messenger'      => 'FB/Messenger',
            'course_id'         => 'Course',
            'year_level'        => 'Year Level',
            'batch_number' => 'Batch Number',
            'barangay'          => 'Barangay',
            'district'          => 'District',
            'status'            => 'Status',
            'class_schedule'    => 'Class Schedule',
        ];

        $changed = [];
        foreach ($before as $k => $oldVal) {
            $newVal = $after[$k] ?? null;

            if (in_array($k, ['full_name', 'email', 'fb_messenger', 'barangay', 'district', 'class_schedule'], true)) {
                $oldVal = preg_replace('/\s+/', ' ', trim((string) $oldVal));
                $newVal = preg_replace('/\s+/', ' ', trim((string) $newVal));
            }

            if ((string) $newVal !== (string) $oldVal) {
                $changed[$k] = [
                    'label' => $labels[$k] ?? $k,
                    'from'  => $oldVal,
                    'to'    => $newVal,
                ];
            }
        }

        if ($photoChanged) {
            $changed['profile_picture'] = [
                'label' => 'Profile Picture',
                'from'  => 'Previous photo',
                'to'    => 'Updated photo',
            ];
        }


        $adminId = Auth::guard('admin')->id();
        $nameForLog = $after['full_name'] ?? $before['full_name'] ?? 'Unknown';

        if (!empty($changed)) {
            $fieldDetails = [];
            foreach ($changed as $info) {
                $to = is_null($info['to']) ? '' : (string) $info['to'];
                $fieldDetails[] = "{$info['label']}='{$to}'";
            }

            $summary = "Updated Volunteer Profile — {$nameForLog}: " . implode(', ', $fieldDetails);

            $this->logFact(
                'volunteer_profile.updated',
                'Updated',
                'VolunteerProfile',
                (int) $volunteer->volunteer_id,
                [
                    'summary' => $summary,
                    'data' => [
                        'changed' => $changed,
                    ],
                ],
                is_numeric($adminId) ? (int) $adminId : null
            );
        } else {
            $this->logFact(
                'volunteer_profile.updated',
                'No Change',
                'VolunteerProfile',
                (int) $volunteer->volunteer_id,
                "No changes made — {$nameForLog}",
                is_numeric($adminId) ? (int) $adminId : null
            );
        }

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    // Delete profile
    public function destroy($id)
    {
        $admin = Auth::guard('admin')->user();
        $adminId = $admin?->admin_id;

        $volunteer = VolunteerProfile::findOrFail($id);
        $name = $volunteer->full_name ?? 'Unknown';

        $snapshot = $volunteer->only([
            'volunteer_id',
            'full_name',
            'id_number',
            'email',
            'status',
            'course_id',
            'year_level',
            'batch_number',
            'barangay',
            'district',
            'class_schedule',
            'import_id',
        ]);

        $volunteer->delete();

        $this->logFact(
            'volunteer_profile.deleted',
            'Deleted',
            'VolunteerProfile',
            (int) ($snapshot['volunteer_id'] ?? $id),
            [
                'summary' => "Deleted Volunteer Profile — {$name}",
                'data' => [
                    'snapshot' => $snapshot,
                    'deleted_by' => [
                        'admin_id'   => $adminId,
                        'username'   => $admin?->username,
                        'full_name'  => $admin?->full_name,
                    ],
                ],
            ],
            is_numeric($adminId) ? (int) $adminId : null
        );

        return redirect()->route('volunteers.list')
            ->with('success', 'Volunteer profile deleted.');
    }
    
}
