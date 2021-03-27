<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Url;

class UrlTest extends TestCase
{
    public function test_url_base62_returns_urls_base62_version_of_id()
    {
        $url = Url::create(['url' => 'https://www.google.com']);
        $url->id = 100;
        $this->assertEquals('1C', $url->base62id());
    }
}
