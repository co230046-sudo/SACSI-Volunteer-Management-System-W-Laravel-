<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactType extends Model
{
    use HasFactory;

    protected $table = 'fact_types';
    protected $primaryKey = 'fact_type_id';
    public $timestamps = false;

    protected $fillable = [
        'type_name', // <-- use type_name
        'description',
    ];

    public function factLogs()
    {
        return $this->hasMany(FactLog::class, 'fact_type_id', 'fact_type_id');
    }
}
