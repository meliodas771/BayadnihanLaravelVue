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
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Hardcoded admin credentials - checked only in controller, no DB password check
        $adminEmail = 'admin@bayadnihan.com';
        $adminPassword = 'admin123';

        // Check credentials only in controller (hardcoded, no database password verification)
        if ($credentials['email'] === $adminEmail && $credentials['password'] === $adminPassword) {
            // Find any admin user for token generation (we don't check password in DB)
            $user = User::where('role', 'admin')->first();
            
            // If no admin user exists, create one (required for Sanctum token)
            if (!$user) {
                $user = User::create([
                    'username' => 'admin',
                    'email' => $adminEmail,
                    'password' => Hash::make($adminPassword),
                    'role' => 'admin',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'profile_pic' => $user->profile_pic,
                ]
            ]);
        }

        return response()->json(['error' => 'Invalid admin credentials'], 401);
    }

    public function index(Request $request)
    {
        // Get statistics
        $stats = [
            'total_users' => User::count(),
            'total_tasks' => Task::count(),
            'total_applications' => Application::count(),
            'total_feedbacks' => Feedback::count(),
            'total_messages' => Message::count(),
            'total_notifications' => Notification::count(),
            'total_reports' => Report::count(),
            'active_tasks' => Task::whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'completed_tasks' => Task::where('status', 'completed')->count(),
            'posters' => User::where('role', 'poster')->count(),
            'doers' => User::where('role', 'doer')->count(),
            'both_roles' => User::where('role', 'both')->count(),
        ];

        // Recent users
        $recent_users = User::latest()->take(10)->get();

        // Recent tasks
        $recent_tasks = Task::with(['poster', 'doer'])->latest()->take(10)->get();

        // Task status distribution
        $task_status_stats = Task::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Monthly user registrations (last 6 months)
        $monthly_users = User::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_users' => $recent_users,
                'recent_tasks' => $recent_tasks,
                'task_status_stats' => $task_status_stats,
                'monthly_users' => $monthly_users,
            ]
        ]);
    }
}
