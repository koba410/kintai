<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',             // ユーザーID
        'attendance_id',       // 勤怠ID
        'corrected_date',                // 日付
        'corrected_check_in',            // 出勤時間
        'corrected_check_out',           // 退勤時間
        'reason',                // 備考
        'approval_status',     // 承認ステータス
    ];

    public function breakCorrections()
    {
        return $this->hasMany(BreakCorrection::class);
    }
}
