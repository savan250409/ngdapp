<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** Guests are redirected to the login screen. */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    /** The login screen is reachable for guests. */
    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    /** An authenticated admin reaches the dashboard. */
    public function test_authenticated_admin_sees_dashboard(): void
    {
        $admin = User::factory()->create(['email' => 'admin@gmail.com']);

        $this->actingAs($admin)->get('/dashboard')->assertStatus(200)->assertSee('Dashboard');
        $this->actingAs($admin)->get('/')->assertRedirect(route('dashboard'));
    }
}
