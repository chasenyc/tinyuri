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
        $url = 'https://www.google.com';
        $response = $this->post('/url', ['url' => $url]);
        
        $response->assertStatus(200);

        $this->assertDatabaseHas('urls', [
            'url' => $url
        ]);

        $row = Url::where('url', $url)->first();

        $response->assertSee($row->id);
    }

    public function test_we_redirect_users_to_url()
    {
        $url = Url::create(['url' => 'https://www.google.com']);

        $response = $this->get("/url/$url->id");
        $response->assertRedirect($url->url);
    }
}
