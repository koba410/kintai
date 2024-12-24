<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class StaffLoginTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト前に 'staff' ロールを作成
        Role::create(['name' => 'staff']);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('staff');
    }


    /** @test */
    public function it_shows_validation_error_when_email_is_missing()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください。']);
    }

    /** @test */
    public function it_shows_validation_error_when_password_is_missing()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください。']);
    }

    /** @test */
    public function it_shows_validation_error_when_credentials_are_incorrect()
    {
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect(route('login')); // リダイレクト先の確認
        $response->assertSessionHas('status', 'ログイン情報が正しくありません。');
    }
}
