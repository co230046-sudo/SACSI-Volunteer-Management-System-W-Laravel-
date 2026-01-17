<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\VolunteerProfile;
use App\Models\ImportLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImportLogController extends Controller
{
    // Import logs page
    public function index()
    {
        // Fetch all import logs, newest first
        $importLogs = ImportLog::with('admin')
            ->orderBy('created_at', 'desc')
            ->get();

        // Hide cancelled + empty logs
        $importLogs = $importLogs->filter(function ($log) {
            return $log->status !== 'Cancelled' && $log->total_records > 0;
        });

        return view('import_logs.index', compact('importLogs'));
    }

    // Create import log row
    public function store(Request $request)
    {
        $request->validate([
            'file_name'        => 'required|string|max:255',
            'total_records'    => 'required|integer|min:0',
            'valid_count'      => 'required|integer|min:0',
            'invalid_count'    => 'required|integer|min:0',
            'duplicate_count'  => 'nullable|integer|min:0',
            'remarks'          => 'nullable|string',
            'fact_type'        => 'required|in:Import,Validation,Correction',
        ]);

        $admin = Auth::guard('admin')->user();

        ImportLog::create([
            'file_name'        => $request->file_name,
            'admin_id'         => $admin->admin_id ?? null,
            'fact_type'        => $request->fact_type,
            'remarks'          => $request->remarks ?? null,
            'total_records'    => $request->total_records,
            'valid_count'      => $request->valid_count,
            'invalid_count'    => $request->invalid_count,
            'duplicate_count'  => $request->duplicate_count ?? 0,
            'status'           => 'Completed',
            'completed_at'     => now(),
        ]);

        return redirect()->back()->with('success', 'Import log recorded successfully!');
    }

    // Delete old uploaded files not referenced by ImportLog
    private function cleanOldUploadsSafe()
    {
        $uploadPath = 'uploads';

        if (!Storage::disk('public')->exists($uploadPath)) return;

        $files = Storage::disk('public')->files($uploadPath);

        $activeFiles = ImportLog::pluck('file_name')->map(function ($name) {
            return $name;
        })->toArray();

        foreach ($files as $file) {
            $basename = basename($file);

            if (!in_array($basename, $activeFiles)) {
                Storage::disk('public')->delete($file);
            }
        }
    }
}
