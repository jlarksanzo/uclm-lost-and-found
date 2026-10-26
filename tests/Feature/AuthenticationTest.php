<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    public function test_public_pages_and_dashboard_access(): void
    {
        foreach (['/', '/login', '/register'] as $url) { $this->get($url)->assertOk(); }
        $this->get('/dashboard')->assertRedirect('/login');
    }
    public function test_registration_hashes_password_and_signs_in(): void
    {
        $this->post('/register', ['name'=>'Test Student','email'=>'STUDENT@example.test','password'=>'classroom123','password_confirmation'=>'classroom123'])->assertRedirect('/dashboard');
        $user = User::where('email', 'student@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('classroom123', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk()->assertSee('Test Student');
    }
    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email'=>'student@example.test']);
        $this->post('/register', ['name'=>'Test','email'=>'STUDENT@example.test','password'=>'classroom123','password_confirmation'=>'classroom123'])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }
    public function test_invalid_registration_is_rejected(): void
    {
        $this->post('/register', ['name'=>'','email'=>'invalid','password'=>'short','password_confirmation'=>'different'])->assertSessionHasErrors(['name','email','password']);
        $this->assertDatabaseCount('users', 0);
    }
    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['email'=>'student@example.test','password'=>'classroom123']);
        $this->post('/login', ['email'=>'student@example.test','password'=>'classroom123'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }
    public function test_wrong_password_does_not_authenticate(): void
    {
        User::factory()->create(['email'=>'student@example.test']);
        $this->post('/login', ['email'=>'student@example.test','password'=>'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
    public function test_login_attempts_are_limited(): void
    {
        for ($attempt=0; $attempt<6; $attempt++) {
            $this->post('/login', ['email'=>'nobody@example.test','password'=>'incorrect'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email'=>'nobody@example.test','password'=>'incorrect'])->assertStatus(429);
    }
}
