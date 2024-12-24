<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakRecord;
use Spatie\Permission\Models\Role;

class AdminAttendanceDetailCorrectTest extends TestCase
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

    /** @test */
    public function The_content_of_the_detail_screen_matches_the_selected_information()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/attendance/' . $this->attendance->id);
        // ステータスコード確認
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee($this->attendance->date);
        $response->assertSee($this->attendance->check_in->format('H:i'));
        $response->assertSee($this->attendance->check_out->format('H:i'));
        $response->assertSee($this->breakRecord->start_time->format('H:i'));
        $response->assertSee($this->breakRecord->end_time->format('H:i'));
    }

    /** @test */
    public function Validation_message_Clock_in_or_clock_out_time_is_invalid_is_displayed()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/attendance/' . $this->attendance->id);
        // ステータスコード確認
        $response->assertStatus(200);

        $response = $this->post('/admin/attendance/' . $this->attendance->id, [
            'name' => $this->user->name,
            'date' => $this->attendance->date,
            'check_in' => '19:00',
            'check_out' => '18:00',
            'breaks' => [
                '0' => [
                    'id' => $this->breakRecord->id,
                    'start_time' => '12:00',
                    'end_time' => '13:00',
                ],
            ],
            'note' => 'testreason',
        ]);
        // バリデーションメッセージが表示されていることを確認
        $response->assertSessionHasErrors(['check_in' => '出勤時間もしくは退勤時間が不適切な値です。']);
    }

    /** @test */
    public function Validation_message_Break_time_is_outside_working_hours_is_displayed()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/attendance/' . $this->attendance->id);
        // ステータスコード確認
        $response->assertStatus(200);

        $response = $this->post('/admin/attendance/' . $this->attendance->id, [
            'name' => $this->user->name,
            'date' => $this->attendance->date,
            'check_in' => '09:00',
            'check_out' => '18:00',
            'breaks' => [
                '0' => [
                    'id' => $this->breakRecord->id,
                    'start_time' => '18:01',
                    'end_time' => '19:00',
                ],
            ],
            'note' => 'testreason',
        ]);
        // バリデーションメッセージが表示されていることを確認
        $response->assertSessionHasErrors(['breaks.0.start_time' => '休憩時間が勤務時間外です。']);
    }


    /** @test */
    public function Validation_message_Break_time_is_outside_working_hours_is_displayed_2()
    {
        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/attendance/' . $this->attendance->id);

        $response = $this->post('/admin/attendance/' . $this->attendance->id, [
            'name' => $this->user->name,
            'date' => $this->attendance->date,
            'check_in' => '09:00',
            'check_out' => '18:00',
            'breaks' => [
                '0' => [
                    'id' => $this->breakRecord->id,
                    'start_time' => '17:00',
                    'end_time' => '19:00',
                ],
            ],
            'note' => 'testreason',
        ]);
        // バリデーションメッセージが表示されていることを確認
        $response->assertSessionHasErrors(['breaks.0.end_time' => '休憩時間が勤務時間外です。']);
    }

    /** @test */
    public function Validation_message_Please_enter_a_remark_is_displayed()
    {
        // ユーザーとしてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/attendance/' . $this->attendance->id);

        $response = $this->post('/admin/attendance/' . $this->attendance->id, [
            'name' => $this->user->name,
            'date' => $this->attendance->date,
            'check_in' => '09:00',
            'check_out' => '18:00',
            'breaks' => [
                '0' => [
                    'id' => $this->breakRecord->id,
                    'start_time' => '12:00',
                    'end_time' => '13:00',
                ],
            ],
            'note' => '',
        ]);
        // バリデーションメッセージが表示されていることを確認
        $response->assertSessionHasErrors(['note' => '備考を記入してください。']);
    }


}
