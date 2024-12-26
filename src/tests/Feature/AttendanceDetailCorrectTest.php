<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakRecord;
use App\Models\AttendanceCorrection;
use Spatie\Permission\Models\Role;

class AttendanceDetailCorrectTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $attendance;
    protected $breakRecord;
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
        Carbon::setTestNow('2024-12-16 09:00:00'); // テスト時の現在時刻を固定

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
    public function Validation_message_Clock_in_or_clock_out_time_is_invalid_is_displayed()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/' . $this->attendance->id);

        $response = $this->post('/attendance/' . $this->attendance->id, [
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
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/' . $this->attendance->id);

        $response = $this->post('/attendance/' . $this->attendance->id, [
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
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/' . $this->attendance->id);

        $response = $this->post('/attendance/' . $this->attendance->id, [
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
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/' . $this->attendance->id);

        $response = $this->post('/attendance/' . $this->attendance->id, [
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

    /** @test */
    public function Correction_request_is_executed_and_displayed_on_the_admin_approval_screen_and_request_list_screen()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/' . $this->attendance->id);

        $response = $this->post('/attendance/' . $this->attendance->id, [
            'name' => $this->user->name,
            'date' => $this->attendance->date,
            'check_in' => '10:00',
            'check_out' => '19:00',
            'breaks' => [
                '0' => [
                    'id' => $this->breakRecord->id,
                    'start_time' => '13:00',
                    'end_time' => '14:00',
                ],
            ],
            'note' => 'testreason',
        ]);
        // リダイレクト先を追跡
        $response->assertRedirect('/attendance/' . $this->attendance->id);
        $response = $this->followRedirects($response);
        // ステータスコードが 200 を確認
        $response->assertStatus(200);

        $attendanceCorrection = AttendanceCorrection::where('attendance_id', $this->attendance->id)->first();

        // データベースに出勤退勤の修正時間が登録されていることを確認
        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $this->attendance->id,
            'corrected_check_in' => '2024-12-16 10:00:00',
            'corrected_check_out' => '2024-12-16 19:00:00',
            'reason' => 'testreason',
        ]);
        // データベースに休憩の修正時間が登録されていることを確認
        $this->assertDatabaseHas('break_corrections', [
            'attendance_correction_id' => $attendanceCorrection->id,
            'corrected_start_time' => '2024-12-16 13:00:00',
            'corrected_end_time' => '2024-12-16 14:00:00',
        ]);

        // ログアウト
        $this->post('/logout');

        // 4. 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        // 5. 管理者パネルで出勤時間が表示されていることを確認
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/stamp_correction_request/list');        
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('test');
        $response->assertSee('2024/12/16');
        $response->assertSee('testreason');

        // 6. 勤怠詳細ページを開き、修正時間が表示されていることを確認
        $response = $this->get('/stamp_correction_request/approve/'.$attendanceCorrection->id);
        $response->assertStatus(200);
        $response->assertSee('2024年12月16日');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('13:00');
        $response->assertSee('14:00');
        $response->assertSee('testreason');
    }
    
    /** @test */
    public function All_of_my_requests_are_displayed_in_the_request_list()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/' . $this->attendance->id);
        
        $response = $this->post('/attendance/' . $this->attendance->id, [
            'name' => $this->user->name,
            'date' => $this->attendance->date,
            'check_in' => '10:00',
            'check_out' => '19:00',
            'breaks' => [
                '0' => [
                    'id' => $this->breakRecord->id,
                    'start_time' => '13:00',
                    'end_time' => '14:00',
                ],
            ],
            'note' => 'testreason',
        ]);
        
        // ログアウト
        $this->post('/logout');
        
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
        $response->assertSee('test');
        $response->assertSee('2024/12/16');
        $response->assertSee('testreason');
    }

    /** @test */
    public function All_requests_approved_by_the_admin_are_displayed_in_Approved()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/' . $this->attendance->id);

        $response = $this->post('/attendance/' . $this->attendance->id, [
            'name' => $this->user->name,
            'date' => $this->attendance->date,
            'check_in' => '10:00',
            'check_out' => '19:00',
            'breaks' => [
                '0' => [
                    'id' => $this->breakRecord->id,
                    'start_time' => '13:00',
                    'end_time' => '14:00',
                ],
            ],
            'note' => 'testreason',
        ]);
        $attendanceCorrection = AttendanceCorrection::where('attendance_id', $this->attendance->id)->first();

        // ログアウト
        $this->post('/logout');

        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/stamp_correction_request/list');

        // 勤怠詳細ページを開き、修正時間が表示されていることを確認
        $response = $this->get('/stamp_correction_request/approve/'.$attendanceCorrection->id);
        $response->assertStatus(200);
        $response = $this->post('/stamp_correction_request/approve/'.$attendanceCorrection->id, ['attendance_correct_request' => $attendanceCorrection->id]);
        $response = $this->followRedirects($response);
        $response->assertStatus(200);

        $response = $this->get('/admin/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('test');
        $response->assertSee('2024/12/16');
        $response->assertSee('testreason');
    }

    /** @test */
    public function Navigate_to_the_request_details_screen()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/' . $this->attendance->id);

        $response = $this->post('/attendance/' . $this->attendance->id, [
            'name' => $this->user->name,
            'date' => $this->attendance->date,
            'check_in' => '10:00',
            'check_out' => '19:00',
            'breaks' => [
                '0' => [
                    'id' => $this->breakRecord->id,
                    'start_time' => '13:00',
                    'end_time' => '14:00',
                ],
            ],
            'note' => 'testreason',
        ]);
        $attendanceCorrection = AttendanceCorrection::where('attendance_id', $this->attendance->id)->first();

        // ログアウト
        $this->post('/logout');

        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $response = $this->get('/admin/attendance/list');
        $response = $this->get('/admin/stamp_correction_request/list');

        // 勤怠詳細ページを開き、修正時間が表示されていることを確認
        $response = $this->get('/stamp_correction_request/approve/'.$attendanceCorrection->id);
        $response->assertStatus(200);
    }
}

