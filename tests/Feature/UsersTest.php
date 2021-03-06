<?php

namespace Tests\Feature;

use Hash;
use App\Models\User;
use App\Models\Url;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UsersTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_users_can_register()
    {
        $userData = [
            'email' => 'fake@email.com',
            'password' => 'password1234'
        ];

        $response = $this->post(route('register'), $userData);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email']
        ]);

        $this->assertAuthenticated();
    }

    public function test_users_can_login()
    {
        $this->withoutExceptionHandling();
        $userData = [
            'email' => 'fake@email.com',
            'password' => 'password1234'
        ];

        User::create([
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);

        $response = $this->post(route('login'), $userData);
        $response->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_logged_in_users_cannot_view_login_or_register()
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('session.create'));
        $response->assertRedirect(route('home'));

        $response = $this->get(route('register.create'));
        $response->assertRedirect(route('home'));
    }

    public function test_logout_removes_authenticated_status()
    {
        $this->actingAs(User::factory()->create());

        $this->assertAuthenticated();
        $this->post(route('logout'));
        $this->assertGuest();
    }

    public function test_a_user_can_see_all_of_their_urls()
    {
        $user = User::factory()->create();

        $urls = Url::factory()->count(10)->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->get(route('user.urls'));

        $response->assertStatus(200)
            ->assertSeeText(route('shortened', $urls->last()->base62id()));
    }
}
