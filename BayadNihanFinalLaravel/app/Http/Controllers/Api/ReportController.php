<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\Task;
use App\Models\Application;
use App\Models\Message;
use App\Events\ReportCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function create(Request $request)
    {
        $userId = $request->query('user_id');
        $taskId = $request->query('task_id');
        
        $user = $userId ? User::findOrFail($userId) : null;
        $task = $taskId ? Task::findOrFail($taskId) : null;
        
        return response()->json([
            'user' => $user,
            'task' => $task
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reported_username' => 'required|string',
            'report_type' => 'required|in:poster,doer',
            'reason' => 'required|string|max:255',
            'description' => 'required|string',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        $reportedUser = User::where('username', $data['reported_username'])->first();
        
        if (!$reportedUser) {
            return response()->json(['error' => 'User not found. Please check the username and try again.'], 404);
        }

        if ($reportedUser->id == auth()->id()) {
            return response()->json(['error' => 'You cannot report yourself.'], 400);
        }

        // Verify that the reported user is someone the current user has interacted with
        $currentUserId = auth()->id();
        
        // Check if they've interacted through tasks or messages
        $hasInteracted = false;
        
        // Check through tasks (applications, assigned tasks)
        $hasInteracted = DB::table('applications')
            ->join('tasks', 'applications.task_id', '=', 'tasks.id')
            ->where(function($query) use ($currentUserId, $reportedUser) {
                $query->where(function($q) use ($currentUserId, $reportedUser) {
                    // Current user posted, reported user applied/was assigned
                    $q->where('tasks.poster_id', $currentUserId)
                      ->where('applications.doer_id', $reportedUser->id);
                })->orWhere(function($q) use ($currentUserId, $reportedUser) {
                    // Reported user posted, current user applied/was assigned
                    $q->where('tasks.poster_id', $reportedUser->id)
                      ->where('applications.doer_id', $currentUserId);
                });
            })
            ->exists();
        
        if (!$hasInteracted) {
            // Check through assigned tasks
            $hasInteracted = DB::table('tasks')
                ->where(function($query) use ($currentUserId, $reportedUser) {
                    $query->where(function($q) use ($currentUserId, $reportedUser) {
                        $q->where('poster_id', $currentUserId)
                          ->where('doer_id', $reportedUser->id);
                    })->orWhere(function($q) use ($currentUserId, $reportedUser) {
                        $q->where('poster_id', $reportedUser->id)
                          ->where('doer_id', $currentUserId);
                    });
                })
                ->exists();
        }
        
        if (!$hasInteracted) {
            // Check through messages
            $hasInteracted = DB::table('messages')
                ->where(function($query) use ($currentUserId, $reportedUser) {
                    $query->where(function($q) use ($currentUserId, $reportedUser) {
                        $q->where('sender_id', $currentUserId)
                          ->where('receiver_id', $reportedUser->id);
                    })->orWhere(function($q) use ($currentUserId, $reportedUser) {
                        $q->where('sender_id', $reportedUser->id)
                          ->where('receiver_id', $currentUserId);
                    });
                })
                ->exists();
        }
        
        if (!$hasInteracted) {
            return response()->json(['error' => 'You can only report users you have interacted with through tasks or messages.'], 403);
        }

        // Check if user has already reported this user today
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        
        $existingReport = Report::where('reporter_id', $currentUserId)
            ->where('reported_user_id', $reportedUser->id)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->first();
        
        if ($existingReport) {
            return response()->json([
                'error' => 'You have already submitted a report for this user today. You can only report the same user once per day.'
            ], 400);
        }

        $report = Report::create([
            'reporter_id' => auth()->id(),
            'reported_user_id' => $reportedUser->id,
            'report_type' => $data['report_type'],
            'reason' => $data['reason'],
            'description' => $data['description'],
            'task_id' => $data['task_id'] ?? null,
            'status' => 'pending',
        ]);

        // Broadcast the new report to admin dashboard
        try {
            event(new ReportCreated($report));
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast new report: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully. Our admin team will review it.'
        ]);
    }

    public function getUserTasks(Request $request)
    {
        $username = $request->query('username');
        
        if (!$username) {
            return response()->json(['tasks' => []]);
        }

        $user = User::where('username', $username)->first();
        
        if (!$user) {
            return response()->json(['tasks' => [], 'user_found' => false]);
        }

        $currentUserId = auth()->id();

        // Only show tasks that have a connection to the reporter (current user)
        // When reporting a poster: show tasks where reported user is poster AND current user was assigned/accepted (not just applied)
        $relatedTasks = DB::table('tasks')
            ->where('tasks.poster_id', $user->id)
            ->where(function($query) use ($currentUserId) {
                // Current user was assigned/accepted to this task (not just applied)
                $query->whereExists(function($q) use ($currentUserId) {
                    $q->select(DB::raw(1))
                        ->from('applications')
                        ->whereColumn('applications.task_id', 'tasks.id')
                        ->where('applications.doer_id', $currentUserId)
                        ->whereIn('applications.status', ['accepted', 'completed']);
                })
                // OR current user is the assigned doer
                ->orWhere('tasks.doer_id', $currentUserId);
            })
            ->select('tasks.id', 'tasks.title', 'tasks.status', 'tasks.created_at')
            ->distinct()
            ->orderByDesc('tasks.created_at')
            ->limit(10)
            ->get()
            ->map(function($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'type' => 'posted',
                    'status' => $task->status,
                ];
            });

        // When reporting a doer: show tasks where reported user is doer AND current user is the poster
        // Show all related tasks (assigned, in_progress, completed) where there's a connection
        $completedTasks = DB::table('tasks')
            ->join('applications', 'tasks.id', '=', 'applications.task_id')
            ->where('applications.doer_id', $user->id)
            ->where('tasks.poster_id', $currentUserId) // Only tasks posted by current user
            ->whereIn('applications.status', ['accepted', 'completed'])
            ->select('tasks.id', 'tasks.title', 'tasks.status', 'tasks.created_at')
            ->distinct()
            ->orderByDesc('tasks.created_at')
            ->limit(10)
            ->get()
            ->map(function($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'type' => 'completed',
                    'status' => $task->status,
                ];
            });

        $allTasks = $relatedTasks->concat($completedTasks)->sortByDesc('created_at')->values();

        return response()->json([
            'tasks' => $allTasks,
            'user_found' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'profile_pic' => $user->profile_pic
            ]
        ]);
    }

    public function getInteractedUsers(Request $request)
    {
        $currentUserId = auth()->id();
        
        // Get user IDs from tasks where current user is poster and someone applied/was assigned
        $doerIdsFromPostedTasks = DB::table('applications')
            ->join('tasks', 'applications.task_id', '=', 'tasks.id')
            ->where('tasks.poster_id', $currentUserId)
            ->whereNotNull('applications.doer_id')
            ->pluck('applications.doer_id')
            ->unique();

        $doerIdsFromAssignedTasks = DB::table('tasks')
            ->where('poster_id', $currentUserId)
            ->whereNotNull('doer_id')
            ->pluck('doer_id')
            ->unique();

        // Get user IDs from tasks where current user applied/was assigned
        $posterIdsFromAppliedTasks = DB::table('applications')
            ->join('tasks', 'applications.task_id', '=', 'tasks.id')
            ->where('applications.doer_id', $currentUserId)
            ->whereNotNull('tasks.poster_id')
            ->pluck('tasks.poster_id')
            ->unique();

        $posterIdsFromAssignedTasks = DB::table('tasks')
            ->where('doer_id', $currentUserId)
            ->whereNotNull('poster_id')
            ->pluck('poster_id')
            ->unique();

        // Get user IDs from messages
        $senderIds = DB::table('messages')
            ->where('receiver_id', $currentUserId)
            ->where('sender_id', '!=', $currentUserId)
            ->pluck('sender_id')
            ->unique();

        $receiverIds = DB::table('messages')
            ->where('sender_id', $currentUserId)
            ->where('receiver_id', '!=', $currentUserId)
            ->pluck('receiver_id')
            ->unique();

        // Combine all user IDs
        $allInteractedUserIds = collect()
            ->merge($doerIdsFromPostedTasks)
            ->merge($doerIdsFromAssignedTasks)
            ->merge($posterIdsFromAppliedTasks)
            ->merge($posterIdsFromAssignedTasks)
            ->merge($senderIds)
            ->merge($receiverIds)
            ->unique()
            ->filter()
            ->values();

        if ($allInteractedUserIds->isEmpty()) {
            return response()->json([
                'users' => [],
                'count' => 0
            ]);
        }

        $interactedUsers = User::whereIn('id', $allInteractedUserIds)
            ->select('id', 'username', 'email', 'profile_pic')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic
                ];
            });

        return response()->json([
            'users' => $interactedUsers,
            'count' => $interactedUsers->count()
        ]);
    }
}

