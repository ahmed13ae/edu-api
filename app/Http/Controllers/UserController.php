<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Register a new user",
     *     operationId="register",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email", "password", "password_confirmation"},
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="secret123"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password", example="secret123"),
     *                 @OA\Property(property="image", type="file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/login",
     *     summary="User login",
     *     description="Authenticate user and return access token",
     *     operationId="loginUser",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"email", "password"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     example="user@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     format="password",
     *                     example="password123"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged in!"),
     *             @OA\Property(property="token", type="string", example="1|longTokenHere123"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid email or password"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Something went wrong"
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Logout the authenticated user",
     *     operationId="logout",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
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
