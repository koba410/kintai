<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use Spatie\Permission\Models\Role;

class CheckOutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $user2;
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

        $this->user2 = User::factory()->create([
            'name' => 'test2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->user2->assignRole('staff');

        // 現在の日時情報を固定してテスト
        Carbon::setTestNow('2024-12-16 09:00:00'); // テスト時の現在時刻を固定

        // 勤怠データを作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in' => Carbon::now(),
            'check_out' => null,
            'date' => Carbon::now()->format('Y-m-d'),
            'attendance_status' => '出勤中',
        ]);
    }

    /** @test */
    public function Clock_Out_Button_is_displayed_on_screen_and_status_changes_to_Clocked_Out_after_processing()
    {
        // 1. ステータスが出勤中のユーザーにログイン
        $this->actingAs($this->user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 2. 「休憩入」ボタンが表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee('退勤');

        // 3. 退勤の処理を行う
        $this->post('/attendance', ['status' => '出勤中', 'action' => 'end_work']); // 退勤処理を行うルートを呼び出す

        // 勤怠データが正しく作成されたことを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'attendance_status' => '退勤済',
        ]);

        // 再度勤怠打刻画面を開き、ステータスが「退勤済」に更新されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    /** @test */
    public function Clock_Out_time_is_accurately_recorded_in_management_screen()
    {
        // ステータスが出勤中のユーザーにログイン
        $this->actingAs($this->user2);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 出勤時間の日時情報を固定して出勤時間を記録
        Carbon::setTestNow('2024-12-16 09:00:00'); 
        $this->post('/attendance', ['status' => '勤務外', 'action' => 'start_work']); // 出勤処理を行うルートを呼び出す
        // 退勤時間の日時情報を固定して退勤時間を記録
        Carbon::setTestNow('2024-12-16 18:00:00');
        $this->post('/attendance', ['status' => '出勤中', 'action' => 'end_work']); // 退勤処理を行うルートを呼び出す
        
        // ログアウト
        $this->post('/logout');

        // 管理者としてログイン
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        // 管理者パネルで出勤時間が表示されていることを確認
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('18:00');
        $response->assertSee('test2');
        $response->assertSee('2024年12月16日');
    }
}
