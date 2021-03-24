# Creating a URL Shortener

## Setting up a project
### Getting our server up and running

The first thing we are going to want to do is setup a project locally and add some version control to it. For this project we are going to be using [Laravel](laravel.com) as our framework of choice. The first step here is to install everything necessary to create a new Laravel project. Its always recommended to familiarize yourself with a frameworks [documentation](laravel.com/docs/8.x).  For Laravel there are a few prerequisites which are to have PHP and Composer installed. Once those are installed we are going to go ahead and run the following: 
```sh
composer global require laravel/installer

laravel new tinyuri

cd tinyuri

php artisan serve
```

Once we do that we should see terminal output like the following:
`Starting Laravel development server: http://127.0.0.1:8000` and if we navigate there we should see our server up and running!

{{screenshot01}}

### Adding version control

The next step is to add some version control to our project so we can keep track of changes and implement features on separate branches. For this we will be using [Github](github.com) but there are plenty of other options. We are going to create a GitHub repository with the same name as our project, tinyuri:

{{screenshot02}}

Once we create the repository we are going to see some instructions on how to add git to our project:

```sh
git init
git add .
git commit -m "first commit"
git branch -M main
git remote add origin {{your url here}}
git push -u origin main
```

A new Laravel project comes with a README.md so we are removing that step from what we need to do.  After we have finished these steps we should now be able to refresh our GitHub repository and see the Laravel Skeleton application code.

## MVP Designs
Before we hope straight into coding it is important to first figure out what we are trying to create and how we might want to achieve those goals. I find the best way to do some basic system design is to start with the end user MVP state and work your way from the user interactions to the API supporting it to the underlying data models.

With a URL shortener there are two immediate interactions that jump out as crucial MVP features. The ability to create a shortened url and the ability to visit a shortened url and be redirected to the correct location.

### Wireframe

{{screenshot03}}

The most basic version of this would be a one page application that you can submit a url to an input box, click submit and below it will render a shortened url. Beyond that there is no need to wireframe a redirect but we can imagine if we visit our base domain with a `/{id}` we should be redirected to that url that the user submitted. 

### API Design

There should be two real endpoints for the MVP of this url shortener. An endpoint to create a shortened url and an endpoint that looks up shortened urls are redirects users to the full url. It seems like we need a `POST /url` endpoint that takes in one parameter which is a `url`  and returns an object with the url and a shortened id for that url.

The second endpoint that redirects is just a catch-all that will look up any id and redirect a user to the correct stored url.

### Data modeling

{{screenshot04}}

The most basic data modeling we need seems quite simple! All we need to store is the url the user submits and a unique identifier for that url which is just the shortened url we are using to uniquely identify urls and figure out where to redirect them to. For the most basic of basic MVPs we are just going to use a number to uniquely identify then, When you visit tinyuri.to/1 we need be able to look up the user submitted url by `1`.  MySQL by default will give us everything we need with an auto-incrementing primary key field `id`. It will ensure this field is always unique and that it is indexed for quick lookup.  

### Documenting designs

The now that I have the most basic version of my wireframe, API, and data modeling designs thought out and drawn up I want to make sure I keep these designs readily available and can continue to work on them. I’m going to create a new folder in my repository at the root level called `docs`:
```sh
mkdir docs
cd docs
```
And then I’m going to put a second folder within that one for my designs:
```sh
mkdir designs
cd designs
```
And I’m going to flesh out some basic documentation of the designs mentioned above. Please refer to above folders to see where the designs are being stored.

## Getting Started with Code
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

### Creating a test

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

### Creating our second test and endpoint

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

### Creating the basic frontend
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