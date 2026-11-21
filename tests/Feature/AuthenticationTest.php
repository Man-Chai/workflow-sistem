<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible_for_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Selamat datang kembali')
            ->assertSee('KorlasSync');
    }

    public function test_active_user_can_login_and_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'wali@example.test',
            'password' => 'password',
            'role' => User::ROLE_PARENT,
            'is_active' => true,
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'wali@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'wali@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'wali@example.test',
            'password' => 'salah-total',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'nonaktif@example.test',
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'nonaktif@example.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_login_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_inactive_session_is_terminated_on_next_request(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_registration_page_is_accessible_for_guests(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Buat akun orang tua')
            ->assertSee('Daftar akun');
    }

    public function test_guest_can_register_as_parent_and_is_logged_in(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Wali Murid Baru',
            'email' => 'wali.baru@example.test',
            'phone' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'wali.baru@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_PARENT, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'sudah.ada@example.test',
            'is_active' => true,
        ]);

        $this->post(route('register.store'), [
            'name' => 'Wali Murid Baru',
            'email' => 'sudah.ada@example.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
