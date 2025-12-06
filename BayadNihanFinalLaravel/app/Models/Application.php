<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
	protected $fillable = ['task_id', 'doer_id', 'status', 'applied_at', 'decision_at'];

	protected $casts = [
		'applied_at' => 'datetime',
		'decision_at' => 'datetime',
	];

	public function task() { return $this->belongsTo(Task::class); }
	public function doer() { return $this->belongsTo(User::class, 'doer_id'); }
}

