<?php

namespace Tests\Feature;

use App\Models\Url;
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

    public function test_we_redirect_users_to_url()
    {
        $url = Url::create(['url' => 'https://www.google.com']);
        $url->id = 10;
        $url->save();
        $response = $this->get(route('shortened', $url->base62id()));
        $response->assertRedirect($url->url);
    }
}
