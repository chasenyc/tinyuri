# Refactoring

## Adding some validations
Now that we have implemented an MVP and added our first feature we should make a small addition to our application to validate that our user submitted urls are actually urls. Our end goal is to show an error to the user if the submit a bad url. Let's start with a small test, we are going to add to our feature test.

```php
public function test_we_return_an_error_for_invalid_url()
{
    $url = 'google';
    $response = $this->post('/url', ['url' => $url]);
    $response->assertRedirect(route('home'));
    $response->assertSessionHasErrors(['url']);
}
```

Before we start implementing this logic, our routes file is starting to get a little busy. Let's try to move our logic out of the routes file(`web.php`) and into a controller. We can make our first controller like so:

```sh
php artisan make:controller UrlController
```

We'll start with adding a store function to the controller and copy our logic from `web.php` into this function:

```php
public function store(Request $request)
{
    $url = Url::create([
        'url' => $request->input('url')
    ]);

    return redirect(route('home'))->with(['urlId' => $url->base62id()]);
}
```

now we can update the route to the following:

```php
Route::post('/url', [UrlController::class, 'store'])->name('create');
```

If we run our tests they will still fail because we have yet to add any validations. So let's go ahead and add some validations within our Controller's store method:

```php
$request->validate([
    'url' => ['required', 'url', 'max:255'],
]);
```

If we run our tests again they will now pass. All thats left is to add some frontend logic to display this to our users.

```html
<!-- create.blade.php -->
@if ($urlId)
    <p class="success message">{{ route('shortened', ['url' => $urlId]) }}</p>
@endif

@error('url')
    <div class="error message">{{ $message }}</div>
@enderror
```

we now will display when there is an error returned from validation. We've gone ahead and made some computed styles for these messages that can be found in our `app.css`:

```css
.success {
    @apply  text-green-700 bg-green-100 border border-green-300;
}

.error {
    @apply text-red-700 bg-red-100 border border-red-300;
}

.message {
    @apply border my-2 font-medium py-1 px-2 rounded-md;
}
```
And if we now test a bad url with our server we should see the following:

<img :src="$withBase('/09_error_message.png')" alt="error message">

## Moving logic out of the controller

Now that we moved all of our logic out of the route file and into a controller, let's move that validation logic out of the controller to keep our controllers as small as possible. Laravel comes with robust form request validation that we are going to take advantage to isolate our request validation. Lets make a new form request:

```sh
php artisan make:request StoreUrlRequest
```

This is going to make a new file `app/Http/Requests/StoreUrlRequest.php`. Lets go ahead and use that file in our `UrlController`

```php
use App\Http\Requests\StoreUrlRequest;
```

and then in our store function lets change our method signature from a plain request to a `StoreUrlRequest` and remove the request validation from the function:

```php
public function store(StoreUrlRequest $request)
{
    $url = Url::create([
        'url' => $request->input('url')
    ]);

    return redirect(route('home'))->with(['urlId' => $url->base62id()]);
}
```
lastly within the `StoreUrlRequest` lets add the following:

```php
/**
 * Get the validation rules that apply to the request.
 *
 * @return array
 */
public function rules()
{
    return [
        'url' => ['required', 'url', 'max:255'],
    ];
}
```

Let's also remove the `authorize()` function for now as we are not utilizing any authorization for the moment. If we run the tests again they should still pass. We now have a specific file dedicated to validating user input, we have a lean controller and we have a better functioning product.

Next up, lets tackle creating users who can keep track of their shortened links.