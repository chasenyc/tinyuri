# Getting Started with Code
## Creating the model
The first thing we are going to want to do is make our data model, like most modern frameworks, Laravel comes with some very robust database migration tools. You can read more about database migrations and how to use them [here](laravel.com/docs/8.x/migrations). So our first and only table right now is going to be the `urls` table and we will create it by using the `php artisan` command:

```sh
php artisan make:migration create_urls_table
```

This is going to create a new file in our `database/migrations` folder with the following automatically generated:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('urls', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('urls');
    }
}
```

By default our table is almost fully setup, we only need to add one additional column which is the `varchar` column `url`. In laravel database migrations we will use the [`string`](https://laravel.com/docs/8.x/migrations#column-method-string) method for our urls like so:

```php
Schema::create('urls', function (Blueprint $table) {
    $table->id();
    $table->string('url');
    $table->timestamps();
});
```

After we have defined our migration we need to run it. If you have not setup mysql you may need to do that before as well as create the database for this project. Whatever you name your database, you will need to update in your `.env` file under `DB_DATABASE` to reflect the correct name. Once that is done we want to run our migrations:
```sh
php artisan migrate
```
we should see some output similar to the following:
```sh
Migration table created successfully.
Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table (35.59ms)
Migrating: 2014_10_12_100000_create_password_resets_table
Migrated:  2014_10_12_100000_create_password_resets_table (20.03ms)
Migrating: 2019_08_19_000000_create_failed_jobs_table
Migrated:  2019_08_19_000000_create_failed_jobs_table (20.36ms)
Migrating: 2021_03_21_152342_create_urls_table
Migrated:  2021_03_21_152342_create_urls_table (6.99ms)
```
We can now check our database either through the mysql cli or an app like [sequel ace](https://apps.apple.com/us/app/sequel-ace/id1518036000?mt=12) to see that our database has populated the new table with the correct schema. The next thing we will want to do is add the corresponding model to our code so we can reference the table. We can read in depth about Laravel's ORM, Eloquent, and how to generate models [here](https://laravel.com/docs/8.x/eloquent#generating-model-classes).

```sh
php artisan make:model Url
```

If we had wanted to do this quicker in one step we could have done:
```sh
php artisan make:model Url --migration
```
which would have created both the model and the migration, but for tutorials sake we want to be more explicit and go through each step individually. We should now see a new file in `app/models` directory called `Url.php`. It should look something like this:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Url extends Model
{
    use HasFactory;
}
```

## Creating a test

Before we continue any further with our application we are going to write a test for the expected behavior of our application. We are going to go to the `tests/Feature` folder and create a new file called `UrlTest.php`, we can do this manually or we can use the corresponding artisan command for it:
```sh
php artisan make:test UrlTest
```

We are going to start with just the beginning of a test. We want to assert that you can `POST` to a `/url` endpoint with a parameter `url` and receive a 200 response:

```php
public function test_we_create_a_url_record()
{
    $response = $this->post('/api/url', ['url' => 'https://www.google.com']);
    $response->assertStatus(200);
}
```

If we go back to the terminal and run this command we are going to see that we have a failing test!

```sh
There was 1 failure:

1) Tests\Feature\UrlTest::test_example
Expected status code 200 but received 404.
Failed asserting that 200 is identical to 404.
```

In order to make that pass, lets go over and create a new route that returns success when you post above. We can make a route by going into our web route file `web.php` and add a new post route matching what is in our test:

```php
Route::post('/url', function() {
    return 'success';
});
```

If we run the tests again they should all be green now, but we aren't exactly doing anything at all yet. The next step is to assert that we have a row with the supplied url in our database so we are going to update our test slightly:

```php
public function test_we_create_a_url_record()
{
    $url = 'https://www.google.com';
    $response = $this->post('/url', ['url' => $url]);
    
    $response->assertStatus(200);

    $this->assertDatabaseHas('urls', [
        'url' => $url
    ]);
}
```

