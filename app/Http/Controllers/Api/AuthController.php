<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="MyRVM API Documentation",
 *      description="API documentation for MyReverseVendingMachine Server",
 *      @OA\Contact(
 *          email="admin@myrvm.com"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Demo API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 */
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * 
     * @OA\Post(
     *      path="/api/v1/register",
     *      operationId="registerUser",
     *      tags={"Auth"},
     *      summary="Register new user",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name","email","password","password_confirmation"},
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="secret"),
     *              @OA\Property(property="password_confirmation", type="string", format="password", example="secret")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="User registered successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Registrasi berhasil"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="user", type="object"),
     *                  @OA\Property(property="token", type="string"),
     *                  @OA\Property(property="token_type", type="string", example="Bearer")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // Default role for public registration
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Log successful registration via API
        ActivityLog::log('Auth', 'Create', "New user registered via API: {$user->name} ({$user->email})", $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    /**
     * Handle incoming authentication request.
     * 
     * @OA\Post(
     *      path="/api/v1/login",
     *      operationId="loginUser",
     *      tags={"Auth"},
     *      summary="Login user and return token",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password","device_name"},
     *              @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="secret"),
     *              @OA\Property(property="device_name", type="string", example="MyDevice")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful login",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Login berhasil"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="token", type="string"),
     *                  @OA\Property(property="token_type", type="string", example="Bearer")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            // Log failed login attempt via API
            ActivityLog::create([
                'user_id' => null,
                'module' => 'Auth',
                'action' => 'Error',
                'description' => "Failed API login attempt for email: {$request->email}",
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Kredensial tidak valid',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Delete old tokens if needed (optional security measure)
        // $user->tokens()->delete();

        $token = $user->createToken($request->device_name)->plainTextToken;

        // Log successful login via API
        ActivityLog::log('Auth', 'Login', "User {$user->name} ({$user->email}) logged in via API from device: {$request->device_name}", $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Get the authenticated User.
     * 
     * @OA\Get(
     *      path="/api/v1/me",
     *      operationId="getProfile",
     *      tags={"Auth"},
     *      summary="Get user profile",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="User profile",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      )
     * )
     */
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user(),
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     * 
     * @OA\Post(
     *      path="/api/v1/logout",
     *      operationId="logoutUser",
     *      tags={"Auth"},
     *      summary="Logout user",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful logout"
     *      )
     * )
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Log logout via API
        ActivityLog::log('Auth', 'Logout', "User {$user->name} ({$user->email}) logged out via API");

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successfully'
        ]);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate reset token
        $token = \Illuminate\Support\Str::random(64);

        // Store token (expires in 1 hour)
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => hash('sha256', $token),
                'created_at' => now()
            ]
        );

        // TODO: Send email with reset link
        // Mail::to($user->email)->send(new PasswordResetMail($token));

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset link sent to your email',
            // In development, return token for testing
            'token' => config('app.env') === 'local' ? $token : null
        ]);
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify token
        $resetRecord = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid reset token'
            ], 400);
        }

        // Check if token matches and not expired (1 hour)
        if (
            hash('sha256', $request->token) !== $resetRecord->token ||
            now()->diffInMinutes($resetRecord->created_at) > 60
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reset token expired or invalid'
            ], 400);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => \Hash::make($request->password)
        ]);

        // Delete used token
        \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successfully'
        ]);
    }
}
