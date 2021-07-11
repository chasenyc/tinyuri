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
                    <a href="{{ route('user.urls') }}">Links</a>
                </li>
                <li class="pr-5 underline">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <a href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            Log out
                        </a>
                    </form>
                </li>
                @endif
            </ul>
        </nav>
        <div class="w-full lg:w-1/3 mx-auto">
            <div class="mx-2">
                @yield('content')
            </div>
        </div>
    </body>
</html>