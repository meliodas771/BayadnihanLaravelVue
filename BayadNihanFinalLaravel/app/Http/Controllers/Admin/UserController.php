<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Task;
use App\Models\Application;
use App\Models\Feedback;
use App\Models\Report;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        $users = $query->withCount(['postedTasks as posted_tasks_count', 'applications as applied_tasks_count'])
            ->latest()
            ->paginate(20);

        $role_counts = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role');

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'role_counts' => $role_counts,
            ]
        ]);
    }

    public function show(User $user)
    {
        $user->loadCount(['postedTasks as posted_tasks_count', 'applications as applied_tasks_count']);
        
        // Get user's posted tasks
        $posted_tasks = Task::where('poster_id', $user->id)
            ->with('applications')
            ->latest()
            ->paginate(10, ['*'], 'posted_page');

        // Get user's applied tasks
        $applied_tasks = Application::where('doer_id', $user->id)
            ->with(['task.poster'])
            ->latest()
            ->paginate(10, ['*'], 'applied_page');

        // Get user's feedbacks given and received
        $feedbacks_given = Feedback::where('from_user_id', $user->id)->with('task')->latest()->take(5)->get();
        $feedbacks_received = Feedback::where('to_user_id', $user->id)->with('fromUser')->latest()->take(5)->get();

        // Get reports against this user
        $reports = Report::where('reported_user_id', $user->id)
            ->with(['reporter', 'task'])
            ->latest()
            ->get();

        // User statistics
        $user_stats = [
            'total_earnings' => Application::where('applications.doer_id', $user->id)
                ->join('tasks', 'applications.task_id', '=', 'tasks.id')
                ->where('tasks.status', 'completed')
                ->sum('tasks.price'),

            'total_spent' => Task::where('poster_id', $user->id)
                ->where('status', 'completed')
                ->sum('price'),
                
            'completed_tasks' => Application::where('applications.doer_id', $user->id)
                ->join('tasks', 'applications.task_id', '=', 'tasks.id')
                ->where('tasks.status', 'completed')
                ->count(),
                
            'avg_rating' => Feedback::where('to_user_id', $user->id)->avg('rating') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'posted_tasks' => $posted_tasks,
                'applied_tasks' => $applied_tasks,
                'feedbacks_given' => $feedbacks_given,
                'feedbacks_received' => $feedbacks_received,
                'user_stats' => $user_stats,
                'reports' => $reports,
            ]
        ]);
    }

    public function destroy(User $user)
    {
        // Prevent deleting admin users
        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete admin users.'
            ], 403);
        }

        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        return response()->json([
            'success' => true,
            'message' => "User {$status} successfully.",
            'data' => $user->fresh()
        ]);
    }

    /**
     * Update a user's role (admin-only area)
     */
    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => 'required|in:poster,doer,both',
        ]);

        // Prevent changing admin users
        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change role of admin users.'
            ], 403);
        }

        $user->role = $data['role'];
        // Clear any trial data when admin manually changes role
        $user->trial_ends_at = null;
        $user->subscription_status = 'active';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully.',
            'data' => $user->fresh()
        ]);
    }
}
