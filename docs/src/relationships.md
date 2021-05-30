# Relationships

Now we are going to create our first relationship between two models. The goal of this is to be able for a user to track their shortened urls and see them all in once place. Eventually maybe we will even enable them to see some statistics such as how many times the url was visited.

## Defining the relationship

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

## ORM Definition

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