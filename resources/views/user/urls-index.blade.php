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