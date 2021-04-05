# Authentication

## Planning

Adding users and authentication is a slightly more complex task, although most modern frameworks, [Laravel included](https://laravel.com/docs/8.x/authentication#authentication-quickstart), give a lot of the basics to use right out of the box. There are a few options that would require almost no work besides requiring a package to have working user auth. We are going to do a slightly more hybrid approach, utilizing some of the core `Auth` features given to us but not a one line dependency solution.

In order to implement this feature we obviously need a table for users and then a way to tie users to the urls they have created. We can do this simply by adding a new column to our `urls` table: `user_id`. With this column a url now potentially has an owner. Before we jump in we do have a product question to ask, do we want to enforce that a user is logged in to create a shortened link or should we allow guests to create shortened links. I think it makes for a nicer service to allow anyone to create a link so that informs some important design decision, should this new column on the `urls` table be nullable or not.

You may have noticed when we ran out first migration that Laravel already comes with a users table in a fresh install. Let's take a peak at that table and see what we get from it out of the box. You can see the migration in your `database/migrations/2014_10_12_0000000_create_users_table.php`:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
```

So looking at this table a few things jump out, there is a `name`, `email`, `email_verified`, `password`, [`remember_token`](https://laravel.com/docs/8.x/migrations#column-method-rememberToken), and some `created_at` and `updated_at` timestamps. For our application we don't really need a user to have both an email and a name, it would be fine for them to login with their email and password. It looks like currently `name` is required since it does not have `->nullable()` after it. Besides that it seems like we should be okay with everything else. We probably wont initially be verifying email addresses but there is no harm in leaving it there for a future feature.

## Altering tables

We seem to have two tables we would like to alter, let's start with with the users table and getting rid of the name column. Let's go ahead and make a migration to alter the table:

```sh
php artisan make:migration drop_name_column_from_users_table
```

And let's add the following to our new migration file:

```php
/**
 * Run the migrations.
 *
 * @return void
 */
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('name');
    });
}

/**
 * Reverse the migrations.
 *
 * @return void
 */
public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('name');
    });
}
```

Now once we run the migration we should see our users table no longer has a name column.

## Auth endpoints

We are going to next go ahead and make some new endpoints to register a user and an endpoint to log in a user. Like always lets start with some new feature tests. 

```sh
php artisan make:test UsersTest
```

Now lets start with the register endpoint. We want a user to be able to send an email address and password, have that user be stored in the database and the user is now authenticated:

```php
public function test_users_can_register()
{
    $userData = [
        'email' => 'fake@email.com',
        'password' => 'password1234'
    ];

    $response = $this->post('/register', $userData);

    $this->assertDatabaseHas('users', [
        'email' => $userData['email']
    ]);

    $this->assertAuthenticated();
}
```

If we run this we will see that the first error is that there is no row in the database. If we add `$this->withoutExceptionHandling();` to the top of the test we see the first real error is that the route does not exist. Let's go ahead and first make our controller:

```sh
php artisan make:controller RegisterController
```

Lets start with a store function, we will start with a vanilla `Illuminate\Http\Request` object and then eventually once we have everything worked out move to another form request.

```php
public function store(Request $request)
{
    $request->validate([
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|confirmed|min:8',
    ]);

    Auth::login($user = User::create([
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]));

    return redirect(route('home'));
}
```

Here we are validating that there is an email address, it is not already in the users table, and there is a password with a minimum of 8 characters.

```php
<?php

namespace App\Http\Controllers;

use Auth, Hash;
use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        Auth::login($user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]));

        return redirect(route('home'));
    }
}
```

and then let's add the accompanying route:

```php
Route::post('/register', [RegisterController::class, 'store'])->name('register');
```

If we run the tests again they should now pass. Let's go ahead and make one small change to our test to reference the named route instead of the url directly.

Lastly, we are going to need to create a view for users to register from. Let's make a new folder in our `resources/views` folder named `auth` and make a `register.blade.php` Let's go ahead and copy and paste our whole `urls/create.blade.php` in there so we have a starting point. Then we just need to update our form and title in some minor ways.

```html
<!-- views/auth/register.blade.php -->
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>tinyuri - register</title>
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    </head>
    <body>
        <div class="w-1/3 mx-auto">
            <h1 class="text-4xl text-center pt-6">register</h1>
            <form class="flex flex-col" method="POST" action="{{ route('register') }}">
                @csrf
                <label class="uppercase mt-4" for="email-input">email:
                    <input class="my-2 w-full border py-2 px-3 text-grey-darkest rounded" id="email-input" type="text" name="email">
                </label>
                @error('email')
                    <div class="error message">{{ $message }}</div>
                @enderror
                <label class="uppercase mt-4" for="email-input">password:
                    <input class="my-2 w-full border py-2 px-3 text-grey-darkest rounded" id="email-input" type="password" name="password">
                </label>
                @error('password')
                    <div class="error message">{{ $message }}</div>
                @enderror
                <button class="btn btn-black">Submit</button>
            </form>

        </div>
    </body>
