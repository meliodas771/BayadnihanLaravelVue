<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class AuthController extends Controller
{
	public function login(Request $request)
	{
		$credentials = $request->validate(['email' => 'required|email','password' => 'required']);
		
		// Check rate limiting
		$key = 'login:' . $request->input('email') . '|' . $request->ip();
		
		if (RateLimiter::tooManyAttempts($key, 5)) {
			$seconds = RateLimiter::availableIn($key);
			return response()->json([
				'error' => "Too many login attempts. Please try again in {$seconds} seconds."
			], 429);
		}
		
		// For API authentication, manually check credentials instead of using Auth::attempt()
		$user = User::where('email', $credentials['email'])->first();
		
		// Check if user exists and password is correct
		if ($user && Hash::check($credentials['password'], $user->password)) {
			
			// Check if account is active
			if (!$user->is_active) {
				RateLimiter::hit($key, 60);
				return response()->json(['error' => 'Your account has been banned. Please contact support.'], 403);
			}
			
			// // Check if email is verified
			// if (!$user->hasVerifiedEmail()) {
			// 	RateLimiter::hit($key, 60);
				
			// 	// Send verification email
			// 	try {
			// 		$user->sendEmailVerificationNotification();
			// 	} catch (\Exception $e) {
			// 		\Log::error('Failed to send verification email: ' . $e->getMessage());
			// 	}
				
			// 	return response()->json([
			// 		'error' => 'Please verify your email address before logging in. We\'ve sent a verification link to ' . $user->email
			// 	], 403);
			// }
			
			// Clear rate limiter on successful login
			RateLimiter::clear($key);
			
			$token = $user->createToken('auth_token')->plainTextToken;
			
			return response()->json([
				'success' => true,
				'token' => $token,
				'user' => [
					'id' => $user->id,
					'username' => $user->username,
					'email' => $user->email,
					'role' => $user->role,
					'profile_pic' => $user->profile_pic,
				]
			]);
		}
		
		// Increment failed login attempts
		RateLimiter::hit($key, 60);
		
		return response()->json(['error' => 'Invalid credentials'], 401);
	}

	public function register(Request $request)
	{
		$data = $request->validate([
			'username' => ['required','string','regex:/^\d{3}-\d{5}$/','unique:users,username'],
			'email' => ['required','email','regex:/^[A-Za-z0-9._%+-]+@carsu\.edu\.ph$/i','unique:users,email'],
			'password' => 'required|string|min:6',
			'role' => 'nullable|string',
			'phone_number' => 'nullable|string',
		], [
			'username.regex' => 'ID Number must be in the format 000-00000 (e.g., 231-00123).',
			'email.regex' => 'Email must be a valid @carsu.edu.ph',
		]);

		//Verify that the student ID and email exist in the school system via API
		try {
			$apiUrl = env('SCHOOL_API_URL') . '/api/verify-student';

			// Send POST request with student_id and email
			$response = Http::timeout(10)->post($apiUrl, [
				'student_id' => $data['username'],
				'email' => $data['email'],
			]);

			// Check if API connection succeeded
			if (!$response->successful()) {
				return response()->json([
					'error' => 'Student ID or email not found in the school system.'
				], 400);
			}

			$result = $response->json();

			// Step 1: Check if student ID exists
			if (empty($result['student_exists']) || !$result['student_exists']) {
				return response()->json([
					'error' => 'Student ID not found in the school system.'
				], 404);
			}

			// Step 2: Check if email matches
			if (empty($result['email_matches']) || !$result['email_matches']) {
				return response()->json([
					'error' => 'The email does not match this student ID.'
				], 400);
			}

			// Step 3: Check if student is enrolled
			if (empty($result['enrolled']) || !$result['enrolled']) {
				return response()->json([
					'error' => 'This student exists but is not currently enrolled.'
				], 400);
			}

		} catch (\Exception $e) {
			return response()->json([
				'error' => 'Unable to connect to school system. Error: ' . $e->getMessage()
			], 500);
		}

		// Determine subscription status and trial period
		$subscriptionStatus = 'active';
		$trialEndsAt = null;
		
		if (isset($data['role']) && $data['role'] === 'both') {
			$subscriptionStatus = 'trial';
			$trialEndsAt = now()->addDays(7);
		}

		$user = User::create([
			'username' => $data['username'],
			'email' => $data['email'],
			'password' => Hash::make($data['password']),
			'role' => $data['role'] ?? null,
			'subscription_status' => $subscriptionStatus,
			'trial_ends_at' => $trialEndsAt,
			'phone_number' => $data['phone_number'] ?? null,
		]);
		
		// Send email verification notification
		$user->sendEmailVerificationNotification();
		
		return response()->json([
			'success' => true,
			'message' => 'Registration successful! We\'ve sent a verification link to ' . $data['email'] . '. Please check your email and click the verification link to activate your account.',
			'user' => [
				'id' => $user->id,
				'username' => $user->username,
				'email' => $user->email,
			]
		], 201);
	}

	public function checkAuth(Request $request)
	{
		// Check if user is authenticated via Sanctum token
		// Manually validate the token since route is public
		$bearerToken = $request->bearerToken();
		
		if ($bearerToken) {
			// Find the token in the database
			$accessToken = PersonalAccessToken::findToken($bearerToken);
			
			if ($accessToken) {
				$user = $accessToken->tokenable;
				
				if ($user && $user instanceof User) {
					return response()->json([
						'authenticated' => true,
						'user' => [
							'id' => $user->id,
							'username' => $user->username,
							'email' => $user->email,
							'role' => $user->role,
							'profile_pic' => $user->profile_pic,
						]
					], 200);
				}
			}
		}
		
		// Return 200 with authenticated: false instead of 401
		// This allows the frontend to handle unauthenticated state gracefully
		return response()->json(['authenticated' => false], 200);
	}

	public function logout(Request $request)
	{
		$request->user()->tokens()->delete();
		return response()->json(['success' => true, 'message' => 'Logged out successfully']);
	}

	public function sendResetCode(Request $request)
	{
		$request->validate([
			'email' => 'required|email|regex:/^[A-Za-z0-9._%+-]+@carsu\.edu\.ph$/i'
		], [
			'email.regex' => 'Email must be a valid @carsu.edu.ph address'
		]);

		$user = User::where('email', $request->email)->first();
		if (!$user) {
			return response()->json(['error' => 'No account found with this email address.'], 404);
		}

		// Generate 6-digit verification code
		$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
		
		// Store the code in database (expires in 5 minutes)
		DB::table('password_reset_tokens')->updateOrInsert(
			['email' => $request->email],
			[
				'token' => $code,
				'expires_at' => Carbon::now()->addMinutes(5),
				'created_at' => Carbon::now(),
				'updated_at' => Carbon::now()
			]
		);

		// Send email with verification code
		try {
			$appUrl = env('APP_URL_EMAIL', 'http://localhost:3000');
			$content = "<h2>Password Reset Verification Code</h2>";
			$content .= "<p>Your BayadNihan password reset verification code is:</p>";
			$content .= "<div class='task-details' style='text-align: center; font-size: 24px; font-weight: bold; color: #4e73df;'>";
			$content .= "<p style='margin: 10px 0;'>{$code}</p>";
			$content .= "</div>";
			$content .= "<p><strong>This code will expire in 5 minutes.</strong></p>";
			$content .= "<p style='color: #858796; font-size: 13px;'>Do not reply to this email.</p>";
			$content .= "<p style='color: #858796; font-size: 13px;'>If you didn't request this, please ignore this email.</p>";
			
			Mail::send('emails.notification', [
				'content' => $content,
				'subject' => 'BayadNihan - Password Reset Verification Code',
				'appUrl' => $appUrl
			], function ($message) use ($request) {
				$message->to($request->email)
					->subject('BayadNihan - Password Reset Verification Code');
			});

			return response()->json([
				'success' => true,
				'message' => 'Verification code sent to your email!',
				'email' => $request->email
			]);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Failed to send verification code. Please try again.'], 500);
		}
	}

	public function verifyCode(Request $request)
	{
		$request->validate([
			'email' => 'required|email',
			'code' => 'required|string|size:6'
		]);

		$resetToken = DB::table('password_reset_tokens')
			->where('email', $request->email)
			->where('token', $request->code)
			->where('expires_at', '>', Carbon::now())
			->first();

		if (!$resetToken) {
			return response()->json(['error' => 'Invalid or expired verification code.'], 400);
		}

		return response()->json([
			'success' => true,
			'email' => $request->email,
			'token' => $request->code
		]);
	}

	public function resendCode(Request $request)
	{
		$request->validate(['email' => 'required|email']);

		$user = User::where('email', $request->email)->first();
		if (!$user) {
			return response()->json(['error' => 'No account found with this email address.'], 404);
		}

		// Generate new 6-digit verification code
		$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
		
		// Update the code in database
		DB::table('password_reset_tokens')->updateOrInsert(
			['email' => $request->email],
			[
				'token' => $code,
				'expires_at' => Carbon::now()->addMinutes(5),
				'updated_at' => Carbon::now()
			]
		);

		// Send email with new verification code
		try {
			$appUrl = env('APP_URL_EMAIL', 'http://localhost:3000');
			$content = "<h2>New Password Reset Verification Code</h2>";
			$content .= "<p>Your new BayadNihan password reset verification code is:</p>";
			$content .= "<div class='task-details' style='text-align: center; font-size: 24px; font-weight: bold; color: #4e73df;'>";
			$content .= "<p style='margin: 10px 0;'>{$code}</p>";
			$content .= "</div>";
			$content .= "<p><strong>This code will expire in 5 minutes.</strong></p>";
			$content .= "<p style='color: #858796; font-size: 13px;'>If you didn't request this, please ignore this email.</p>";
			
			Mail::send('emails.notification', [
				'content' => $content,
				'subject' => 'BayadNihan - New Password Reset Verification Code',
				'appUrl' => $appUrl
			], function ($message) use ($request) {
				$message->to($request->email)
					->subject('BayadNihan - New Password Reset Verification Code');
			});

			return response()->json(['success' => true, 'message' => 'New verification code sent to your email!']);
		} catch (\Exception $e) {
			return response()->json(['error' => 'Failed to send verification code. Please try again.'], 500);
		}
	}

	public function resetPassword(Request $request)
	{
		$request->validate([
			'email' => 'required|email',
			'token' => 'required|string|size:6',
			'password' => 'required|string|min:6|confirmed'
		]);

		// Verify token is still valid
		$resetToken = DB::table('password_reset_tokens')
			->where('email', $request->email)
			->where('token', $request->token)
			->where('expires_at', '>', Carbon::now())
			->first();

		if (!$resetToken) {
			return response()->json(['error' => 'Invalid or expired reset token.'], 400);
		}

		// Find user and update password
		$user = User::where('email', $request->email)->first();
		if (!$user) {
			return response()->json(['error' => 'User not found.'], 404);
		}

		// Update password
		$user->password = Hash::make($request->password);
		$user->save();

		// Delete the reset token
		DB::table('password_reset_tokens')->where('email', $request->email)->delete();

		return response()->json(['success' => true, 'message' => 'Password reset successfully! You can now login with your new password.']);
	}

	public function verifyEmail(Request $request)
	{
		$user = User::find($request->route('id'));
		
		if (!$user) {
			return response()->json([
				'success' => false,
				'message' => 'User not found. Please contact support.'
			], 404);
		}
		
		if ($user->hasVerifiedEmail()) {
			return response()->json([
				'success' => true,
				'message' => 'Email already verified! You can now login from the browser where you registered.'
			]);
		}
		
		// Verify the hash matches the email
		if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
			return response()->json([
				'success' => false,
				'message' => 'Invalid verification link. Please request a new verification email.'
			], 400);
		}
		
		// Verify the signed URL
		if (!URL::hasValidSignature($request)) {
			return response()->json([
				'success' => false,
				'message' => 'Invalid or expired verification link. Please request a new verification email.'
			], 400);
		}
		
		if ($user->markEmailAsVerified()) {
			event(new \Illuminate\Auth\Events\Verified($user));
		}
		
		// Custom success message for trial users
		$message = 'Thank you for verifying your email! Your account has been activated successfully. You are good to login now from the browser where you registered.';
		if ($user->subscription_status === 'trial' && $user->role === 'both') {
			$message = 'Thank you for verifying your email! Your 7-day free trial has started. Enjoy full access to both Poster and Doer roles! You are good to login now from the browser where you registered.';
		}
		
		return response()->json([
			'success' => true,
			'message' => $message
		]);
	}

	public function resendVerificationEmail(Request $request)
	{
		$request->validate(['email' => 'required|email']);
		
		$user = User::where('email', $request->email)->first();
		
		if (!$user) {
			return response()->json(['error' => 'No account found with this email address.'], 404);
		}
		
		if ($user->hasVerifiedEmail()) {
			return response()->json(['success' => true, 'message' => 'Email already verified. You can login.']);
		}
		
		$user->sendEmailVerificationNotification();
		
		return response()->json(['success' => true, 'message' => 'Verification link has been sent to your email address. Please check your email.']);
	}
}

