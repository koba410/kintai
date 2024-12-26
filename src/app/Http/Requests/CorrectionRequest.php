<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i|before:check_out',
            'check_out' => 'required|date_format:H:i|after:check_in',
            'note' => 'required|string|max:255',
        ];

        // 休憩データのバリデーション
        foreach ($this->input('breaks', []) as $index => $break) {
            $rules["breaks.{$index}.start_time"] = 'required|date_format:H:i|before:breaks.' . $index . '.end_time|after_or_equal:check_in|before_or_equal:check_out';
            $rules["breaks.{$index}.end_time"] = 'required|date_format:H:i|after:breaks.' . $index . '.start_time|before_or_equal:check_out|after_or_equal:check_in';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'check_in.before' => '出勤時間もしくは退勤時間が不適切な値です。',
            'check_out.after' => '出勤時間もしくは退勤時間が不適切な値です。',
            'breaks.*.start_time.before' => '休憩開始時間は休憩終了時間より前である必要があります。',
            'breaks.*.end_time.after' => '休憩終了時間は休憩開始時間より後である必要があります。',
            'breaks.*.start_time.after_or_equal' => '休憩時間が勤務時間外です。',
            'breaks.*.start_time.before_or_equal' => '休憩時間が勤務時間外です。',
            'breaks.*.end_time.before_or_equal' => '休憩時間が勤務時間外です。',
            'breaks.*.end_time.after_or_equal' => '休憩時間が勤務時間外です。',
            'note.required' => '備考を記入してください。',
        ];
    }
}
