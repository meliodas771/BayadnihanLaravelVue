<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
	protected $fillable = [
		'poster_id','doer_id','title','description','category','price','payment_method','status','attachment_url','is_draft','completion_percentage',
	];

	public function poster() { return $this->belongsTo(User::class, 'poster_id'); }
	public function doer() { return $this->belongsTo(User::class, 'doer_id'); }
	public function applications() { return $this->hasMany(Application::class); }
	public function acceptedApplication() { return $this->hasOne(Application::class)->whereIn('status', ['accepted', 'completed']); }
	public function messages() { return $this->hasMany(Message::class); }
	public function feedback() { return $this->hasOne(Feedback::class); }
	public function feedbacks() { return $this->hasMany(Feedback::class); }
}

