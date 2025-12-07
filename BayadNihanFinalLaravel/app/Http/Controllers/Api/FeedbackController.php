<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Task;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
	public function create($taskId)
	{
		$userId = auth()->id();
		
		$task = Task::with('acceptedApplication')->find($taskId);
		if (!$task) {
			return response()->json(['error' => 'Task not found'], 404);
		}

		if ($task->poster_id !== $userId) {
			return response()->json(['error' => 'Only the task poster can give feedback'], 403);
		}

		if ($task->status !== 'completed') {
			return response()->json(['error' => 'Task must be completed before giving feedback'], 400);
		}

		// Check if THIS USER already submitted feedback (not just any feedback)
		if ($task->doer_id && Feedback::where('task_id', $taskId)
			->where('from_user_id', $userId)
			->where('to_user_id', $task->doer_id)
			->exists()) {
			return response()->json(['error' => 'You have already submitted feedback for this task'], 400);
		}

		return response()->json(['task' => $task]);
	}

	public function store(Request $request, $taskId)
	{
		$userId = auth()->id();
		
		$task = Task::find($taskId);
		if (!$task) {
			return response()->json(['error' => 'Task not found'], 404);
		}

		if ($task->poster_id !== $userId) {
			return response()->json(['error' => 'Only the task poster can give feedback'], 403);
		}

		if ($task->status !== 'completed') {
			return response()->json(['error' => 'Task must be completed before giving feedback'], 400);
		}

		if (!$task->doer_id) {
			return response()->json(['error' => 'No doer assigned to this task'], 400);
		}

		// Check if THIS USER already submitted feedback (not just any feedback)
		if (Feedback::where('task_id', $taskId)
			->where('from_user_id', $userId)
			->where('to_user_id', $task->doer_id)
			->exists()) {
			return response()->json(['error' => 'You have already submitted feedback for this task'], 400);
		}

		$data = $request->validate([
			'rating' => 'required|integer|min:1|max:5',
			'reviews' => 'nullable|string',
		]);

		$feedback = Feedback::create([
			'task_id' => $taskId,
			'from_user_id' => $userId,
			'to_user_id' => $task->doer_id,
			'rating' => $data['rating'],
			'reviews' => $data['reviews'] ?? null,
		]);

		return response()->json([
			'success' => true,
			'message' => 'Feedback submitted successfully',
			'feedback' => $feedback
		]);
	}

	public function storeDoerFeedback(Request $request, $taskId)
	{
		$userId = auth()->id();
		
		$task = Task::find($taskId);
		if (!$task) {
			return response()->json(['error' => 'Task not found'], 404);
		}

		$userApplication = \App\Models\Application::where('task_id', $taskId)
			->where('doer_id', $userId)
			->whereIn('status', ['accepted', 'completed'])
			->first();
			
		if (!$userApplication) {
			return response()->json(['error' => 'Only the assigned doer can give feedback to the poster'], 403);
		}

		if ($task->status !== 'completed') {
			return response()->json(['error' => 'Task must be completed before giving feedback'], 400);
		}

		if (Feedback::where('task_id', $taskId)->where('from_user_id', $userId)->where('to_user_id', $task->poster_id)->exists()) {
			return response()->json(['error' => 'Feedback already submitted for this task'], 400);
		}

		$data = $request->validate([
			'rating' => 'required|integer|min:1|max:5',
			'reviews' => 'nullable|string',
		]);

		$feedback = Feedback::create([
			'task_id' => $taskId,
			'from_user_id' => $userId,
			'to_user_id' => $task->poster_id,
			'rating' => $data['rating'],
			'reviews' => $data['reviews'] ?? null,
		]);

		return response()->json([
			'success' => true,
			'message' => 'Feedback to poster submitted successfully',
			'feedback' => $feedback
		]);
	}

	public function show($taskId)
	{
		$userId = auth()->id();
		$task = Task::find($taskId);
		
		if (!$task) {
			return response()->json(['error' => 'Task not found'], 404);
		}

		// Check if user is the poster - return feedback from poster to doer
		if ($task->poster_id === $userId) {
			$feedback = Feedback::where('task_id', $taskId)
				->where('from_user_id', $userId)
				->where('to_user_id', $task->doer_id)
				->with(['fromUser:id,username', 'toUser:id,username', 'task'])
				->first();
		} else {
			// User is the doer - return feedback from doer to poster
			$feedback = Feedback::where('task_id', $taskId)
				->where('from_user_id', $userId)
				->where('to_user_id', $task->poster_id)
				->with(['fromUser:id,username', 'toUser:id,username', 'task'])
				->first();
		}

		if (!$feedback) {
			return response()->json(['error' => 'Feedback not found'], 404);
		}

		return response()->json(['feedback' => $feedback]);
	}
}

