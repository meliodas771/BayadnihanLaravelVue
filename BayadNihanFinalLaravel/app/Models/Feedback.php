<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
	protected $table = 'feedbacks';
	
	protected $fillable = ['task_id', 'from_user_id', 'to_user_id', 'rating', 'reviews'];

	public function task() { return $this->belongsTo(Task::class); }
	public function fromUser() { return $this->belongsTo(User::class, 'from_user_id'); }
	public function toUser() { return $this->belongsTo(User::class, 'to_user_id'); }
}