</html>
```

After that, let's add the controller method and route:

```php
// app/Http/Controller/RegisterController.php

public function create()
{
    return view('auth.register');
}

// routes/web.php
Route::get('/register', [RegisterController::class, 'create'])->name('register.create');
```

Now if we go to our site `/register` we should see the following:

<img :src="$withBase('/10_register_view.png')" alt="register view">

If we fill out the form we can see a we are directed to the url creation page, if we put any kind of bad input or omit either field, we should see some error input for the user.

## Login flow

Next up, lets create the same type of flow for an already registered user. It would be great for you to pause reading through this and checkout the documentation and have a go at the whole section yourself. 

In this section we want to accomplish:
- A test covering logging in
- An endpoint to login
- A view with a form that will hit the login endpoint
- Once logged in, to be redirected to the `home` route.

You can see some documentation on how to achieve this [here](https://laravel.com/docs/8.x/authentication#authenticating-users). 

If you are more comfortable following along with this tutorial, lets start with the test as usual. We will pick back up in our `UsersTest.php`:

```php
public function test_users_can_login()
{
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
```

Obviously the test is not going to pass, lets make a new `LoginController`, see if you can find the command above or in the official documentation.

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect(route('home'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }
}
```

And lets add a route for this to our `web.php`:

```php
Route::post('/login', [LoginController::class, 'authenticate'])->name('login');
```

Our test should now pass. Once again we just need a view and route for the users. Let's make a new `login.blade.php` in our `auth` views folder. We can pretty much copy and past all of our html from `register.blade.php` and make a few minor tweaks.

```html
<!-- views/auth/login.blade.php -->
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>tinyuri - login</title>
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    </head>
    <body>
        <div class="w-1/3 mx-auto">
            <h1 class="text-4xl text-center pt-6">login</h1>
            <form class="flex flex-col" method="POST" action="{{ route('login') }}">
                @csrf
                <label class="uppercase mt-4" for="email-input">email:
                    <input class="my-2 w-full border py-2 px-3 text-grey-darkest rounded" id="email-input" type="text" name="email">
                </label>
                @error('email')
                    <div class="error message">{{ $message }}</div>
                @enderror
                <label class="uppercase mt-4" for="email-input">password:
                    <input class="my-2 w-full border py-2 px-3 text-grey-darkest rounded" id="email-input" type="password" name="password">
                </label>
                <button class="btn btn-black">Submit</button>
            </form>

        </div>
    </body>
</html>
```

Now we need a controller function and web route to map to that function. Go ahead and try to fill those in without looking at the code below.

```php
// LoginController.php
public function create()
{
    return view('auth.login');
}

// web.php
Route::get('/login', [LoginController::class, 'create'])->name('session.create');
```

We should now have a functioning login! This is pretty hard to test manually at the moment because we have no acknowledgement in our UI of a logged in state. We also are able to visit login even when we are already logged in. Let's fix those problems right now.

First let's make a quick test to assert what we are hoping for in our `UsersTest.php`:

```php
public function test_logged_in_users_cannot_view_login_or_register()
{
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('session.create'));
    $response->assertRedirect(route('home'));

    $response = $this->get(route('register.create'));
    $response->assertRedirect(route('home'));
}
```

## Middleware

The first thing we are going to do is make it so you cannot visit the login or register page if you are currently logged in. Laravel comes with some middleware already that will do this for us but let's actually take the time to make our own check. The documentation from laravel provides a pretty good explanation of middleware:

> Middleware provide a convenient mechanism for inspecting and filtering HTTP requests entering your application. For example, Laravel includes a middleware that verifies the user of your application is authenticated. If the user is not authenticated, the middleware will redirect the user to your application's login screen. However, if the user is authenticated, the middleware will allow the request to proceed further into the application.

There are two types of middleware, actions that are performed before the request is completed and actions that are performed after the request is completed. In this case we want to check if the user is logged in before the request is completed. Let's go ahead and take our first step by making a new middleware file.

```sh
php artisan make:middleware EnsureNotAuthed
```

Let's open this new file `app/Http/Middleware/EnsureNotAuthed`. First, we are definitely going to need access to `Auth`, so lets add that to the `use` statement up top:

```php
<?php

namespace App\Http\Middleware;

use Auth, Closure;
use Illuminate\Http\Request;

class EnsureNotAuthed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        
        return $next($request);
    }
}
```

If we wanted to perform an action *after* the request was handled we would do something like this:

```php
$response = $next($request);

// Perform action

return $response;
```

But in this case we want to perform an action before the request is handled. Basically we want to check if there is an `Auth`'d user, and if there is, let's redirect them to the url generation page for now.

```php
public function handle(Request $request, Closure $next)
{
    if (Auth::user()) {
        return redirect(route('home'));
    }
    
    return $next($request);
}
```

Next up we need to add this middleware, with a name for it to our `app/Http/Kernel.php`:

```php
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'unauthed' => EnsureNotAuthed::class,
        // ...
```

And lastly we need to create a new route group in our `web.php` and move all of our authentication routes within it:

```php
Route::middleware(['unauthed'])->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register.create');
    Route::post('/register', [RegisterController::class, 'store'])->name('register');
    Route::get('/login', [LoginController::class, 'create'])->name('session.create');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login');
});
```

Now if we try to access login, while logged in, it will immediately redirect us to the home route and our tests should now all be passing.

## Logging out and Layouts

If you havent noticed playing around with login, its hard to test it more than once since we do not have a way to logout or a good way to see when we are logged in. Ideally we would like to have a link or two at the top for logging in or registering when we are not logged in, and a log out button for when we are logged in.

If you've noticed we have been repeating a lot of html code between our few views. We want to have a consistent nav bar on the top of all of our pages. This will also help us see whether we are logged in our not. Let's create our layout:

```html
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Tinyuri - @yield('title')</title>
        @yield('head')
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    </head>
    <body>
        <div class="w-full lg:w-1/3 mx-auto">
            <div class="mx-2">
                @yield('content')
            </div>
        </div>
    </body>
</html>
```

now if we update `resources/views/urls/create.blade.php`:

```html
<!-- views/urls/create.blade.php -->
@extends('layouts.app')

@section('title', 'Create')

@section('content')
    <div>
        <h1 class="text-4xl text-center pt-6">tinyuri</h1>
        <form class="flex flex-col" method="POST" action="{{ route('create') }}">
            @csrf
            <label class="uppercase mt-4" for="url-input">submit a url:
                <input class="my-2 w-full border py-2 px-3 text-grey-darkest rounded" id="url-input" type="text" name="url">
            </label>
            <button class="btn btn-black">Submit</button>
        </form>

        @if ($urlId)
            <p class="success message">{{ route('shortened', ['url' => $urlId]) }}</p>
        @endif

        @error('url')
            <div class="error message">{{ $message }}</div>
        @enderror
    </div>
@endsection
```

We should see the same view as before. Great, now lets add a very basic nav bar to the top of our page. Let's add some code at the top of our `<body>`:

```html
<nav class="flex justify-between p-2">
    <div>
        <a class="text-lg" href="{{ route('home') }}">Tinyuri</a>
    </div>
    <ul class="flex flex-row">
        @if (!Auth::check())
        <li class="pr-5 underline">
            <a href="{{ route('login') }}">Login</a>
        </li>
        <li class="pr-5 underline">
            <a href="{{ route('register') }}">Register</a>
        </li>
        @else
        <li class="pr-5 underline">
            Logout
        </li>
        @endif
    </ul>
</nav>
```

Once we have added this we should be able to see a our current logged in or logged out state. Take a minute to checkout the page with this new nav bar, then let's use this globally throughout all of our pages (login/register).


## Logout
We don't yet have any logic for logging out. Before we add that logic we should make a small test to assert the functionality we are hoping for:

```php
public function test_logout_removes_authenticated_status()
{
    $this->actingAs(User::factory()->create());

    $this->assertAuthenticated();
    $this->post(route('logout'));
    $this->assertGuest();
}
```

Basically all we are doing here is using `actingAs` to state that we are logged in as this user. For extra explicitness we are asserting that before we hit the logout endpoint we are authenticated. Then we hit the logout endpoint and expect that we now are a "guest" which [according to the documentation](https://laravel.com/docs/8.x/http-tests#assert-guest) is how we assert that a user is not authenticated. Now that we have a test that is failing lets go through and create everything we need to make this test pass. Let's add a new method to our `LoginController`:

```php
public function destroy(Request $request)
{
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect(route('home'));
}
```

and let's add the logout to the routes:

```php
// web.php
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
```

If we run our tests again, everything should pass now. Now let's replace the empty logout nav item with some actual logic so that our users can logout, not just in our tests!

```html
<form method="POST" action="{{ route('logout') }}">
    @csrf

    <a href="route('logout')"
            onclick="event.preventDefault();
                        this.closest('form').submit();">
        Log out
    </a>
</form>
```

Now that we are able to login and logout let's actually use this authenticated user for something. In the next section we will be working on tying shortened urls to a user and displaying them all to the authenticated user.