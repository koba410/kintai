<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use Spatie\Permission\Models\Role;

class RestingTest extends TestCase
{
    
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $time_check_in;
    protected $time_start_rest;
    protected $time_end_rest;

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
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in' => Carbon::now(),
            'check_out' => null,
            'date' => Carbon::now()->format('Y-m-d'),
            'attendance_status' => '出勤中',
        ]);
    }

    /** @test */
    public function Break_In_Button_is_displayed_on_screen_and_status_changes_to_On_Break_after_processing()
    {
        // 1. ステータスが出勤中のユーザーにログイン
        $this->actingAs($this->user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 2. 「休憩入」ボタンが表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 3. 休憩入の処理を行う
        $this->post('/attendance', ['status' => '出勤中', 'action' => 'start_break']); // 休憩処理を行うルートを呼び出す

        // 勤怠データが正しく作成されたことを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'attendance_status' => '休憩中',
        ]);

        // 再度勤怠打刻画面を開き、ステータスが「休憩中」に更新されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /** @test */
    public function Break_In_Button_is_displayed_on_screen()
    {
        // ステータスが出勤中のユーザーにログイン
        $this->actingAs($this->user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 休憩入の処理を行う
        $this->post('/attendance', ['status' => '出勤中', 'action' => 'start_break']); // 休憩入処理を行うルートを呼び出す
        // 休憩戻の処理を行う
        $this->post('/attendance', ['status' => '休憩中', 'action' => 'end_break']); // 休憩戻処理を行うルートを呼び出す

        // 再度勤怠打刻画面を開き、ステータスが「出勤中」に更新されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');
    }

    /** @test */
    public function Return_From_Break_Button_is_displayed_and_status_changes_to_Working_after_processing()
    {
        // ステータスが出勤中のユーザーにログイン
        $this->actingAs($this->user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 休憩入の処理を行う
        $this->post('/attendance', ['status' => '出勤中', 'action' => 'start_break']); // 休憩入処理を行うルートを呼び出す

        // 再度勤怠打刻画面を開き、「休憩戻」ボタンが表示されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        // 休憩戻の処理を行う
        $this->post('/attendance', ['status' => '休憩中', 'action' => 'end_break']); // 休憩戻処理を行うルートを呼び出す

        // 再度勤怠打刻画面を開き、ステータスが「出勤中」に更新されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /** @test */
    public function Return_From_Break_Button_is_displayed_on_screen()
    {
        // ステータスが出勤中のユーザーにログイン
        $this->actingAs($this->user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 休憩入の処理を行う
        $this->post('/attendance', ['status' => '出勤中', 'action' => 'start_break']); // 休憩入処理を行うルートを呼び出す
        // 休憩戻の処理を行う
        $this->post('/attendance', ['status' => '休憩中', 'action' => 'end_break']); // 休憩戻処理を行うルートを呼び出す
        // 休憩入の処理を行う
        $this->post('/attendance', ['status' => '出勤中', 'action' => 'start_break']); // 休憩入処理を行うルートを呼び出す

        // 再度勤怠打刻画面を開き、「休憩戻」ボタンが表示されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩戻');
    }

    /** @test */
    public function Break_time_is_accurately_recorded_in_management_screen()
    {
        // ステータスが出勤中のユーザーにログイン
        $this->actingAs($this->user);

        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 休憩時間の日時情報を固定して休憩時間を記録
        Carbon::setTestNow('2024-12-16 12:00:00'); 
        $this->post('/attendance', ['status' => '出勤中', 'action' => 'start_break']); // 休憩入処理を行うルートを呼び出す
        // 休憩戻の処理を行う
        Carbon::setTestNow('2024-12-16 13:00:00');
        $this->post('/attendance', ['status' => '休憩中', 'action' => 'end_break']); // 休憩戻処理を行うルートを呼び出す
        
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
        $response->assertSee('1:00');
        $response->assertSee('test');
        $response->assertSee('2024年12月16日');
    }
}
