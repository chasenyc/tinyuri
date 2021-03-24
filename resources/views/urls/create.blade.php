<!-- views/urls/create.blade.php -->
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width">
        <title>Shorten a link</title>
    </head>
    <body>
        <form method="POST" action="{{ route('create') }}">
            @csrf
            <label for="url-input">URL:
                <input id="url-input" type="text" name="url">
            </label>
            <button>Submit</button>
        </form>

        @if ($urlId)
            <p>{{ route('shortened', $urlId) }}</p>
        @endif
    </body>
</html>