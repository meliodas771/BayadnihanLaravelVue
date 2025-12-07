<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
	public function tasks()
	{
		$userId = auth()->id();
		$posted = DB::table('tasks as t')
			->leftJoin('applications as a', function($join) {
				$join->on('t.id', '=', 'a.task_id')
					->where('a.status', '=', 'accepted');
			})
			->leftJoin('users as d', 'a.doer_id', '=', 'd.id')
			->where('t.poster_id', $userId)
			->where('t.status', '!=', 'cancelled')
			->orderByDesc('t.created_at')
			->select('t.*', DB::raw('d.username as doer_username'), DB::raw('a.status as application_status'))
			->get();
		
		$cancelled = DB::table('tasks')
			->where('poster_id', $userId)
			->where('status', 'cancelled')
			->orderByDesc('created_at')
			->get();
		
		$assigned = DB::table('tasks as t')
			->join('users as u', 't.poster_id', '=', 'u.id')
			->join('applications as a', function($join) {
				$join->on('t.id', '=', 'a.task_id')
					->whereIn('a.status', ['accepted', 'completed']);
			})
			->where('a.doer_id', $userId)
			->orderByDesc('a.applied_at')
			->select('t.*', DB::raw('u.username as poster_name'), DB::raw('a.applied_at'), DB::raw('a.status as application_status'), DB::raw('t.status as task_status'))
			->get();
		
		return response()->json([
			'tasks' => [
				'posted_tasks' => $posted,
				'assigned_tasks' => $assigned,
				'cancelled_tasks' => $cancelled
			],
			'posted_tasks' => $posted,
			'assigned_tasks' => $assigned,
			'cancelled_tasks' => $cancelled
		]);
	}

	public function profile(Request $request)
	{
		$user = $request->user();
		$userId = $user->id;
		
		$completedTasksAsDoer = DB::table('tasks as t')
			->join('applications as a', function($join) {
				$join->on('t.id', '=', 'a.task_id')
					->whereIn('a.status', ['accepted', 'completed']);
			})
			->where('a.doer_id', $userId)
			->where('t.status', 'completed')
			->select('t.*')
			->get();
		
		$totalEarnings = $completedTasksAsDoer->sum('price');
		
		$completedTasksAsPoster = DB::table('tasks')
			->where('poster_id', $userId)
			->where('status', 'completed')
			->where('is_draft', false)
			->get();
		
		$totalSpend = $completedTasksAsPoster->sum('price');
		
		$postedTasksList = DB::table('tasks as t')
			->leftJoin('applications as a', function($join) {
				$join->on('t.id', '=', 'a.task_id')
					->whereIn('a.status', ['accepted', 'completed']);
			})
			->leftJoin('users as u', 'a.doer_id', '=', 'u.id')
			->where('t.poster_id', $userId)
			->where('t.is_draft', false)
			->select('t.*', 'u.username as doer_username')
			->orderByDesc('t.created_at')
			->get();
		
		$postedTasks = $postedTasksList->groupBy('status')->map(function($group) {
			return $group->count();
		})->toArray();
		
		$cancelledTasksCount = DB::table('tasks')
			->where('poster_id', $userId)
			->where('status', 'cancelled')
			->count();
		
		$doneTasksList = DB::table('tasks as t')
			->join('applications as a', function($join) {
				$join->on('t.id', '=', 'a.task_id')
					->whereIn('a.status', ['accepted', 'completed']);
			})
			->join('users as u', 't.poster_id', '=', 'u.id')
			->where('a.doer_id', $userId)
			->select('t.*', 'u.username as poster_username', 't.completion_percentage')
			->orderByDesc('a.applied_at')
			->get();
		
		$doneTasks = $doneTasksList->groupBy('status')->map(function($group) {
			return $group->count();
		})->toArray();
		
		$avgRating = DB::table('feedbacks')
			->where('to_user_id', $userId)
			->avg('rating');
		
		$totalFeedbacks = DB::table('feedbacks')
			->where('to_user_id', $userId)
			->count();
		
		$hasPostedTasks = $postedTasksList->count() > 0;
		$hasDoneTasks = $doneTasksList->count() > 0;
		
	// Get feedbacks received as doer (from posters) - match Blade controller exactly
	// Feedback structure: from_user_id = poster, to_user_id = doer (task->doer_id)
	$feedbacks = DB::table('feedbacks as f')
		->join('users as u', 'f.from_user_id', '=', 'u.id')
		->join('tasks as t', 'f.task_id', '=', 't.id')
		->where('f.to_user_id', $userId)
		->where('t.doer_id', $userId) // Ensure this user was the doer
		->select('f.*', 'u.username as from_username', 'u.profile_pic as from_profile_pic', 't.title as task_title')
		->orderByDesc('f.created_at')
		->paginate(5);
	
	// Get feedbacks received as poster (from doers) - always fetch
	// Feedback structure: from_user_id = doer, to_user_id = poster
	// When doer gives feedback: from_user_id = doer, to_user_id = poster (task->poster_id)
	$feedbacksAsPoster = DB::table('feedbacks as f')
		->join('users as u', 'f.from_user_id', '=', 'u.id')
		->join('tasks as t', 'f.task_id', '=', 't.id')
		->where('f.to_user_id', $userId) // User received the feedback (as poster)
		->where('t.poster_id', $userId) // User was the poster of the task
		->select('f.*', 'u.username as from_username', 'u.profile_pic as from_profile_pic', 't.title as task_title')
		->orderByDesc('f.created_at')
		->get();
		
		return response()->json([
			'user' => $user,
			'totalEarnings' => $totalEarnings ?? 0,
			'totalSpend' => $totalSpend ?? 0,
			'completedTasksCount' => count($completedTasksAsDoer),
			'postedTasks' => $postedTasks,
			'doneTasks' => $doneTasks,
			'postedTasksList' => $postedTasksList,
			'doneTasksList' => $doneTasksList,
			'cancelledTasksCount' => $cancelledTasksCount,
			'avgRating' => $avgRating ? round($avgRating, 1) : null,
			'totalFeedbacks' => $totalFeedbacks,
			'feedbacks' => $feedbacks,
			'feedbacks_as_poster' => $feedbacksAsPoster->values()->all(),
			'hasPostedTasks' => $hasPostedTasks,
			'hasDoneTasks' => $hasDoneTasks,
		]);
	}

	public function updateProfile(Request $request)
	{
		$user = $request->user();
		$data = $request->validate([
			'username' => 'nullable|string|unique:users,username,' . $user->id,
			'email' => 'nullable|email|unique:users,email,' . $user->id,
			'profile_pic' => 'nullable|image|mimes:jpeg,jpg,png',
		]);
		
		if ($request->hasFile('profile_pic')) {
			$filename = 'user_' . $user->id . '.' . $request->file('profile_pic')->extension();
			
			// Save to storage/app/public/profile_pics (Laravel storage system)
			$request->file('profile_pic')->storeAs('profile_pics', $filename, 'public');
			$user->profile_pic = $filename;
		}
		
		$user->username = $data['username'] ?? $user->username;
		$user->email = $data['email'] ?? $user->email;
		$user->save();
		
		return response()->json(['success' => true, 'message' => 'Profile updated', 'user' => $user]);
	}

	public function updateUsername(Request $request)
	{
		$user = $request->user();

		// Validate username + email for verification
		$data = $request->validate([
			'username' => [
				'required',
				'regex:/^\d{3}-\d{5}$/',
				'unique:users,username,' . $user->id
			],
			'email' => ['required', 'email'], // Just needed for API verification
		], [
			'username.regex' => 'ID Number must be in the format 000-00000 (e.g., 231-00123).',
		]);

		// Call SCHOOL API to verify student ID + email
		try {
			$apiUrl = env('SCHOOL_API_URL') . '/api/verify-student-id-number';

			$response = Http::timeout(10)->post($apiUrl, [
				'student_id' => $data['username'],
				'email' => $data['email'],
			]);

			if (!$response->successful()) {
				return response()->json([
					'error' => 'Student ID is not found in the school system.'
				], 400);
			}

			$result = $response->json();

			// Student ID does not exist
			if (empty($result['student_exists']) || !$result['student_exists']) {
				return response()->json([
					'error' => 'Student ID not found in the school system.'
				], 404);
			}

			// Email mismatch
			if (empty($result['email_matches']) || !$result['email_matches']) {
				return response()->json([
					'error' => 'This student ID does not belong to this email.'
				], 400);
			}


		} catch (\Exception $e) {
			return response()->json([
				'error' => 'Unable to connect to school system. Error: ' . $e->getMessage()
			], 500);
		}

		// Update username
		$user->username = $data['username'];
		$user->save();

		return response()->json([
			'success' => true,
			'message' => 'Username updated successfully.',
			'new_username' => $user->username
		], 200);
	}

	
	public function updateTaskCompletion(Request $request, $taskId)
	{
		$request->validate([
			'completion_percentage' => 'required|integer|min:0|max:100',
		]);
		
		$task = \App\Models\Task::findOrFail($taskId);
		
		$isDoer = DB::table('applications')
			->where('task_id', $taskId)
			->where('doer_id', auth()->id())
			->whereIn('status', ['accepted', 'completed'])
			->exists();
		
		if (!$isDoer) {
			return response()->json(['error' => 'You are not authorized to update this task.'], 403);
		}
		
		$task->completion_percentage = $request->completion_percentage;
		$task->save();
		
		return response()->json(['success' => true, 'message' => 'Task completion percentage updated successfully.']);
	}
	
	public function viewProfile($userId, Request $request)
	{
		$user = \App\Models\User::findOrFail($userId);
		$context = $request->query('context');
		
		$hasPostedTasks = DB::table('tasks')->where('poster_id', $userId)->where('status', '!=', 'cancelled')->exists();
		$allPostedTasksCount = DB::table('tasks')->where('poster_id', $userId)->where('status', 'completed')->count();
		$hasCompletedTasks = DB::table('tasks as t')
			->join('applications as a', function($join) {
				$join->on('t.id', '=', 'a.task_id')
					->whereIn('a.status', ['accepted', 'completed']);
			})
			->where('a.doer_id', $userId)
			->where('t.status', 'completed')
			->exists();
		
		$completedTasks = collect();
		$postedTasks = collect();
		$feedbacks = collect();
		$feedbacksAsPoster = collect();
		$avgRating = null;
		$avgRatingAsPoster = null;
		$totalFeedbacks = 0;
		
		$showCompletedTasks = false;
		$showPostedTasks = false;
		
		if ($context === 'applicant') {
			$showCompletedTasks = $hasCompletedTasks;
		} elseif ($context === 'poster') {
			$showPostedTasks = $hasPostedTasks;
		} else {
			$showCompletedTasks = $hasCompletedTasks;
			$showPostedTasks = $hasPostedTasks;
		}
		
		if ($showCompletedTasks) {
			$completedTasks = DB::table('tasks as t')
				->join('applications as a', function($join) {
					$join->on('t.id', '=', 'a.task_id')
						->whereIn('a.status', ['accepted', 'completed']);
				})
				->where('a.doer_id', $userId)
				->where('t.status', 'completed')
				->select('t.*')
				->orderByDesc('t.updated_at')
				->get();
			
			$feedbacks = DB::table('feedbacks as f')
				->join('users as u', 'f.from_user_id', '=', 'u.id')
				->join('tasks as t', 'f.task_id', '=', 't.id')
				->where('f.to_user_id', $userId)
				->where('t.doer_id', $userId)
				->where('t.status', 'completed')
				->select('f.*', 'u.username as from_username', 't.title as task_title')
				->orderByDesc('f.created_at')
				->get();
			
			$avgRating = DB::table('feedbacks as f')
				->join('tasks as t', 'f.task_id', '=', 't.id')
				->where('f.to_user_id', $userId)
				->where('t.doer_id', $userId)
				->where('t.status', 'completed')
				->avg('f.rating');
			
			$totalFeedbacks = $feedbacks->count();
		}
		
		if ($showPostedTasks) {
			$postedTasks = DB::table('tasks as t')
				->leftJoin('applications as a', function($join) {
					$join->on('t.id', '=', 'a.task_id')
						->whereIn('a.status', ['accepted', 'completed']);
				})
				->leftJoin('users as u', 'a.doer_id', '=', 'u.id')
				->leftJoin('feedbacks as f', function($join) {
					$join->on('t.id', '=', 'f.task_id')
						->where('f.from_user_id', '=', DB::raw('t.poster_id'));
				})
				->where('t.poster_id', $userId)
				->where('t.status', 'completed')
				->select('t.*', 'u.username as doer_username', 'u.id as doer_id', 'f.rating as feedback_rating', 'f.reviews as feedback_reviews')
				->orderByDesc('t.updated_at')
				->get();
			
			$feedbacksAsPoster = DB::table('feedbacks as f')
				->join('users as u', 'f.from_user_id', '=', 'u.id')
				->join('tasks as t', 'f.task_id', '=', 't.id')
				->where('f.to_user_id', $userId)
				->where('t.poster_id', $userId)
				->select('f.*', 'u.username as from_username', 't.title as task_title')
				->orderByDesc('f.created_at')
				->get();
			
			$avgRatingAsPoster = DB::table('feedbacks as f')
				->join('tasks as t', 'f.task_id', '=', 't.id')
				->where('f.to_user_id', $userId)
				->where('t.poster_id', $userId)
				->avg('f.rating');
		}
		
		$isAcceptedDoer = false;
		if (auth()->check()) {
			$isAcceptedDoer = DB::table('tasks as t')
				->join('applications as a', function($join) {
					$join->on('t.id', '=', 'a.task_id')
						->whereIn('a.status', ['accepted', 'completed']);
				})
				->where('t.poster_id', $userId)
				->where('a.doer_id', auth()->id())
				->exists();
		}
		
		$isDoerForCurrentUser = false;
		if (auth()->check() && auth()->id() !== $userId) {
			$isDoerForCurrentUser = DB::table('tasks as t')
				->join('applications as a', 't.id', '=', 'a.task_id')
				->where('t.poster_id', auth()->id())
				->where('a.doer_id', $userId)
				->exists();
		}
		
		return response()->json([
			'user' => $user,
			'context' => $context,
			'hasPostedTasks' => $hasPostedTasks,
			'hasCompletedTasks' => $hasCompletedTasks,
			'showCompletedTasks' => $showCompletedTasks,
			'showPostedTasks' => $showPostedTasks,
			'completedTasksCount' => $completedTasks->count(),
			'completedTasks' => $completedTasks,
			'postedTasksCount' => $allPostedTasksCount,
			'postedTasks' => $postedTasks,
			'feedbacks' => $feedbacks,
			'feedbacks_as_poster' => $feedbacksAsPoster,
			'avgRating' => $avgRating ? round($avgRating, 1) : null,
			'avgRatingAsPoster' => $avgRatingAsPoster ? round($avgRatingAsPoster, 1) : null,
			'totalFeedbacks' => $totalFeedbacks,
			'isAcceptedDoer' => $isAcceptedDoer,
			'isDoerForCurrentUser' => $isDoerForCurrentUser,
		]);
	}

	public function getProfilePicture($filename)
	{
		// Sanitize filename to prevent directory traversal
		$filename = basename($filename);
		
		$path = storage_path('app/public/profile_pics/' . $filename);
		
		if (!file_exists($path)) {
			\Log::warning('Profile picture not found: ' . $path);
			abort(404, 'Profile picture not found');
		}
		
		// Get MIME type
		$mimeType = mime_content_type($path);
		if (!$mimeType) {
			$mimeType = 'image/jpeg'; // default
		}
		
		return response()->file($path, [
			'Content-Type' => $mimeType,
			'Cache-Control' => 'public, max-age=3600',
		]);
	}
}

