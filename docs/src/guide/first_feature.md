# First feature

## Thinking past base10
Now that we have a working basic MVP, we want to start considering and planning our first additional feature to make this product slightly more useable. We mentioned prior but using base10 for our identifiers for our shortened urls is less than ideal, we only get 0-9 per character after our root url which is 10 potential characters, so after 10 rows in our database we are up to a url id that is two characters long. If we were to use 0-9 a-z and A-Z we move from base10 to base62 (10 + 26 + 26). It is pretty common for url shorteners to use base62 but lets just think about why. Once we have 100 rows in our database a base10 `id` will be 3 characters long while a base62 `id` will only be 2 characters long: `1C`. Having a much higher base will help us keep our URLs short! 

## Planning the implementation

So there are a few ways we can implement this, immediately we could be adding a new column called something like `hash` that could represent the `base62` id for our row in the database, we could index this column to ensure we can look it up quickly. But we would also have to consider how we would go about generating these hashes. We'd need to make sure they are unique and incrementing properly. These present some difficulty because they would require querying the database to figure out what the next `base62` hash would be. 

If we think about it though we already have something that pretty much fits all of our requirements, the auto-incrementing, unique and indexed column in our tiny table. Thats the primary key column `id`. If we are going to continue to use the `id` column we need to adjust two things to make it work.

1. We need to be able to return to the user the `base62` version of our ids
2. We need to be able to look up our urls table entries given a `base62` id.

## Creating base conversion

We have identified that we need two functions two make this work, something two convert base10 to base62 and one to convert base62 to base10. Lets start with base10 to base62:

```php
function base62($num) {
  $base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $res = '';
  do {
    $res = $base[$num % 62] . $res;
    $num = intval($num / 62);
  } while ($num);
  return $res;
}
```

if we test this out we should see `base62(100) == 1C`.

and then to create the inverse we are going to need to do something like the following:

```php
function to10($num) {
  $base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $limit = strlen($num);
  $res = strpos($base, $num[0]);
  for($i = 1; $i < $limit; $i++) {
    $res = 62 * $res + strpos($base, $num[$i]);
  }
  return $res;
}
```

if we test this out we should see `to10(base62(100)) == 100`. Now that we have these functions lets add them as some private functions on our Url model. This might not be an ideal place for them but we really just want to get this working. First lets add a constant to the top of the file so we have the same `$base` being used by both functions so we don't have any mistakes going back and forth as the order matters for translation between.

```php
// app/Models/Url.php

class Url extends Model
{
    use HasFactory;

    const BASE = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // ...
}
```

now lets add these two private functions to the bottom of our class:
```php
private function base62($num) {
    $res = '';

    do {
        $res = Url::BASE[$num % 62] . $res;
        $num = intval($num / 62);
    } while ($num);

    return $res;
}

private function to10($num) {
    $limit = strlen($num);
    $res = strpos(Url::BASE, $num[0]);

    for($i = 1; $i < $limit; $i++) {
        $res = 62 * $res + strpos(Url::BASE, $num[$i]);
    }

    return $res;
}
```

## Writing tests for our new feature

Now that we have these two functions lets make some tests for our next steps. We are going to want to make some unit tests and update some feature tests. Lets start with the unit tests for our desired outcome. 

```sh
php artisan make:test UrlTest --unit
```

And then we are going to make some adjustments to the new file and write our first test:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Url;

class UrlTest extends TestCase
{
    public function test_url_base62_returns_urls_base62_version_of_id()
    {
        $url = Url::make(['id' => 100, 'url' => 'https://www.google.com']);

        $this->assertEquals('1C', $url->base62id());
    }
}
```
If we run the test now we should see:
```sh
BadMethodCallException: Call to undefined method App\Models\Url::base62id()
```
Now if we add some code to return the `base62id`:

```php
// app/models/Url.php

