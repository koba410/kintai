<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakRecord;
use Spatie\Permission\Models\Role;

class AttendanceDetailTest extends TestCase
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
    public function Name_matches_the_logged_in_user()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/'.$this->attendance->id);
        $response->assertStatus(200);
        // ユーザー名が表示されていることを確認
        $response->assertSee($this->user->name);
    }

    /** @test */
    public function Attendance_detail_screen_is_displayed()
    {
        // ユーザーとしてログイン   
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/'.$this->attendance->id);
        // 200ステータスが返されていることを確認
        $response->assertStatus(200);
        // 勤怠詳細ページのテンプレートが正しいことを確認
        $response->assertSee(Carbon::now()->format('Y-m-d'));
    }
    /** @test */
    public function Clock_in_and_out_times_match_the_logged_in_users_timestamps()
    {
        // ユーザーとしてログイン   
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/'.$this->attendance->id);
        // 200ステータスが返されていることを確認
        $response->assertStatus(200);
        // 勤怠詳細ページのテンプレートが正しいことを確認
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
    /** @test */
    public function Break_times_match_the_logged_in_users_timestamps()
    {
        // ユーザーとしてログイン   
        $this->actingAs($this->user);
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/'.$this->attendance->id);
        // 200ステータスが返されていることを確認
        $response->assertStatus(200);
        // 勤怠詳細ページのテンプレートが正しいことを確認
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
