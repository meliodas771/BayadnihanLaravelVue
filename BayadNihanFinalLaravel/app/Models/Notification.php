<?php

namespace App\Models;

use App\Events\NotificationCreated;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
	protected $fillable = ['user_id','task_id','type','title','message','data','read','is_read'];

	protected static function booted()
	{
		static::created(function ($notification) {
			broadcast(new NotificationCreated($notification->load('task')))->toOthers();
		});
	}

	public function user() { return $this->belongsTo(User::class); }
	public function task() { return $this->belongsTo(Task::class); }
}

