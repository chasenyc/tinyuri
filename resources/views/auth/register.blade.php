<!-- views/auth/register.blade.php -->
@extends('layouts.app')

@section('title', 'register')

@section('content')
    <div>
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
@endsection