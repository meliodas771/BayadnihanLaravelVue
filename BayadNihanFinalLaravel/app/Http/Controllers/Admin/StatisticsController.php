<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Task;
use App\Models\Application;
use App\Models\Feedback;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index()
    {
        // Overall statistics
        $overall_stats = [
            'total_users' => User::count(),
            'total_tasks' => Task::count(),
            'total_applications' => Application::count(),
            'total_feedbacks' => Feedback::count(),
            'total_messages' => Message::count(),
            'total_notifications' => Notification::count(),
            'active_users' => User::where('is_active', true)->count(),
            'completed_tasks' => Task::where('status', 'completed')->count(),
            'total_earnings' => Application::where('applications.status', 'accepted')
                ->join('tasks', 'applications.task_id', '=', 'tasks.id')
                ->sum('tasks.price'),

        ];

        // User role distribution
        $user_roles = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role');

        // Task status distribution
        $task_statuses = Task::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Monthly registrations (last 12 months)
        $monthly_registrations = User::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        // Monthly task creation (last 12 months)
        $monthly_tasks = Task::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        // Top users by tasks posted
        $top_posters = User::withCount('postedTasks')
            ->orderBy('posted_tasks_count', 'desc')
            ->take(10)
            ->get();

        // Top users by tasks completed
        $top_doers = User::whereHas('applications', function($query) {
                $query->where('status', 'accepted');
            })
            ->withCount(['applications as completed_tasks_count' => function($query) {
                $query->where('status', 'accepted');
            }])
            ->orderBy('completed_tasks_count', 'desc')
            ->take(10)
            ->get();

        // Price statistics
        $price_stats = [
            'min_price' => Task::min('price'),
            'max_price' => Task::max('price'),
            'avg_price' => Task::avg('price'),
            'median_price' => Task::selectRaw('price')
                ->orderBy('price')
                ->offset(Task::count() / 2)
                ->limit(1)
                ->value('price'),
        ];

        // Application success rate
        $application_stats = [
            'total_applications' => Application::count(),
            'accepted_applications' => Application::where('status', 'accepted')->count(),
            'pending_applications' => Application::where('status', 'pending')->count(),
            'rejected_applications' => Application::where('status', 'rejected')->count(),
        ];

        $application_stats['success_rate'] = $application_stats['total_applications'] > 0 
            ? round(($application_stats['accepted_applications'] / $application_stats['total_applications']) * 100, 2)
            : 0;

        // Recent activity
        $recent_users = User::latest()->take(5)->get();
        $recent_tasks = Task::with('poster')->latest()->take(5)->get();
        $recent_applications = Application::with(['doer', 'task'])->latest()->take(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'overall_stats' => $overall_stats,
                'user_roles' => $user_roles,
                'task_statuses' => $task_statuses,
                'monthly_registrations' => $monthly_registrations,
                'monthly_tasks' => $monthly_tasks,
                'top_posters' => $top_posters,
                'top_doers' => $top_doers,
                'price_stats' => $price_stats,
                'application_stats' => $application_stats,
                'recent_users' => $recent_users,
                'recent_tasks' => $recent_tasks,
                'recent_applications' => $recent_applications,
            ]
        ]);
    }
}
