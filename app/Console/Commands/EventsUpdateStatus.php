<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use Carbon\Carbon;

class EventsUpdateStatus extends Command
{
    protected $signature = 'events:update-status';
    protected $description = 'Auto-update event statuses based on start/end datetime (Asia/Manila)';

    public function handle(): int
    {
        $now = Carbon::now('Asia/Manila');

        // planned -> ongoing
        Event::where('status', 'planned')
            ->whereNotNull('start_datetime')
            ->where('start_datetime', '<=', $now)
            ->update(['status' => 'ongoing']);

        // ongoing -> completed
        Event::where('status', 'ongoing')
            ->whereNotNull('end_datetime')
            ->where('end_datetime', '<=', $now)
            ->update(['status' => 'completed']);

        $this->info('Event statuses updated.');
        return self::SUCCESS;
    }
}
