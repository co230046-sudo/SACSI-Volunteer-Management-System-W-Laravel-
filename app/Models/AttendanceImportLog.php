<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceImportLog extends Model
{
    use HasFactory;

    protected $table = 'attendance_import_logs';
    protected $primaryKey = 'import_id';
    public $incrementing = true;
    public $timestamps = true; // ✅ because migration has timestamps()

    protected $fillable = [
        'event_id','admin_id','filename','import_batch',
        'total_records','valid_count','invalid_count',
        'duplicate_count','walk_in_count',
        'import_date','remarks',
    ];

    protected $casts = [
        'import_date' => 'datetime',
    ];
}
