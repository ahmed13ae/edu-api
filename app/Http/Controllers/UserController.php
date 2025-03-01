<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;

class UserController extends Controller
{
    /**
     * Register a new user.
     *
     * @param Request $request The HTTP request containing user registration data.
     * @return \Illuminate\Http\JsonResponse The response with a success message and user data.
     * 
     * @throws ValidationException If validation fails.
     * @throws Exception If an unexpected error occurs.
     */
    public function register(Request $request)
    {
        try {
            // Validate user input
            $validated = $request->validate([
                'name' => 'required|string|min:3|max:255',
                'email' => 'required|email|string|unique:users,email',
                'password' => 'required|string|max:255|min:6|confirmed',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',               
            ]);
            if ($request->hasFile('image')) {
                $path=$request->file('image')->store('images','public');
                $validated['image']=$path;
            }

            // Create a new user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' =>  'user',
                'image' => $validated['image']
            ]);

            return response()->json(["message" => "Registered successfully!", "user" => $user], 201);
        } catch (ValidationException $e) {
            return response()->json(["message" => "Validation failed!", "errors" => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(["message" => "Something went wrong!", "error" => $e->getMessage()], 500);
        }
    }

    /**
     * Authenticate a user and generate an access token.
     *
     * @param Request $request The HTTP request containing login credentials.
     * @return \Illuminate\Http\JsonResponse The response containing the authentication token.
     * 
     * @throws ValidationException If validation fails.
     * @throws Exception If an unexpected error occurs.
     */
    public function login(Request $request)
    {
        try {
            // Validate login credentials
            $validated = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string'
            ]);

            // Attempt authentication
            if (!Auth::attempt($validated)) {
                return response()->json(["message" => "Invalid email or password!"], 401);
            }

            // Retrieve authenticated user and generate token
            $user = User::where('email', $validated['email'])->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json(["message" => "Logged in!", "user" => $user, "token" => $token], 200);
        } catch (ValidationException $e) {
            return response()->json(["message" => "Validation failed!", "errors" => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(["message" => "Something went wrong!", "error" => $e->getMessage()], 500);
        }
    }

    /**
     * Logout the authenticated user by revoking their current access token.
     *
     * @param Request $request The HTTP request containing authentication details.
     * @return \Illuminate\Http\JsonResponse The response confirming logout.
     * 
     * @throws Exception If an unexpected error occurs.
     */
    public function logout(Request $request)
    {
        try {
            // Revoke user's current access token
            $request->user()->currentAccessToken()->delete();
            return response()->json(["message" => "Logged out!"], 200);
        } catch (Exception $e) {
            return response()->json(["message" => "Something went wrong!", "error" => $e->getMessage()], 500);
        }
    }
}
