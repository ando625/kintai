<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class KintaiDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {

            for ($monthOffset = 0; $monthOffset <= 7; $monthOffset++) {
                $startDate = Carbon::now()->subMonths($monthOffset)->startOfMonth();
                $endDate   = Carbon::now()->subMonths($monthOffset)->endOfMonth();

                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {

                    if ($date->isToday() || $date->isFuture()) continue;

                    $isWeekend = $date->isWeekend();
                    $shouldWork = !$isWeekend || rand(0, 4) !== 0;

                    if (!$shouldWork) continue;

                    $clockIn  = Carbon::parse($date->format('Y-m-d') . ' 09:00')->addMinutes(rand(0, 20));
                    $clockOut = Carbon::parse($date->format('Y-m-d') . ' 18:00')->addMinutes(rand(0, 30));

                    $attendance = Attendance::create([
                        'user_id'    => $user->id,
                        'work_date'  => $date->format('Y-m-d'),
                        'clock_in'   => $clockIn,
                        'clock_out'  => $clockOut,
                        'status'     => 'finished',
                    ]);

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start'   => $date->copy()->setTime(12, 0),
                        'break_end'     => $date->copy()->setTime(13, 0),
                    ]);
                }
            }
        }
    }
}
