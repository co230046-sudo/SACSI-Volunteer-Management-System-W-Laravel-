<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Backfill only rows with null/empty codes
        $events = DB::table('events')
            ->select('event_id')
            ->where(function ($q) {
                $q->whereNull('event_code')
                  ->orWhere('event_code', '');
            })
            ->get();

        foreach ($events as $e) {
            // Generate unique code like ABC-123
            do {
                $letters = $this->letters(3);
                $digits  = random_int(100, 999);
                $code    = "{$letters}-{$digits}";

                $exists = DB::table('events')->where('event_code', $code)->exists();
            } while ($exists);

            DB::table('events')
                ->where('event_id', $e->event_id)
                ->update(['event_code' => $code]);
        }
    }

    public function down(): void
    {
        // No-op (don’t delete codes)
    }

    private function letters(int $len): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // no I/O
        $out = '';

        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }

        return $out;
    }
};