public function base62id() {
    return $this->base62($this->id);
}
```

This is just converting the model's id to base62 and returning it so lets give it another shot:

```sh
Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'1C'
+'1'
```

So this is not working as expected. Looking at the output it looks as if we are returning `id` 1 which implies we are possibly not setting a different id in our test. That's because `id` is not a [`fillable`](https://laravel.com/docs/8.x/eloquent#mass-assignment) property, we can add `id` to fillable which would allow it to be set by form input or we can just set the `id` in code. For now lets just update the test and see if that works:
```php
$url = Url::create(['url' => 'https://www.google.com']);
$url->id = 100;
$this->assertEquals('1C', $url->base62id());
```

And now we see our test pass! Thats great, our next step is going to be to start returning that in our view instead of the `id`. Let's update our redirect with session data:

```php
// routes/web.php

return redirect(route('home'))->with(['urlId' => $url->base62id()]);
```

Now if we run the tests again do you think they are going to pass or fail? We have changed the logic in our route and are asserting that we attach the `id` even though we are now calling `$url->base62id()`. If we run the tests they are still going to pass, that's because 1 is 1 in `base10` and `base62`. Let's figure out a way we can ensure this test would fail unless we changed the test(`tests/Feature/UrlTest.php`) to start looking for the `base62id`.

## Factories

To help improve testing with ease Laravel has some helpful tools at its disposal such as [model factories](https://laravel.com/docs/8.x/database-testing#defining-model-factories) that help you generate fake data with ease and default data. Let's go ahead and make a UrlFactory tied to our Url model:

```sh
php artisan make:factory UrlFactory --model=Url
```

if we now look in out `database/factories/` folder we should see a new file: `UrlFactory.php` lets take a look:

```php
<?php

namespace Database\Factories;

use App\Models\Url;
use Illuminate\Database\Eloquent\Factories\Factory;

class UrlFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Url::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
        ];
    }
}
```

We don't need to add a lot to our factory as our table only really has one interesting column which is the url column. Factories also give us access to [Faker](https://github.com/FakerPHP/Faker) which is a php library that generates fake data. Lets go ahead and add a fake url to our Factory definition:

```php
    public function definition()
    {
        return [
            'url' => $this->faker->url,
        ];
    }
```

Now that we have this model factory lets go ahead and try using it in our test:

```php
// tests/Feature/UrlTest.php

public function test_we_create_a_url_record()
{
    Url::factory()->count(10)->create();
    // ... rest of the test ...
}
```
with this new list we are creating 10 urls before our user creates one, let's see if our test still passes.

```sh
Failed asserting that 'c' matches expected 12.
```

Now that our test is working as expected, and failing, let's fix it. Now if we run our server and create enough records to get past `id` 10 we should see something like this:

<img :src="$withBase('/08_base_62.png')" alt="base62 url">

Thats great but if we visit that url (`http://localhost:8000/url/f`) it is not going to work, we should see a 404. This is due to our implicit model route binding not finding a row with id `f`.

## Explicit binding and Scopes

Now we want to switch to explicit model binding. We already have a test that checks our redirects work correctly, lets update it with our new desired outcome:

```php
// tests/Feature/UrlTest.php

$url = Url::create(['url' => 'https://www.google.com']);
$url->id = 10;
$url->save();
$response = $this->get(route('shortened', $url->base62id()));
$response->assertRedirect($url->url);
```

We've gone ahead and updated the test to have an id above 9, and started using the handy `route()` helper. Our test is now failing with 
```sh
Response status code [404] is not a redirect status code.
```

Before we can create our new model binding we need to add a way to find these models with their `base62id`. Lets create a [scope](https://laravel.com/docs/8.x/eloquent#query-scopes) that can take in a `base62id` and convert it to an id and return the appropriate record.  

```php
// app/models/Url.php

public function scopeFromBase62($query, $base62)
{
    $id = $this->to10($base62);

    return $query->where('id', $id);
}
```

This will convert the id and return a query where the id is equal to the converting base10 version of what was passed in.

Lets take a look at the [explicit model binding documentation](https://laravel.com/docs/8.x/routing#explicit-binding). It looks like we need to go into our `RouteServiceProvider.php` and update the logic for our `Url` model within the `boot()` function:

```php
    Route::bind('url', function ($value) {
        return Url::fromBase62($value)->firstOrFail();
    });
```

Now if we run the tests or visit the url we should see it works as expected! Great, we have fully implemented the feature of using shorter urls.