<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use HasFactory;

    protected $table = 'import_logs';
    protected $primaryKey = 'import_id';
    public $timestamps = true;

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

    // Relation: Belongs to AdminAccount
    public function admin()
    {
        return $this->belongsTo(AdminAccount::class, 'admin_id', 'admin_id');
    }

    // Relation: Has many VolunteerProfiles
    public function volunteerProfiles()
    {
        return $this->hasMany(VolunteerProfile::class, 'import_id', 'import_id');
    }
}
