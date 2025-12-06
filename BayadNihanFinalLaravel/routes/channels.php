<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;
use App\Models\Task;

// User notification channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
	return (int) $user->id === (int) $userId;
});

// Task chat channel
Broadcast::channel('task.{taskId}', function ($user, $taskId) {
    if (!$user) {
        return false;
    }
    
    $task = Task::find($taskId);
    if (!$task) {
        return false;
    }
    
    // Allow poster
    if ($task->poster_id == $user->id) {
        return true;
    }
    
    // Allow accepted doer
    $isAcceptedDoer = Application::where('task_id', $taskId)
        ->where('doer_id', $user->id)
        ->where('status', 'accepted')
        ->exists();
    
    return $isAcceptedDoer;
});

