<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakRecord;
use Spatie\Permission\Models\Role;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $attendance;
    protected $breakRecord;
    protected $staffs;
    protected function setUp(): void
    {
        parent::setUp();

        // 'staff' ロールを作成し、テスト用ユーザーを作成
        Role::create(['name' => 'staff']);
        Role::create(['name' => 'admin']);
        $this->user = User::factory()->create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->user->assignRole('staff');
        $this->admin->assignRole('admin');

        // スタッフを3人作成
        $this->staffs = User::factory(3)->create();

        foreach ($this->staffs as $staff) {
            $staff->assignRole('staff'); // スタッフロールを付与
            $this->createAttendanceData($staff); // 勤怠データ作成
        }

        // 現在の日時情報を固定してテスト
        Carbon::setTestNow('2024-12-18 09:00:00'); // テスト時の現在時刻を固定

        // 勤怠データを作成
        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in' => Carbon::now()->setTime(9, 0),
            'check_out' => Carbon::now()->setTime(18, 0),
            'date' => Carbon::now()->format('Y-m-d'),
            'attendance_status' => '退勤済',
        ]);
        $this->breakRecord = BreakRecord::create([
            'attendance_id' => $this->attendance->id,
            'start_time' => Carbon::now()->setTime(12, 0),
            'end_time' => Carbon::now()->setTime(13, 0),
            'duration' => '01:00:00',
        ]);
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
    
    /** @test */
    public function Attendance_information_for_all_users_on_that_day_is_correct()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('test');
        foreach ($this->staffs as $staff) {
            $response->assertSee($staff->name);
        }
    }
    
    /** @test */
    public function The_date_for_that_day_is_displayed_on_the_attendance_list_screen()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('2024-12-18');
    }
    
    /** @test */
    public function Attendance_information_for_the_previous_day_is_displayed()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        // 前月の勤怠情報を表示
        $response = $this->get('/admin/attendance/list?date=' . Carbon::now()->subDay()->format('Y-m-d'));
        // ステータスコード確認
        $response->assertStatus(200);
        // 前月が表示されていることを確認
        $response->assertSee(Carbon::now()->subDay()->format('Y-m-d'));
        $response->assertSee(Carbon::now()->subDay()->subDay()->format('Y-m-d'));
        foreach ($this->staffs as $staff) {
            $response->assertSee($staff->name);
        }
    }

    /** @test */
    public function Attendance_information_for_the_next_day_is_displayed()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        // 前月の勤怠情報を表示
        $response = $this->get('/admin/attendance/list?date=' . Carbon::now()->addDay()->format('Y-m-d'));
        // ステータスコード確認
        $response->assertStatus(200);
        // 前月が表示されていることを確認
        $response->assertSee(Carbon::now()->addDay()->format('Y-m-d'));
        $response->assertSee(Carbon::now()->addDay()->addDay()->format('Y-m-d'));
        foreach ($this->staffs as $staff) {
            $response->assertSee($staff->name);
        }
    }
}
