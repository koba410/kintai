<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakRecord;
use App\Models\AttendanceCorrection;
use App\Models\BreakCorrection;
use Spatie\Permission\Models\Role;

class AdminAttendanceCorrectTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $attendance0;
    protected $attendance1;
    protected $breakRecord0;
    protected $breakRecord1;
    protected $staffs;
    protected $attendance_correct_waiting;
    protected $break_correct_waiting;
    protected $attendance_correct_done;
    protected $break_correct_done;
    protected function setUp(): void
    {
        parent::setUp();

        // 'staff' ロールを作成し、テスト用ユーザーを作成
        Role::create(['name' => 'staff']);
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->admin->assignRole('admin');
        // スタッフを3人作成
        $this->staffs = User::factory(3)->create();
        foreach ($this->staffs as $staff) {
            $staff->assignRole('staff'); // スタッフロールを付与
            $this->createAttendanceData($staff); // 勤怠データ作成
        }
        // 現在の日時情報を固定してテスト
        Carbon::setTestNow('2024-12-18 09:00:00'); // テスト時の現在時刻を固定

        $this->attendance0 = Attendance::where('user_id', $this->staffs[0]->id)
            ->where('date', Carbon::now()->format('Y-m-d'))
            ->first();
        $this->attendance1 = Attendance::where('user_id', $this->staffs[1]->id)
            ->where('date', Carbon::now()->format('Y-m-d'))
            ->first();
        $this->breakRecord0 = BreakRecord::where('attendance_id', $this->attendance0->id)
            ->first();
        $this->breakRecord1 = BreakRecord::where('attendance_id', $this->attendance1->id)
            ->first();
        $this->attendance_correct_waiting = AttendanceCorrection::create([
            'attendance_id' => $this->attendance0->id,
            'user_id' => $this->staffs[0]->id,
            'corrected_check_in' => Carbon::now()->setTime(10, 0),
            'corrected_check_out' => Carbon::now()->setTime(19, 0),
            'reason' => 'test_waiting',
            'approval_status' => '承認待ち',
            'corrected_date' => Carbon::now()->format('Y-m-d'),
        ]);
        $this->break_correct_waiting = BreakCorrection::create([
            'break_id' => $this->breakRecord0->id,
            'attendance_correction_id' => $this->attendance_correct_waiting->id,
            'corrected_start_time' => Carbon::now()->setTime(13, 0),
            'corrected_end_time' => Carbon::now()->setTime(14, 0),
            'corrected_duration' => '01:00:00',
        ]);
        $this->attendance_correct_done = AttendanceCorrection::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->staffs[1]->id,
            'corrected_check_in' => Carbon::now()->setTime(8, 0),
            'corrected_check_out' => Carbon::now()->setTime(17, 0),
            'approval_status' => '承認済み',
            'reason' => 'test_done',
            'corrected_date' => Carbon::now()->format('Y-m-d'),
            'admin_id' => $this->admin->id,
            'response_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
        ]);
        $this->break_correct_done = BreakCorrection::create([
            'break_id' => $this->breakRecord1->id,
            'attendance_correction_id' => $this->attendance_correct_done->id,
            'corrected_start_time' => Carbon::now()->setTime(11, 0),
            'corrected_end_time' => Carbon::now()->setTime(12, 0),
            'corrected_duration' => '01:00:00',
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
    public function All_users_unapproved_correction_requests_are_displayed()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        
        // 管理者パネルで出勤時間が表示されていることを確認
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/stamp_correction_request/list');        
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee($this->staffs[0]->name);
        $response->assertSee(\Carbon\Carbon::parse($this->attendance_correct_waiting->corrected_date)->format('Y/m/d'));
        $response->assertSee($this->attendance_correct_waiting->reason);
    }

    /** @test */
    public function All_users_approved_correction_requests_are_displayed()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee($this->staffs[1]->name);
        $response->assertSee(\Carbon\Carbon::parse($this->attendance_correct_done->corrected_date)->format('Y/m/d'));
        $response->assertSee($this->attendance_correct_done->reason);
    }

    /** @test */
    public function The_request_details_are_correctly_displayed()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/stamp_correction_request/list');

        // 勤怠詳細ページを開き、修正時間が表示されていることを確認
        $response = $this->get('/stamp_correction_request/approve/'.$this->attendance_correct_waiting->id);
        $response->assertStatus(200);
        $response->assertSee($this->staffs[0]->name);
        $response->assertSee(\Carbon\Carbon::parse($this->attendance_correct_waiting->corrected_date)->format('Y年m月d日'));
        $response->assertSee(\Carbon\Carbon::parse($this->attendance_correct_waiting->corrected_check_in)->format('H:i'));
        $response->assertSee(\Carbon\Carbon::parse($this->attendance_correct_waiting->corrected_check_out)->format('H:i'));
        $response->assertSee(\Carbon\Carbon::parse($this->break_correct_waiting->corrected_start_time)->format('H:i'));
        $response->assertSee(\Carbon\Carbon::parse($this->break_correct_waiting->corrected_end_time)->format('H:i'));
        $response->assertSee($this->attendance_correct_waiting->reason);
    }
    
    /** @test */
    public function The_correction_request_is_approved_and_attendance_information_is_updated()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/stamp_correction_request/list');

        // 勤怠詳細ページを開き、修正時間が表示されていることを確認
        $response = $this->get('/stamp_correction_request/approve/'.$this->attendance_correct_waiting->id);
        $response->assertStatus(200);
        $response = $this->post('/stamp_correction_request/approve/'.$this->attendance_correct_waiting->id, ['attendance_correct_request' => $this->attendance_correct_waiting->id]);
        $response = $this->followRedirects($response);
        $response->assertStatus(200);

        // 承認済みの申請が表示されていることを確認
        $response = $this->get('/admin/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee($this->staffs[0]->name);
        $response->assertSee(\Carbon\Carbon::parse($this->attendance_correct_waiting->corrected_date)->format('Y/m/d'));
        $response->assertSee($this->attendance_correct_waiting->reason);
    }
}