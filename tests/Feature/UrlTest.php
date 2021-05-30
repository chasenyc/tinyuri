<?php

namespace Tests\Feature;

use App\Models\Url;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UrlTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_we_create_a_url_record()
    {
        Url::factory()->count(10)->create();
        $url = 'https://www.google.com';
        $response = $this->post('/url', ['url' => $url]);

        $this->assertDatabaseHas('urls', [
            'url' => $url
        ]);

        $row = Url::where('url', $url)->first();
        
        $response->assertRedirect(route('home'));
        $response->assertSessionHas(['urlId' => $row->base62id()]);
    }

    public function test_we_return_an_error_for_invalid_url()
    {
        $url = 'google';
        $response = $this->post('/url', ['url' => $url]);
        $response->assertRedirect(route('home'));
        $response->assertSessionHasErrors(['url']);
    }

    public function test_we_redirect_users_to_url()
    {
        $url = Url::create(['url' => 'https://www.google.com']);
        $url->id = 10;
        $url->save();
        $response = $this->get(route('shortened', $url->base62id()));
        $response->assertRedirect($url->url);
    }

    public function test_a_logged_in_user_has_url_tied_to_them()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $this->actingAs($user);

        $url = 'https://www.google.com';
        $response = $this->post('/url', ['url' => $url]);

        $this->assertDatabaseHas('urls', [
            'url' => $url,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(1, $user->urls()->count());
    }
}
