<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
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

    /**
 * @OA\Post(
 *     path="/api/forgot-password",
 *     summary="Send password reset email",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Reset link sent to your email.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Reset link sent to your email.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Something went wrong. Please try again later.")
 *         )
 *     )
 * )
 */
        public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);
    
            // Generate token
            $token = Str::random(64);
    
            // Delete any existing tokens
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
    
            // Insert new token (hashed, as Laravel expects)
            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => hash('sha256', $token),
                'created_at' => Carbon::now(),
            ]);
    
            // Custom frontend reset link
            $resetLink = "https://frontend-app.com/reset-password?token={$token}&email=" . urlencode($request->email);
    
            // Send the reset link via email
            Mail::raw("Click the link to reset your password: $resetLink", function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Reset Your Password');
            });
    
            return response()->json(['message' => 'Reset link sent to your email.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong. Please try again later.',
                'error' => $e->getMessage() // For development, remove in production
            ], 500);
        }
    }

    /**
 * @OA\Post(
 *     path="/api/reset-password",
 *     summary="Reset password with token",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "token", "password", "password_confirmation"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *             @OA\Property(property="token", type="string", example="some-reset-token-here"),
 *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
 *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Password has been reset.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Password has been reset.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid or expired token",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Invalid or expired token.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Failed to reset password.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to reset password.")
 *         )
 *     )
 * )
 */
        public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'token' => 'required',
                'password' => 'required|confirmed|min:6',
            ]);
    
            $record = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();
    
            if (!$record || !Hash::check($request->token, $record->token)) {
                return response()->json(['message' => 'Invalid or expired token.'], 400);
            }
    
            // Optional: Check if token expired (e.g., older than 60 minutes)
            if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
                return response()->json(['message' => 'Token has expired.'], 400);
            }
    
            $user = \App\Models\User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->setRememberToken(Str::random(60));
            $user->save();
    
            // Fire the password reset event
            event(new PasswordReset($user));
    
            // Delete token after successful reset
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
    
            return response()->json(['message' => 'Password has been reset.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reset password.',
                'error' => $e->getMessage() // Remove in production
            ], 500);
        }
    }
}