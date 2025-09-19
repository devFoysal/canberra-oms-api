<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Helpers\{
    FileHelper,
    ResponseHelper
};

use App\Http\Resources\Api\V1\User\{
    UserResource,
};

// Request
use App\Http\Requests\Api\V1\Auth\{
    SignUpRequest,
    SignInRequest,
    ForgotPasswordRequest,
    ResetPasswordRequest
};

use App\Mail\Auth\{
    PasswordResetMail,
    PasswordResetSuccessMail
};

/**
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="mobile_number", type="string", example="+1234567890"),
 *     @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="is_email_verified", type="boolean", example=true),
 *     @OA\Property(property="is_mobile_verified", type="boolean", example=false),
 *     @OA\Property(property="is_logged_in", type="boolean", example=true),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="status", type="string", example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Error message"),
 *     @OA\Property(property="errors", type="object", example={"field": {"Error message"}})
 * )
 */

class AuthController extends Controller
{
    /**
     * User Sign Up
     *
     * @OA\Post(
     *     path="/auth/sign-up",
     *     summary="Register a new user",
     *     description="Create a new user account with email and password",
     *     operationId="signUp",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data (Form URL Encoded)",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"first_name","email","password","password_confirmation"},
     *                 @OA\Property(property="first_name", type="string", example="Foysal"),
     *                 @OA\Property(property="last_name", type="string", example="Mahmud"),
     *                 @OA\Property(property="mobile_number", type="string", example="01688784568"),
     *                 @OA\Property(property="email", type="string", format="email", example="foysal.km68@gmail.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="password"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password", example="password"),
     *                 @OA\Property(property="avatar", type="string", format="binary", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function signUp(SignUpRequest $request): JsonResponse
    {

        try {
            $userData = $request->validated();
            $userData['password'] = Hash::make($userData['password']);
            $userData['full_name'] = $userData['full_name'] ?? $userData['first_name'] . ' ' . $userData['last_name'];
            $userData['email_verification_token'] = Str::random(60);

            if($userData['avatar']){
                $imagePath = FileHelper::uploadImages($request->file('avatar'), 'avatar', [
                    'optimize' => true
                ]);

                $userData['avatar'] = $imagePath;
            }

            $user = User::create($userData);

            return ResponseHelper::success(new UserResource($user), 'User registered successfully');

        } catch (\Exception $e) {
            return ResponseHelper::error('Registration failed', $e->getCode(), $e->getMessage());
        }
    }

    /**
     * User Sign In
     *
     * @OA\Post(
     *     path="/auth/sign-in",
     *     summary="Authenticate user",
     *     description="Sign in with email and password",
     *     operationId="signIn",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="foysal.km68@gmail.com"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="password"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function signIn(SignInRequest $request): JsonResponse
    {

        try{
            if (Auth::attempt($request->only('email', 'password'))) {
                $user = Auth::user();

                if ($user->status !== 'active') {
                    return response()->json([
                        'message' => 'Account is ' . $user->status
                    ], 403);
                }

                $user->update([
                    'is_logged_in' => true,
                    'last_login_at' => now()
                ]);

                $token = $user->createToken('authToken')->plainTextToken;

                $data = [
                    "user" => new UserResource($user),
                    "token" => $token,
                ];
                return ResponseHelper::success($data, 'Login successful');
            }else{
                return ResponseHelper::error('Invalid credentials', 401);
            }

        } catch (\Exception $e) {
            return ResponseHelper::error('Invalid credentials', $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Admin Sign In
     *
     * @OA\Post(
     *     path="/auth/admin/sign-in",
     *     summary="Authenticate user",
     *     description="Sign in with email and password",
     *     operationId="signIn",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="foysal.km68@gmail.com"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="password"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function adminSignIn(SignInRequest $request): JsonResponse
    {

        try{
            if (Auth::attempt($request->only('email', 'password'))) {
                $user = Auth::user();

                if ($user->status !== 'active') {
                    return ResponseHelper::error('Account is ' . $user->status, 403);
                }

                // Check if user has admin or super_admin role
                if (!$user->hasAnyRole(['admin', 'super_admin'])) {
                    return ResponseHelper::error('Access denied.', 403);
                }

                $user->update([
                    'is_logged_in' => true,
                    'last_login_at' => now()
                ]);

                $token = $user->createToken('authToken')->plainTextToken;

                $data = [
                    "user" => new UserResource($user),
                    "token" => $token,
                ];
                return ResponseHelper::success($data, 'Login successful');
            }else{
                return ResponseHelper::error('Invalid credentials', 401);
            }

        } catch (\Exception $e) {
            return ResponseHelper::error('Invalid credentials', $e->getCode(), $e->getMessage());
        }
    }

    /**
     * User Sign Out
     *
     * @OA\Post(
     *     path="/auth/sign-out",
     *     summary="Sign out user",
     *     description="Invalidate the current access token",
     *     operationId="signOut",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function signOut(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user) {
                $user->update(['is_logged_in' => false]);
                $user->currentAccessToken()->delete();

                // Clear session if using web guard
                Auth::guard('web')->logout();
            }

            return ResponseHelper::success($user, 'Successfully logged out');

        } catch (\Exception $e) {
            // Even if there's an error, try to clear the cookie
            $request->user()->currentAccessToken()->delete();
            return ResponseHelper::success($user, 'Successfully logged out');
        }
    }

    /**
     * Forgot Password
     *
     * @OA\Post(
     *     path="/auth/forgot-password",
     *     summary="Request password reset",
     *     description="Send password reset link to email",
     *     operationId="forgotPassword",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Email address",
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="john@example.com"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reset link sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset link sent to your email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unable to send reset email")
     *         )
     *     )
     * )
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {

        try {
            $token = Str::random(60);
            $email = $request->email;
            $user = User::where('email', $email)->first();

            if(!$user)  return ResponseHelper::error("Email not found",404);


            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($token),
                    'token_expiry' => now()->addHours(2),
                    'created_at' => now()
                ]
            );

            $name = $user->full_name;
            $resetUrl = config('canbirra.frontend_url') . "/reset-password?token={$token}";

            // In a real application, you would send an email here
            Mail::to($user->email)->queue(new PasswordResetMail($name, $resetUrl));

            return ResponseHelper::success(null, "Password reset link sent to your email " . count(Mail::failures()));

        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(),500);
        }
    }

    /**
     * Reset Password
     *
     * @OA\Post(
     *     path="/auth/reset-password",
     *     summary="Reset user password",
     *     description="Reset password using token from email",
     *     operationId="resetPassword",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Password reset data",
     *         @OA\JsonContent(
     *             required={"email", "token", "password", "password_confirmation"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="john@example.com"
     *             ),
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 example="reset_token_string"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 minLength=8,
     *                 example="NewPassword123!"
     *             ),
     *             @OA\Property(
     *                 property="password_confirmation",
     *                 type="string",
     *                 format="password",
     *                 example="NewPassword123!"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid or expired reset token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $resetData = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetData || !Hash::check($request->token, $resetData->token)) {
                return ResponseHelper::error('Invalid or expired reset token', 400);
            }

            if (Carbon::parse($resetData->token_expiry)->isPast()) {
                return ResponseHelper::error('Reset token has expired', 400);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return ResponseHelper::error('Invalid email address', 404);
            }

            $user->update([
                'password' => Hash::make($request->password)
            ]);

            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            Mail::to($user->email)->send(new PasswordResetSuccessMail($user->name));

            return ResponseHelper::success(null, 'Password reset successfully {count(Mail::failures())}');

        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(),500);
        }
    }

    /**
     * Change Password
     *
     * @OA\Put(
     *     path="/auth/change-password",
     *     summary="Change user password",
     *     description="Change password while authenticated",
     *     operationId="changePassword",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Password change data",
     *         @OA\JsonContent(
     *             required={"current_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(
     *                 property="current_password",
     *                 type="string",
     *                 format="password",
     *                 example="CurrentPassword123!"
     *             ),
     *             @OA\Property(
     *                 property="new_password",
     *                 type="string",
     *                 format="password",
     *                 minLength=8,
     *                 example="NewPassword123!"
     *             ),
     *             @OA\Property(
     *                 property="new_password_confirmation",
     *                 type="string",
     *                 format="password",
     *                 example="NewPassword123!"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password changed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated or invalid current password",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Current password is incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|current_password:api',
            'new_password' => 'required|min:8|confirmed|different:current_password',
        ], [
            'current_password.current_password' => 'The current password is incorrect',
            'new_password.different' => 'New password must be different from current password'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'message' => 'Password changed successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Password change failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the appropriate cookie domain for cross-domain/subdomain support
     */
    private function getCookieDomain(): string
    {
        $host = request()->getHost();

        // If it's an IP address, return empty (cookies don't work with IPs for cross-domain)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return '';
        }

        // For localhost, return empty
        if ($host === 'localhost') {
            return '';
        }

        // Check if it has subdomains
        $parts = explode('.', $host);

        if (count($parts) > 2) {
            // For subdomains like api.example.com, return .example.com
            array_shift($parts);
            return '.' . implode('.', $parts);
        }

        // For main domains like example.com, return .example.com
        return '.' . $host;
    }
}
