<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト前に 'staff' ロールを作成
        Role::create(['name' => 'staff']);
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->user->assignRole('staff');
    }

    /** @test */
    public function it_displays_out_of_work_status_when_user_is_not_working()
    {
        // ログイン
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance'); // 勤怠打刻画面のルートを指定

        // ステータスが「勤務外」と表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee('勤務外'); // UI上に「勤務外」が表示されていることを確認
    }

    /** @test */
    public function it_displays_working_status_when_user_is_working()
    {
        // 勤怠データを作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in' => Carbon::now(),
            'check_out' => null,
            'date' => Carbon::now()->format('Y-m-d'),
            'attendance_status' => '出勤中',
        ]);

        // ログイン
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance'); // 勤怠打刻画面のルートを指定

        // ステータスが「出勤中」と表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee('出勤中'); // UI上に「出勤中」が表示されていることを確認
    }

    /** @test */
    public function it_displays_resting_status_when_user_is_resting()
    {
        // 勤怠データを作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in' => Carbon::now(),
            'check_out' => null,
            'date' => Carbon::now()->format('Y-m-d'),
            'attendance_status' => '休憩中',
        ]);

        // ログイン
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance'); // 勤怠打刻画面のルートを指定

        // ステータスが「休憩中」と表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee('休憩中'); // UI上に「休憩中」が表示されていることを確認
    }

    /** @test */
    public function it_displays_check_out_status_when_user_is_check_out()
    {
        // 勤怠データを作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in' => Carbon::now(),
            'check_out' => Carbon::now(),
            'date' => Carbon::now()->format('Y-m-d'),
            'attendance_status' => '退勤済',
        ]);

        // ログイン
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance'); // 勤怠打刻画面のルートを指定

        // ステータスが「退勤済」と表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee('退勤済'); // UI上に「退勤済」が表示されていることを確認
    }
}
