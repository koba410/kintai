<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'start_time',
        'end_time',
        'duration',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function corrections()
    {
        return $this->hasOne(BreakCorrection::class, 'break_id');
    }
}
