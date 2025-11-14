<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AmendmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in'  => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],
            'remarks'   => ['required', 'string', 'max:255'],
            'break_times' => ['array'],
            'break_times.*.start' => ['nullable', 'date_format:H:i'],
            'break_times.*.end'   => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.required'   => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in.date_format' => '全ての時間は00:00形式で入力してください',
            'clock_out.required'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.date_format' => '全ての時間は00:00形式で入力してください',
            'remarks.required'    => '備考を記入してください',
            'remarks.max'         => '備考は255文字以内で入力してください',
            'break_times.*.start.date_format' => '全ての時間は00:00形式で入力してください',
            'break_times.*.end.date_format'   => '全ての時間は00:00形式で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $clockIn  = $this->clock_in;
            $clockOut = $this->clock_out;

            if ($clockIn && $clockOut) {
                // 出勤時間より後の場合はエラー
                if ($clockIn >= $clockOut) {
                    $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                    $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            foreach ($this->break_times ?? [] as $i => $break) {
                $start = $break['start'] ?? null;
                $end   = $break['end'] ?? null;

                if ($start && $end) {
                    // 休憩開始は出勤以降・退勤以前
                    if ($start < $clockIn || $start > $clockOut) {
                        $validator->errors()->add("break_times.$i", '休憩時間が不適切な値です');
                    }
                    // 休憩終了は開始より後、退勤以前
                    if ($end < $start || $end > $clockOut) {
                        $validator->errors()->add("break_times.$i", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                } elseif ($start || $end) {
                    // 片方だけ入力はエラー
                    $validator->errors()->add("break_times.$i", '休憩は開始と終了両方を入力してください');
                }
            }
        });
    }
}
