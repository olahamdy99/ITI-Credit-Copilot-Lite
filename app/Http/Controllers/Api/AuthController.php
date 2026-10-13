<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Login",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password"},
     *
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="loan.officer@delta.test"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 example="password"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login successful"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where(
            'email',
            $credentials['email']
        )->first();

        if (
            ! $user
            || ! Hash::check(
                $credentials['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken(
            'credit-copilot-api'
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'authority_limit' => $user->authority_limit,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Authentication"},
     *     summary="Logout current user",
     *     description="Revokes the current Sanctum access token.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user",
     *     tags={"Authentication"},
     *     summary="Get current authenticated user",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
