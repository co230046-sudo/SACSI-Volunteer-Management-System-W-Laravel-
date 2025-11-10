<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use HasFactory;

    protected $table = 'import_logs';
    protected $primaryKey = 'import_id';
    public $timestamps = true; // enable timestamps

    protected $fillable = [
        'file_name',
        'admin_id',
        'total_records',
        'valid_count',
        'invalid_count',
        'duplicate_count',
        'status',
        'remarks',
    ];

    public function admin()
    {
        return $this->belongsTo(AdminAccount::class, 'admin_id', 'admin_id');
    }

    public function volunteerProfiles()
    {
        return $this->hasMany(VolunteerProfile::class, 'import_id', 'import_id');
    }
}