What we are doing here now is we are checking that the database table `urls` has a row where `url` equals the value of whatever we have set `$url` to. We can read more about some of the available database assertions available to us [here](https://laravel.com/docs/8.x/database-testing#available-assertions). Our next step is now to see if we can have this endpoint create a new row in the database. Some of you will be wondering why we have not created a controller yet. For the time being we are trying to do the minimal amount of work to get this test working, afterwards we will definitely make sure to move this code into a more appropriate place but for now lets see what we need to do.

At the top of our `web.php` file we are going to want to include or url model with a use statement as well as a request object:
```php
use App\Models\Url;
use Illuminate\Http\Request;
```
and then within our route we want to access the posted body and create a new Url like the following:
```php
Route::post('/url', function(Request $request) {
    Url::create([
        'url' => $request->input('url')
    ]);
    return 'success';
});
```

If we go back and run our test now we would expect it to succeed but it is not, and unfortunately we are not getting a lot of helpful information because all we see is that we received a 500 error instead of a 200 error. We are not seeing what that 500 error is because our server handles exceptions gracefully when hit by a user. To stop the graceful exception handling we should be able to get to the bottom of this. We can do this by adding the following to the beginning of our test:
```php
$this->withoutExceptionHandling();
```
If we re-run the test we should now see a long stack trace that starts with the following:
```sh
Illuminate\Database\Eloquent\MassAssignmentException: Add [url] to fillable property to allow mass assignment on [App\Models\Url].
```
After referring to the documentation we can see that we need to add a property on our model, `$fillable` with the attributes that can be filled by user input. So we will go to our `Url.php` model and add the following:

```php
protected $fillable = [
    'url',
];
```

and try running our test one more time. Finally we have success! We now have an endpoint that can be hit with a url, that url will then be stored in our database with an id and a url. The last piece we will want to assert is that it returns the id of that new row. Let's update our test one last time:

```php
public function test_we_create_a_url_record()
{
    $this->withoutExceptionHandling();
    $url = 'https://www.google.com';
    $response = $this->post('/url', ['url' => $url]);
    
    $response->assertStatus(200);

    $this->assertDatabaseHas('urls', [
        'url' => $url
    ]);

    $row = Url::where('url', $url)->first();

    $response->assertSee($row->id);
}
```

If we run the test now we will see an error like so: `Failed asserting that 'success' contains "1".`, so lets add some code to return what we were hoping for to our `web.php` route.

```php
Route::post('/url', function(Request $request) {
    $url = Url::create([
        'url' => $request->input('url')
    ]);
    
    return $url->id;
});
```
All we've done here is assigned the newly created row to a variable and returned its `id`. Now if we run the test for the last time we should see success, but... of course, we do not. 
```sh
Failed asserting that '3' contains "1".
```
What this is telling me is that we returned the `id` 3 but were expecting `id` 1. When we think logically, we have run the test 3 times and created 3 Urls. So our database is not being emptied each time, looking through the Laravel [documentation](https://laravel.com/docs/8.x/database-testing#resetting-the-database-after-each-test) one more time we can see we are missing something to clear out our database after every test. We need to add `Illuminate\Foundation\Testing\RefreshDatabase` to our test for it to clear our database after every test. In general I always want this functionality for all of my tests so instead of putting it in every test I'm going to put it in our base test class `tests/TestCase.php`:

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
}
```
And now when I run my tests one more time it is working as expected. We now have an endpoint that generates a `url` model and returns the `id`!

## Creating our second test and endpoint

Now we need one more endpoint for our application which will redirect users to their desired destinations. We are going to create another test in the same `tests/UrlTest.php` file. We want to assert that if you visit `/url/{id}` it redirects the user to the expected url. In Laravel's [testing documentation](https://laravel.com/docs/8.x/http-tests#assert-redirect) we can see that there is a `assertRedirect` which will be exactly what we need:

```php
public function test_we_redirect_users_to_url()
{
    $url = Url::create(['url' => 'https://www.google.com']);

    $response = $this->get("/url/$url->id");
    $response->assertRedirect($url->url);
}
```
If we run this test it is going to fail, due to the route not existing, this one is not going to take many steps to get just right though. Due to something called [implicit route model binding](https://laravel.com/docs/8.x/routing#route-model-binding) this next step is going to be incredibly easy! By just creating a route like the following:

```php
Route::get('/url/{url}', function (Url $url) {
    return redirect($url->url);
});
```
Directly from the documentation you can see:
> Laravel automatically resolves Eloquent models defined in routes or controller actions whose type-hinted variable names match a route segment name.

So we immediately have access to the model through said route binding. By returning a redirect to that model's `url` property we now have a passing test! This is a great time to take a step back and see your action in work. Although we don't have any UI to make urls, lets go ahead and create one in the database manually. You can either use some sort of OS native MySQL client, your CLI or you can use Laravel's [tinker](https://laravel.com/docs/8.x/artisan#tinker) REPL. In this case we are going to try out the REPL:

```sh
php artisan tinker
>>> use App\Models\Url;
>>> Url::create(['url'=>'https://www.google.com']);
=> App\Models\Url {#4219
     url: "https://www.google.com",
     updated_at: "2021-03-21 22:10:11",
     created_at: "2021-03-21 22:10:11",
     id: 1,
   }
>>> exit
```
we now can look in our database and we can see that we have a row in our database with an id of 1. If we now start our server: 
```sh
php artisan serve
Starting Laravel development server: http://127.0.0.1:8000
[Sun Mar 21 18:11:36 2021] PHP 7.4.13 Development Server (http://127.0.0.1:8000) started
```
and visit `http://127.0.0.1:8000/url/1` we will be redirected to google! We now have almost a full MVP of our url shortener, all that is left is adding the frontend code.

## Creating the basic frontend
The first thing we are going to need to do is create a basic page with a form to submit urls. We are going to create a new view to let users submit their urls.

The first thing we are going to have to do is create a new file `views/urls/create.blade.php`. We are going to put some basic html in the blade:

```html
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width">
        <title>Shorten a link</title>
    </head>
    <body>
        It works!
    </body>
</html>
```

we are now going to hook this up to our root url by adjusting `web.php` to go from rendering the `welcome` view to our new `urls.create`:

```php
Route::get('/', function () {
    return view('urls.create');
});
```

While we are in here we are also going to add a name to our route we use to create our urls like so:
```php
Route::post('/url', function(Request $request) {
    $url = Url::create([
        'url' => $request->input('url')
    ]);

    return $url->id;
})->name('create');
```
Named routes give a nice benefit of both resolving full paths automatically as well as remaining constant even if the url changes. It will help us with our form creation. If we refresh `localhost:8000` now we should see our "It works!" in the browser. Next we just need to add a form that will post to our desired endpoint.

```html
<!-- views/urls/create.blade.php -->

<body>
    <form method="POST" action="{{ route('create') }}">
        @csrf
        <label for="url-input">URL:
            <input id="url-input" type="text" name="url">
        </label>
        <button>Submit</button>
    </form>
</body>
```

After we have done this we should now successfully be able to create a shortened url; however the experience is less than ideal right now. Ideally we want to be redirected back to the original page with a success message and the url for the shortened link.

To do this we need to change our post endpoint from returning data directly to redirecting back to our root url with some basic data. We are going to need to use Laravel's [redirect with session data](https://laravel.com/docs/8.x/responses#redirecting-with-flashed-session-data):

```php
// web.php
Route::get('/', function () {
    $urlId = session()->get('urlId');
    return view('urls.create', ['urlId' => $urlId]);
})->name('home');

Route::post('/url', function(Request $request) {
    $url = Url::create([
        'url' => $request->input('url')
    ]);

    return redirect(route('home'))->with(['urlId' => $url->id]);
})->name('create');
```

with these updated routes we are now redirecting the user back to the root url where the user submitted their url with the id of their submitted url stored in session data. We have named our root url as `home` and we are getting that data and passing it to the view with the following code:

```php
$urlId = session()->get('urlId');
return view('urls.create', ['urlId' => $urlId]);
```

If we run our tests again at this point they will now not all pass. We have changed the expected effect of our create url endpoint and we should update our test to reflect it. We still want to ensure that a new row is created in the database, but we now want to ensure we are redirected to the correct place and that the correct data is passed in the user's session.

```php
// tests/Feature/UrlTest.php

public function test_we_create_a_url_record()
{
    $url = 'https://www.google.com';
    $response = $this->post('/url', ['url' => $url]);

    $this->assertDatabaseHas('urls', [
        'url' => $url
    ]);

    $row = Url::where('url', $url)->first();
    
    $response->assertRedirect(route('home'));
    $response->assertSessionHas(['urlId' => $row->id]);
}
```

if we now add to our `create.blade.php` the following we should see some data after creating a url:

```html
@if ($urlId)
    <p>{{ $urlId }}</p>
@endif
```

If we have done everything right we should now see the following:

<img :src="$withBase('/05_website_mvp_pre.png')" alt="website mvp">

We are getting very close, we now have the id but to an end user this is not a great experience, we really want to display to the user the full url they need to get their redirect working. We are going to need one last named route:

```php
// web.php
Route::get('/url/{url}', function (Url $url) {
    return redirect($url->url);
})->name('shortened');
```

And with the `shortened` name for that route we can use the second parameter of the `route()` function to pass in an id and form a full url:

```html
@if ($urlId)
    <p>{{ route('shortened', ['url' => $urlId]) }}</p>
@endif
```
If we go through the process one more time we should now see a fully formed url! Congratulations, we have our most basic MVP of our product. It is not pretty but it is completely functional, its possible going through this process has piqued some ideas about other features or shortcomings of what we currently have. 

## What's next

There are few items that came to my mind while going through this process, the first and most glaring being that our shortened urls are just numbers. Here is a list of a few things that come to mind immediately:

1. There is no styling, basically our site is very ugly
2. Shortened urls are just numbers and will not stay short for nearly as long as if letters were included.
3. There is no validation when submitting a url
4. It would be great to allow for custom vanity urls
5. Would be nice to be able to have a user and see all redirects created
6. Would be nice to be able to see how many people have visited your shortened link