<?php

namespace App\Http\Controllers;

use Auth, Hash;
use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

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
