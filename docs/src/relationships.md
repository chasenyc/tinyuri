# Relationships

Now we are going to create our first relationship between two models. The goal of this is to be able for a user to track their shortened urls and see them all in once place. Eventually maybe we will even enable them to see some statistics such as how many times the url was visited.

The first thing we want to do is determine what [type of database relation](https://en.wikipedia.org/wiki/Cardinality_(data_modeling)#Application_program_modeling_approaches) this is. In this case we have a `user` who has many `urls` so we have a [one to many relationship](https://laravel.com/docs/8.x/eloquent-relationships#one-to-many). What this means is that we want to put a new column on our `urls` table referencing what user it belongs to. In our case we actually don't want to make this a strict requirement though, what we mean by that is we want a person to visit the site and create a shortened url without having to login, they just wont be able to see a page with all of their urls. So in this case we want to make a new database migration adding a `nullable` `user_id` on our `urls` table. 

## Database definition

Let's go ahead and make a new migration to add this new column:

```sh
php artisan make:migration add_user_id_to_urls_table
```

And lets add a nullable foreign key constraint:

```php
public function up()
{
    Schema::table('urls', function (Blueprint $table) {
        $table->foreignId('user_id')->nullable()->constrained('users');
    });
}

public function down()
{
    Schema::table('urls', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropColumn('user_id');
    });
}
```

And once we run this migration, everything should work the same, all of our tests should still pass, we should be able to go to the site and create a url, either logged in or logged out.

## Defining model relationships

Next up lets add the [one to many](https://laravel.com/docs/8.x/eloquent-relationships#one-to-many) relationship to our models. We want to add a `hasMany` to `User` model and a `belongsTo` to our `Url` model.

```php
// app/models/User.php

/**
 * Get the urls a user has created.
 */
public function urls()
{
    return $this->hasMany(Url::class);
}
```

and in our `Url` model:

```php
// app/models/Url.php

/**
 * Gets the owner of this url
 */
public function user()
{
    return $this->belongsTo(User::class);
}
```

Now that we have defined the relationships we want both on the databse and the ORM level, lets create a test asserting that when a logged in user creates a url that it is tied to that user.

```php
public function test_a_logged_in_user_has_url_tied_to_them()
{
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
```

## Associating our models

Now that we have a test describing what we want to occur, let's go ahead and make that happen. We just want to associate the `User` to a `Url` if a user exists. Let's take a look at our `UrlController` and add some logic:

```php
public function store(StoreUrlRequest $request)
{
    $url = Url::create([
        'url' => $request->input('url'),
    ]);

    $url->user()->associate(Auth::user());
    $url->save();

    return redirect(route('home'))->with(['urlId' => $url->base62id()]);
}
```

If we look at the documentation it states you can [add a belongs to relationship](https://laravel.com/docs/8.x/eloquent-relationships#updating-belongs-to-relationships) by calling `associate` on the `belongsTo` relationship and then calling `save()`. If we run our tests again we will see that our tests now pass. If we login to our app and create a new url we will see a `user_id` stored in the database.

## Create vs Make

Before we move on to our next steps, we've done something sub-optimal here which is we create a url record in the database and then we have to update that record a second later. Using create will instantly insert the data into the database, but there is another option, to use the `make` function instead. This will create a new model in memory but not immediately persist it. If we do this, we won't save it until we have finished our logic of adding a `user_id` to the model. Let's update our logic:
```php
$url = Url::make([
    'url' => $request->input('url'),
]);
```

and run our tests one more time to make sure everything works as expected.

## Viewing these relationships

Next we should make a place where a user can see all the urls they have shortened. Let's define the expected outcome in a new test.

```php
public function test_a_user_can_see_all_of_their_urls()
{
    $user = User::factory()->create();

    $urls = Url::factory()->count(10)->create(['user_id' => $user->id]);
    $this->actingAs($user);

    $response = $this->get(route('user.urls'));

    $response->assertStatus(200)
        ->assertSeeText(route('shortened', $urls->last()->base62id()));
}
```

This test will obviously fail because we have not done any of the work yet, but to go over what this test is doing, we are setting up a state where we have a user, who has created ten urls. Visiting a named route, which we have yet to define, and are going to assert that on the page we see the last url's url visible.

Lets start by making a new controller:
```sh
php artisan make:controller UserUrlsController
```

and then we will add some very basic logic in here to pass all of a authenticated user's associated urls into the view:

```php
<?php

namespace App\Http\Controllers;

class UserUrlsController extends Controller
{
    public function index()
    {
        return view('user.urls-index', ['urls' => Auth::user()->urls]);
    }
}
```

And next we are going to want to create a new view in our `resources/views/user` folder named `urls-index.blade.php`. Let's do the bare minimum first and just get all of those urls on the page:

```html
<ul>
@foreach($urls as $url) 
    <li>
        <div>
            Full Url: <a href="{{ $url->url }}">{{ $url->url }}</a>
        <div>
            Short url: <a href="{{ route('shortened', $url->base62id()) }}">{{ route('shortened', $url->base62id()) }}</a>
        </div>
    </li>
@endforeach
</ul>
```

If we re-run the tests now we should see our test succeeding, and we should see something like the following if we visit `/links` while logged in:

<img :src="$withBase('/11_user_urls_basic.png')" alt="wireframe">

With a little styling we can make this look a little nicer, nothing too fancy but at least presentable. Lets also add the same layout we use for the main create page so users can logout or return home:

```html
<!-- views/user/urls-index.blade.php -->
@extends('layouts.app')

@section('title', 'Create')

@section('content')
<div class="w-full">
    <h1 class="text-4xl text-center pt-6">Your links</h1>
    <ul class="">
    @foreach($urls as $url) 
        <li class="border rounded shadow m-2 p-2 hover:bg-gray-50 cursor-pointer">
            <div class="overflow-ellipsis overflow-hidden truncate">
                <a class="hover:underline" href="{{ $url->url }}">{{ $url->url }}</a>
            <div>
                <span class="font-semibold">Shortened:</span> <a class="text-green-500 hover:underline" href="{{ route('shortened', $url->base62id()) }}">{{ route('shortened', $url->base62id()) }}</a>
            </div>
        </li>
    @endforeach
    </ul>
</div>
@endsection
```

<img :src="$withBase('/12_styled_links.png')" alt="wireframe">

Lastly we need a way to navigate to a user's links page so lets add that right before the logout link in our `app.blade.php`:

```html
<li class="pr-5 underline">
    <a href="{{ route('user.urls') }}">Links</a>
</li>
```