<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public $notification;

	public function __construct(Notification $notification)
	{
		$this->notification = $notification;
	}

	public function broadcastOn()
	{
		return new Channel('user.' . $this->notification->user_id);
	}

	public function broadcastWith()
	{
		return [
			'id' => $this->notification->id,
			'message' => $this->notification->message,
			'task_id' => $this->notification->task_id,
			'task_title' => $this->notification->task?->title,
			'read' => $this->notification->read,
			'type' => $this->notification->type,
			'title' => $this->notification->title,
			'created_at' => $this->notification->created_at->toISOString(),
			'created_at_human' => $this->notification->created_at->diffForHumans(),
		];
	}

	public function broadcastAs()
	{
		return 'notification.created';
	}
}

