<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakRecord;
use Spatie\Permission\Models\Role;

class AttendanceListTest extends TestCase
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

        // 現在の日時情報を固定してテスト
        Carbon::setTestNow('2024-12-16 09:00:00'); // テスト時の現在時刻を固定

        $startDate = Carbon::now()->subMonths(3)->startOfMonth(); // 3ヶ月前の月初
        $endDate = Carbon::now()->endOfMonth(); // 現在の月末

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            if ($currentDate->isWeekday()) { // 平日のみ
                $attendance = Attendance::create([
                    'user_id' => $this->user->id,
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
    public function All_of_my_attendance_information_is_displayed()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);

        // 勤怠一覧ページを開く
        $response = $this->get('/attendance/list');

        // ステータスコード確認
        $response->assertStatus(200);

        // 現在の月の平日を取得
        $workdays = [];
        $currentDate = Carbon::now()->firstOfMonth();
        $lastDay = Carbon::now()->lastOfMonth();

        while ($currentDate->lte($lastDay)) {
            if (!$currentDate->isWeekend()) { // 平日の場合
                $workdays[] = $currentDate->isoFormat('M/D (ddd)'); // 日付を文字列として保存
            }
            $currentDate->addDay();
        }

        // 勤怠情報が表示されていることを確認
        // 各平日が表示されていることを確認
        foreach ($workdays as $workday) {
            $response->assertSee($workday);
        }
        // 出勤時刻が表示されていることを確認
        $response->assertSee('09:00');
        // 退勤時刻が表示されていることを確認
        $response->assertSee('18:00');
        // 休憩時間が表示されていることを確認
        $response->assertSee('01:00');
        // 合計時間が表示されていることを確認
        $response->assertSee('08:00');
        // 詳細ボタンが表示されていることを確認
        $response->assertSee('詳細');
    }

    /** @test */
    public function Current_month_is_displayed()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠一覧ページを開く
        $response = $this->get('/attendance/list');
        // 現在の月が表示されていることを確認
        $response->assertSee(Carbon::now()->format('Y-m'));
    }

    /** @test */
    public function Previous_month_information_is_displayed()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠一覧ページを開く
        $response = $this->get('/attendance/list');
        // 前月の勤怠情報を表示
        $response = $this->get('/attendance/list?month=' . Carbon::now()->subMonth()->format('Y-m'));
        // ステータスコード確認
        $response->assertStatus(200);
        // 前月が表示されていることを確認
        $response->assertSee(Carbon::now()->subMonth()->format('Y-m'));
        $response->assertSee(Carbon::now()->subMonth()->subMonth()->format('Y-m'));
    }

    /** @test */
    public function Next_month_information_is_displayed()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠一覧ページを開く
        $response = $this->get('/attendance/list');
        // 翌月の勤怠情報を表示
        $response = $this->get('/attendance/list?month=' . Carbon::now()->addMonth()->format('Y-m'));
        // ステータスコード確認
        $response->assertStatus(200);
        // 翌月が表示されていることを確認
        $response->assertSee(Carbon::now()->addMonth()->format('Y-m'));
        $response->assertSee(Carbon::now()->addMonth()->addMonth()->format('Y-m'));
    }

    /** @test */
    public function Navigate_to_the_attendance_details_screen_for_that_day  ()
    {
        // ユーザーとしてログイン
        $this->actingAs($this->user);
        // 勤怠一覧ページを開く
        $response = $this->get('/attendance/list');
        // 勤怠情報を取得
        $attendance = Attendance::first();
        // 勤怠詳細ページを開く
        $response = $this->get('/attendance/'.$attendance->id);
        // ステータスコード確認
        $response->assertStatus(200);
        // 詳細ページが表示されていることを確認
        $response->assertSee('勤怠詳細');
    }
}
