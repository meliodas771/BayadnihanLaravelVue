<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public int $taskId;
	public array $payload;

	public function __construct(Message $message)
	{
		$this->taskId = (int) $message->task_id;
		$this->payload = [
			'id' => (int) $message->id,
			'sender_id' => (int) $message->sender_id,
			'receiver_id' => (int) $message->receiver_id,
			'sender_name' => optional($message->sender)->username,
			'content' => $message->content,
			'image_url' => $message->image_url,
			'sent_at' => optional($message->sent_at)->toAtomString(),
		];
	}

	public function broadcastOn(): Channel
	{
		return new PrivateChannel('task.' . $this->taskId);
	}

	public function broadcastWith(): array
	{
		return $this->payload;
	}

	public function broadcastAs(): string
	{
		return 'MessageSent';
	}
}

