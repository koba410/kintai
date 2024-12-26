<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_the_current_date_and_time_on_the_ui()
    {
        // 1. 現在の日時情報を固定してテスト
        Carbon::setTestNow('2024-12-16 21:40:00'); // テスト時の現在時刻を固定

        // テスト前に 'staff' ロールを作成
        Role::create(['name' => 'staff']);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('staff');

        // ログイン
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->get('/attendance'); // 勤怠打刻画面のルートを指定

        // 3. UIに現在の日時が表示されているか確認
        $response->assertStatus(200);
        $response->assertSee('2024年12月16日 (月)');
        $response->assertSee('21:40');
    }
}
