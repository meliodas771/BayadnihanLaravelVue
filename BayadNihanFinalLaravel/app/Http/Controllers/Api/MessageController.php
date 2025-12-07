<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Message;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
	public function messagesJson($taskId)
	{
		$userId = auth()->id();
		$hasAccess = Task::where('id', $taskId)
			->where(function ($q) use ($userId) {
				$q->where('poster_id', $userId)
				  ->orWhereIn('id', Application::where('doer_id', $userId)->where('status', 'accepted')->pluck('task_id'));
			})
			->exists();
		if (! $hasAccess) return response()->json([], 403);

		$messages = Message::where('task_id', $taskId)
			->with(['sender:id,username'])
			->orderBy('sent_at', 'asc')
			->get()
			->map(function ($m) {
				// Ensure sent_at is a Carbon instance and format it properly
				// Use toIso8601String() or format it as ISO8601 with timezone
				$sentAt = null;
				if ($m->sent_at) {
					// If it's already a Carbon instance, use toAtomString()
					// Otherwise, parse it first
					$carbon = $m->sent_at instanceof \Carbon\Carbon 
						? $m->sent_at 
						: \Carbon\Carbon::parse($m->sent_at);
					$sentAt = $carbon->toAtomString();
				} elseif ($m->created_at) {
					$sentAt = $m->created_at->toAtomString();
				}
				
				return [
					'id' => $m->id,
					'sender_id' => $m->sender_id,
					'sender_name' => $m->sender?->username,
					'content' => $m->content,
					'image_url' => $m->image_url,
					'sent_at' => $sentAt,
					'created_at' => $m->created_at ? $m->created_at->toAtomString() : null,
				];
			});
		return response()->json($messages);
	}

	public function store($taskId, Request $request)
	{
		$data = $request->validate([
			'content' => 'nullable|string',
			'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
		]);
		
		if (!$request->filled('content') && !$request->hasFile('image')) {
			return response()->json(['error' => 'Message or image required'], 422);
		}

		$task = Task::find($taskId);
		if (! $task) {
			return response()->json(['error' => 'Task not found'], 404);
		}

		$userId = auth()->id();

		$hasAccess = Task::where('id', $taskId)
			->where(function ($q) use ($userId) {
				$q->where('poster_id', $userId)
				  ->orWhereIn('id', Application::where('doer_id', $userId)->where('status', 'accepted')->pluck('task_id'));
			})
			->exists();
		if (! $hasAccess) {
			return response()->json(['error' => 'Access denied'], 403);
		}

		$application = Application::where('task_id', $taskId)->where('status', 'accepted')->first();
		$receiverId = $userId === $task->poster_id
			? ($application?->doer_id ?: null)
			: $task->poster_id;
		if (! $receiverId) {
			return response()->json(['error' => 'Cannot send message yet'], 400);
		}

		$imageUrl = null;
		if ($request->hasFile('image')) {
			$image = $request->file('image');
			$imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
			// Use Storage facade to store in storage/app/public/chat_images
			$path = $image->storeAs('chat_images', $imageName, 'public');
			$imageUrl = 'storage/' . $path;
		}

		// Use UTC time explicitly to ensure consistency
		$msg = Message::create([
			'task_id' => $taskId,
			'sender_id' => $userId,
			'receiver_id' => $receiverId,
			'content' => $data['content'] ?? '',
			'image_url' => $imageUrl,
			'sent_at' => now('UTC'),
		]);

		// Try to broadcast, but don't fail if Pusher/Reverb is unavailable
		try {
			event(new MessageSent($msg->load('sender')));
		} catch (\Exception $e) {
			// Log the error but don't fail the request
			\Log::warning('Failed to broadcast message: ' . $e->getMessage());
		}

		// Return message with properly formatted timestamp
		$msg->load('sender:id,username');
		$msg->refresh(); // Refresh to ensure sent_at is cast properly
		
		// Ensure sent_at is a Carbon instance
		$sentAt = null;
		if ($msg->sent_at) {
			$carbon = $msg->sent_at instanceof \Carbon\Carbon 
				? $msg->sent_at 
				: \Carbon\Carbon::parse($msg->sent_at);
			$sentAt = $carbon->toAtomString();
		} elseif ($msg->created_at) {
			$sentAt = $msg->created_at->toAtomString();
		}
		
		return response()->json([
			'ok' => true, 
			'message' => [
				'id' => $msg->id,
				'sender_id' => $msg->sender_id,
				'sender_name' => $msg->sender?->username,
				'content' => $msg->content,
				'image_url' => $msg->image_url,
				'sent_at' => $sentAt,
				'created_at' => $msg->created_at ? $msg->created_at->toAtomString() : null,
			]
		]);
	}

	public function markAsRead($taskId)
	{
		$userId = auth()->id();
		
		Message::where('task_id', $taskId)
			->where('receiver_id', $userId)
			->where('is_read', false)
			->update(['is_read' => true]);
		
		return response()->json(['ok' => true]);
	}
}

