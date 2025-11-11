<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Volunteer;
use App\Models\VolunteerProfile;
class ImportLogController extends Controller

{
    /**
     * Display a listing of import logs
     */
    public function index()
    {
        // Fetch all import logs, newest first
        $importLogs = ImportLog::with('admin')
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter out logs that are cancelled or have zero total_records
        $importLogs = $importLogs->filter(function ($log) {
            return $log->status !== 'Cancelled' && $log->total_records > 0;
        });

        return view('import_logs.index', compact('importLogs'));
    }

    /**
     * Store a new import log
     */
    public function store(Request $request)
    {
        $request->validate([
            'file_name' => 'required|string|max:255',
            'total_records' => 'required|integer|min:0',
            'valid_count' => 'required|integer|min:0',
            'invalid_count' => 'required|integer|min:0',
            'duplicate_count' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string',
            'fact_type' => 'required|in:Import,Validation,Correction',
        ]);

        $admin = Auth::guard('admin')->user();

        ImportLog::create([
            'file_name' => $request->file_name,
            'admin_id' => $admin->admin_id ?? null,
            'fact_type' => $request->fact_type,
            'remarks' => $request->remarks ?? null,
            'total_records' => $request->total_records,
            'valid_count' => $request->valid_count,
            'invalid_count' => $request->invalid_count,
            'duplicate_count' => $request->duplicate_count ?? 0,
            'status' => 'Completed',
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Import log recorded successfully!');
    }

    private function cleanOldUploadsSafe()
    {
        $uploadPath = 'uploads'; // relative to storage/app/public

        if (!Storage::disk('public')->exists($uploadPath)) return;

        // Get all files in the uploads directory
        $files = Storage::disk('public')->files($uploadPath);

        // Get all file names currently referenced in ImportLog
        $activeFiles = ImportLog::pluck('file_name')->map(function($name) {
            return $name; // adjust if you stored paths differently
        })->toArray();

        foreach ($files as $file) {
            $basename = basename($file); // just the file name

            // Delete only if file is NOT referenced in import logs
            if (!in_array($basename, $activeFiles)) {
                Storage::disk('public')->delete($file);
            }
        }
    }
}
