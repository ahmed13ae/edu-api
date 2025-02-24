<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|string|unique:users,email',
            'password' => 'required|string|max:255|min:6|confirmed'
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password'])
        ]);
        return response()->json(["message" => "registerd successfully!", "user" => $user], 201);
    }
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);
        if (!Auth::attempt($validated)) {
            return response()->json(["message"=>"invalid email or password!"],401);
        } 
        $user=User::where('email',$validated['email'])->firstOrFail();
        $token=$user->createToken('auth_token')->plainTextToken;
        return response()->json(["message" => "Logged in!", "user" => $user,"token"=>$token], 200);
    }
    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(["message" => "Logged out!"], 200);
    }
}
