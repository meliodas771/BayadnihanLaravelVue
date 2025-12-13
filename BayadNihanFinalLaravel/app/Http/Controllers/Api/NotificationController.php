<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
	public function index()
	{
		$notifications = Notification::where('user_id', auth()->id())
			->with('task:id,title')
			->orderByDesc('created_at')
			->get();
		return response()->json(['notifications' => $notifications]);
	}

	public function markRead($id)
	{
		$notification = Notification::where('id', $id)->where('user_id', auth()->id())->first();
		if ($notification) {
			$notification->update(['read' => true]);
			return response()->json([
				'success' => true,
				'task_id' => $notification->task_id
			]);
		}
		return response()->json(['error' => 'Notification not found'], 404);
	}

	public function markAllRead()
	{
		Notification::where('user_id', auth()->id())
		->update(['read' => true]);
		return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
	}

	public function delete($id)
	{
		$notification = Notification::where('id', $id)
		->where('user_id', auth()->id())->first();
		if ($notification) {
			$notification->delete();
			return response()->json(['success' => true, 'message' => 'Notification deleted successfully']);
		}
		return response()->json(['error' => 'Notification not found'], 404);
	}

	public function deleteAll()
	{
		Notification::where('user_id', auth()->id())->delete();
		return response()->json(['success' => true, 'message' => 'All notifications deleted successfully']);
	}
}

