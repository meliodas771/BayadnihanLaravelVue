<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
	protected $fillable = ['task_id','sender_id','receiver_id','content','image_url','sent_at','is_read'];

	protected $casts = [
		'is_read' => 'boolean',
		'sent_at' => 'datetime',
	];

	public function task() { return $this->belongsTo(Task::class); }
	public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
	public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }
	
	/**
	 * Scope to get unread messages for a specific user in a task
	 */
	public function scopeUnreadForUser($query, $taskId, $userId)
	{
		return $query->where('task_id', $taskId)
			->where('receiver_id', $userId)
			->where('is_read', false);
	}
}

