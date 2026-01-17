<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportLog;
use Carbon\Carbon;

class CleanupPendingImports extends Command
{
    protected $signature = 'imports:cleanup-pending';
    protected $description = 'Mark old Pending imports as Abandoned if they are stale';

    public function handle()
    {
        // ✅ Define threshold: Pending with no activity for more than 2 hours
        $threshold = Carbon::now()->subHours(2);

        // ✅ Fetch stale imports (no updates for 2+ hours)
        $staleImports = ImportLog::where('status', 'Pending')
            ->where('updated_at', '<', $threshold)
            ->get();

        foreach ($staleImports as $import) {
            $import->update([
                'status'  => 'Abandoned',
                'remarks' => 'Pending import automatically abandoned by scheduler (stale).',
            ]);

            $this->info("✅ Import ID: {$import->import_id} marked as Abandoned.");
        }

        $this->info("Cleanup complete. Total abandoned: " . $staleImports->count());
        return 0;
    }
}
