<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $date = $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d');

        // 出勤 9:00〜9:30、退勤 17:00〜18:00 の範囲
        $clockIn = Carbon::parse($date . ' 09:00')->addMinutes(rand(0, 30));
        $clockOut = Carbon::parse($date . ' 17:00')->addMinutes(rand(0, 60));


        return [

            'work_date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => 'off_duty', // 勤務外が初期値
            'remarks' => $this->faker->sentence,

        ];
    }
}
