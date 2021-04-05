# Base styling

In this section we are just going to be adding the most basic stying to our website. We are not going to be doing any major designs or anything fancy but we want to just make the site look a little more like an actual site

## Using a css framework

There are a bunch of potential different css frameworks you can choose from and for the most part you are able to achieve everything regardless of the chosen framework. For this project we are going to use [TailwindCSS](https://tailwindcss.com). We are setting this up for a Laravel project and fortunately they have a [setup guide](https://tailwindcss.com/docs/guides/laravel) for working with Laravel. Instead of going through all of the steps here just refer to that guide to setup tailwind within your project.

## Add our first styling to the page

In our `create.blade.php` we are going to adjust our `<head>` section like so:

```html
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>tinyuri</title>
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    </head>
```
What we are doing here is adding the stylesheet using the [asset()](https://laravel.com/docs/8.x/helpers#method-asset) helper to link to our css file.

Next we are going to add a title to the top of the body of our file like so:
```html
<body>
    <h1 class="text-4xl text-center pt-6">tinyuri</h1>
    <!-- ... -->
</body>
```

In a separate terminal window we are going to run `npm run watch` this way as we make any changes to our compiled assets they will recompile. If we now navigate to the the root page we should see a styled title.

## Computed styles in tailwind

Next we are going to style our submit button:

```html
<button class="font-bold py-2 px-4 rounded bg-black text-white">Submit</button>
```

This gives us a nice basic style for out button; however, we are probably going to have more than one button in our website so instead of having to repeat all of these classes in every button and having to worry about adjusting the style of buttons everywhere through out code base we are going to move this code to a computed class. Let's open up out `resources/css/app.css` and add the following:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

.btn {
    @apply font-bold py-2 px-4 rounded;
}

.btn-black {
    @apply bg-black text-white;
}

.btn-black:hover {
    @apply bg-gray-700;
}
```

now that we have `btn` and `btn-black` as classes lets change our button to the following:
```html
<button class="btn btn-black">Submit</button>
```

We are going to add a little more styling to the form itself to get it to a presentable state, mainly adding some vertical padding and centering the content on the page.

```html
<div class="mx-auto w-1/3">
    <form class="flex flex-col" method="POST" action="{{ route('create') }}">
        @csrf
        <label class="uppercase mt-4" for="url-input">submit a url:
            <input class="my-2 w-full border py-2 px-3 text-grey-darkest rounded" id="url-input" type="text" name="url">
        </label>
        <button class="btn btn-black">Submit</button>
    </form>
</div>
```

Now the site should look something like this:

<img :src="$withBase('/06_styled_basic.png')" alt="styled basic">

Lastly we are going to to want to style the newly generated short urls:

```html
<p class="my-2 font-medium py-1 px-2 bg-white rounded-md text-green-700 bg-green-100 border border-green-300">{{ route('shortened', ['url' => $urlId]) }}</p>
```

This also seems like a pretty good candidate for being pulled out into a computed class. That's up to you if you want to make a class like `success-message`.

<img :src="$withBase('/07_success_styled.png')" alt="success message styled">

