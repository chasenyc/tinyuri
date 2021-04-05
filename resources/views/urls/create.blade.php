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