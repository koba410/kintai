<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
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

        // 1. 現在の日時情報を固定してテスト
        Carbon::setTestNow('2024-12-16 21:40:00'); // テスト時の現在時刻を固定
    }

    /** @test */
    public function it_shows_the_clock_in_button_and_correctly_updates_status_to_working()
    {
        // 1. ステータスが勤務外のユーザーにログイン
        $this->actingAs($this->user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 2. 「出勤」ボタンが表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee('勤務外');

        // 3. 出勤の処理を行う
        $this->post('/attendance', ['status' => '勤務外', 'action' => 'start_work']); // 出勤処理を行うルートを呼び出す

        // 勤怠データが正しく作成されたことを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'attendance_status' => '出勤中',
        ]);

        // 再度勤怠打刻画面を開き、ステータスが「勤務中」に更新されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /** @test */
    public function it_doesnot_shows_the_clock_in_button_when_user_is_check_out()
    {
        // 勤怠データを作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in' => Carbon::now(),
            'check_out' => Carbon::now(),
            'date' => Carbon::now()->format('Y-m-d'),
            'attendance_status' => '退勤済',
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertDontSee('出勤');
        $response->assertSee('退勤済');
    }

    /** @test */
    public function it_records_and_displays_clock_in_time_in_admin_panel()
    {
        // 1. ステータスが勤務外のユーザーにログイン
        $this->actingAs($this->user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 2. 出勤の処理を行う
        $this->post('/attendance', ['status' => '勤務外', 'action' => 'start_work']); // 出勤処理を行うルートを呼び出す

        // 3. 出勤時間が記録されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'attendance_status' => '出勤中',
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
        $response->assertStatus(200);
        $response->assertSee('21:40');
        $response->assertSee('test');
        $response->assertSee('2024年12月16日');
    }
}
