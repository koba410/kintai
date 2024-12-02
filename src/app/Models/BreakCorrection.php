<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'break_id',
        'attendance_correction_id',
        'corrected_start_time',
        'corrected_end_time',
        'corrected_duration',
    ];

    public function breakRecord()
    {
        return $this->belongsTo(BreakRecord::class, 'break_id');
    }

    public function attendanceCorrection()
    {
        return $this->belongsTo(AttendanceCorrection::class, 'attendance_correction_id');
    }
}
