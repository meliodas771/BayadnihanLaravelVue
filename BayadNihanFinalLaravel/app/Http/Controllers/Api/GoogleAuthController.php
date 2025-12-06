<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->stateless()->redirect();
        } catch (\Exception $e) {
            \Log::error('Google OAuth Redirect Error: ' . $e->getMessage());
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect($frontendUrl . '/auth/login?error=' . urlencode('Failed to initiate Google authentication. Please check your Google OAuth configuration.'));
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Validate that the email is a student email
            if (!$this->isStudentEmail($googleUser->getEmail())) {
                $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
                return redirect($frontendUrl . '/auth/login?error=' . urlencode('Only student emails are allowed. Please use your educational email address (.edu).'));
            }

            // Check if user exists
            $user = User::where('email', $googleUser->getEmail())->first();
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            if ($user) {
                // User exists, generate Sanctum token and redirect to frontend
                $token = $user->createToken('auth_token')->plainTextToken;
                return redirect($frontendUrl . '/auth/callback?token=' . urlencode($token) . '&success=1');
            } else {
                // New user - redirect to frontend with Google user data for role selection
                $googleData = base64_encode(json_encode([
                    'name' => $googleUser->getName() ?? explode('@', $googleUser->getEmail())[0],
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]));
                
                return redirect($frontendUrl . '/auth/google-role-selection?data=' . urlencode($googleData));
            }
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            \Log::error('Google OAuth State Mismatch: ' . $e->getMessage());
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect($frontendUrl . '/auth/login?error=' . urlencode('Session expired or invalid. Please try logging in with Google again.'));
        } catch (\Exception $e) {
            \Log::error('Google OAuth Error: ' . $e->getMessage());
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $errorMessage = config('app.debug') 
                ? 'Failed to authenticate with Google: ' . $e->getMessage()
                : 'Failed to authenticate with Google. Please try again.';
            return redirect($frontendUrl . '/auth/login?error=' . urlencode($errorMessage));
        }
    }

    /**
     * Complete Google OAuth registration with selected role
     */
    public function completeRegistration(Request $request)
    {
        // Validate role selection
        $request->validate([
            'role' => 'required|in:both,poster,doer',
            'google_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
        ]);

        try {
            // Determine subscription status and trial period
            $subscriptionStatus = 'active';
            $trialEndsAt = null;
            
            if ($request->role === 'both') {
                $subscriptionStatus = 'trial';
                $trialEndsAt = now()->addDays(7); // 7-day trial period
            }

            // Check if user already exists
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'User with this email already exists.'
                ], 409);
            }

            // Create new user with selected role
            $user = User::create([
                'username' => $request->name,
                'email' => $request->email,
                'email_verified_at' => now(), // Google users are pre-verified
                'password' => Hash::make(uniqid()), // Random password since they use Google
                'role' => $request->role,
                'subscription_status' => $subscriptionStatus,
                'trial_ends_at' => $trialEndsAt,
                'google_id' => $request->google_id,
                'profile_pic' => $request->avatar ?? null,
            ]);

            // Generate Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Custom success message for trial users
            $message = $request->role === 'both' 
                ? 'Welcome to BayadNihan! Your 7-day free trial has started. Enjoy full access to both Poster and Doer roles!'
                : 'Welcome to BayadNihan! Your account has been created successfully.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'subscription_status' => $user->subscription_status,
                    'trial_ends_at' => $user->trial_ends_at?->toISOString(),
                ],
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            \Log::error('Google Registration Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to create account. Please try again.'
            ], 500);
        }
    }

    /**
     * Check if email is a student email
     */
    private function isStudentEmail($email)
    {
        // Check if email ends with .edu or specific educational domains
        $educationalDomains = [
            '.edu',
            '.edu.ph',
            '.ust.edu.ph',
            '.dlsu.edu.ph',
            '.up.edu.ph',
            '.admu.edu.ph',
            '.ateneo.edu',
            'carsu.edu.ph',
        ];

        foreach ($educationalDomains as $domain) {
            if (str_ends_with(strtolower($email), $domain)) {
                return true;
            }
        }

        return false;
    }
}

