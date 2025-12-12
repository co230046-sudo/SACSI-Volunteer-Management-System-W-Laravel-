<?php

namespace App\Http\Controllers;

use App\Models\VolunteerProfile;
use App\Models\Course;
use App\Models\Location;
use Illuminate\Http\Request;

class VolunteerProfileController extends Controller
{
    /**
     * Show a specific volunteer profile
     */
    public function show($id)
    {
        $volunteer = VolunteerProfile::with(['course', 'location'])->findOrFail($id);

        // Load dropdown helper data
        $courses = Course::orderBy('course_name')->get();
        $barangays = Location::distinct()->pluck('barangay')->filter()->sort()->values();
        $districts = Location::distinct()->pluck('district_id')->filter()->unique()->sort()->values();

        return view('volunteer_profile.volunteer_profile', compact(
            'volunteer',
            'courses',
            'barangays',
            'districts'
        ));
    }

    /**
     * Update profile
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'full_name'         => 'required|string|max:255',
            'id_number'         => 'nullable|string|max:50',
            'email'             => 'nullable|email|max:255',
            'contact_number'    => 'nullable|string|max:50',
            'emergency_contact' => 'nullable|string|max:50',
            'fb_messenger'      => 'nullable|string|max:255',
            'course_id'         => 'nullable|integer|exists:courses,course_id',
            'year_level'        => 'nullable|integer|min:1|max:10',
            'batch_year'        => 'nullable|integer|digits:4|min:2000|max:2100', // ✅ NEW
            'barangay'          => 'nullable|string|max:255',
            'district'          => 'nullable|string|max:255',
            'status'            => 'nullable|in:active,inactive',
        ]);

        $volunteer = VolunteerProfile::findOrFail($id);

        $volunteer->update($request->only([
            'full_name',
            'id_number',
            'email',
            'contact_number',
            'emergency_contact',
            'fb_messenger',
            'course_id',
            'year_level',
            'batch_year',   // ✅ NEW
            'barangay',
            'district',
            'status',
        ]));

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }


    /**
     * Delete profile
     */
    public function destroy($id)
    {
        VolunteerProfile::findOrFail($id)->delete();

        return redirect()->route('volunteers.list')
            ->with('success', 'Volunteer profile deleted.');
    }
}
