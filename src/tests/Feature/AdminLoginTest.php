<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use App\Models\User;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト前に 'admin' ロールを作成
        Role::create(['name' => 'admin']);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('admin');
    }

    /** @test */
    public function it_shows_validation_error_when_email_is_missing()
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください。']);
    }

    /** @test */
    public function it_shows_validation_error_when_password_is_missing()
    {
        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください。']);
    }

    /** @test */
    public function it_shows_validation_error_when_credentials_are_incorrect()
    {
        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect(route('admin.login')); // リダイレクト先の確認
        $response->assertSessionHas('status', 'ログイン情報が正しくありません。');
    }
}
