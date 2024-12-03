<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakRecord;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ロールを作成
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        // 管理者アカウントの作成
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole($adminRole); // 管理者ロールを付与

        // スタッフアカウントの作成
        $staffs = User::factory(4)->create();

        foreach ($staffs as $staff) {
            $staff->assignRole($staffRole); // スタッフロールを付与
            $this->createAttendanceData($staff); // 勤怠データ作成
        }
    }

    private function createAttendanceData($staff)
    {
        $startDate = Carbon::now()->subMonths(3)->startOfMonth(); // 3ヶ月前の月初
        $endDate = Carbon::now()->endOfMonth(); // 現在の月末

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            if ($currentDate->isWeekday()) { // 平日のみ
                $attendance = Attendance::create([
                    'user_id' => $staff->id,
                    'date' => $currentDate->toDateString(),
                    'check_in' => $currentDate->copy()->setTime(9, 0),
                    'check_out' => $currentDate->copy()->setTime(18, 0),
                    'attendance_status' => '退勤済',
                ]);

                BreakRecord::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => $currentDate->copy()->setTime(12, 0),
                    'end_time' => $currentDate->copy()->setTime(13, 0),
                    'duration' => '01:00:00',
                ]);
            }
            $currentDate->addDay();
        }
    }
}
