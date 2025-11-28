<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VolunteerProfile;
use App\Models\Course;
use Illuminate\Support\Facades\Storage;

class VolunteerController extends Controller
{
    /**
     * Store a newly created volunteer in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'id_number' => 'nullable|string|max:255|unique:volunteer_profiles,id_number',
            'year_level' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'contact_number' => 'nullable|string|max:50',
            'emergency_contact' => 'nullable|string|max:255',
            'fb_messenger' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'barangay' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'course_id' => 'nullable|integer|exists:courses,course_id',
            'course_name' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        // Determine course_id: prefer chosen course_id, otherwise create/find by name
        $courseId = $data['course_id'] ?? null;
        if (empty($courseId) && !empty($data['course_name'])) {
            $course = Course::firstOrCreate([
                'course_name' => $data['course_name']
            ], [
                'college' => ''
            ]);
            $courseId = $course->course_id;
        }

        $profilePicturePath = null;
        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
        }

        $profile = VolunteerProfile::create([
            'course_id' => $courseId,
            'full_name' => $data['full_name'],
            'id_number' => $data['id_number'] ?? null,
            'year_level' => $data['year_level'] ?? null,
            'email' => $data['email'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'fb_messenger' => $data['fb_messenger'] ?? 'No FB messenger',
            'barangay' => $data['barangay'] ?? null,
            'district' => $data['district'] ?? null,
            'profile_picture_path' => $profilePicturePath,
            'profile_picture_url' => null,
            'notes' => $data['notes'] ?? 'No notes',
            'status' => $data['status'] ?? 'active',
        ]);

        return redirect()->back()->with('success', 'Student added successfully.');
    }
}