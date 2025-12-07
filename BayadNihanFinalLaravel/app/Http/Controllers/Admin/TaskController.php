<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Models\Application;
use App\Models\Feedback;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['poster', 'doer']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price') && $request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        $tasks = $query->withCount('applications')
            ->latest()
            ->paginate(20);

        $status_counts = Task::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $price_stats = [
            'min' => Task::min('price'),
            'max' => Task::max('price'),
            'avg' => Task::avg('price'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $tasks,
                'status_counts' => $status_counts,
                'price_stats' => $price_stats,
            ]
        ]);
    }

    public function show(Task $task)
    {
        $task->load(['poster', 'doer', 'applications.doer', 'feedbacks.fromUser']);
        
        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.'
        ]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $request->validate([
            'status' => 'required|in:open,assigned,in_progress,completed,cancelled'
        ]);

        $task->update(['status' => $request->status]);
        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully.',
            'data' => $task->fresh(['poster', 'doer'])
        ]);
    }

    public function statistics()
    {
        $stats = [
            'total_tasks' => Task::count(),
            'open_tasks' => Task::where('status', 'open')->count(),
            'assigned_tasks' => Task::where('status', 'assigned')->count(),
            'in_progress_tasks' => Task::where('status', 'in_progress')->count(),
            'completed_tasks' => Task::where('status', 'completed')->count(),
            'cancelled_tasks' => Task::where('status', 'cancelled')->count(),
            'total_value' => Task::sum('price'),
            'avg_price' => Task::avg('price'),
            'tasks_with_applications' => Task::has('applications')->count(),
            'tasks_without_applications' => Task::doesntHave('applications')->count(),
        ];

        // Daily task creation (last 30 days)
        $daily_tasks = Task::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Price distribution
        $price_ranges = [
            '0-50' => Task::whereBetween('price', [0, 50])->count(),
            '51-100' => Task::whereBetween('price', [51, 100])->count(),
            '101-200' => Task::whereBetween('price', [101, 200])->count(),
            '201-500' => Task::whereBetween('price', [201, 500])->count(),
            '500+' => Task::where('price', '>', 500)->count(),
        ];

        // Top posters
        $top_posters = User::withCount('postedTasks')
            ->orderBy('posted_tasks_count', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'daily_tasks' => $daily_tasks,
                'price_ranges' => $price_ranges,
                'top_posters' => $top_posters,
            ]
        ]);
    }
}
