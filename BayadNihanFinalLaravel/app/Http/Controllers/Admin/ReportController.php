<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with(['reporter', 'reportedUser', 'task', 'reviewer']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by report type
        if ($request->has('report_type') && $request->report_type) {
            $query->where('report_type', $request->report_type);
        }

        // Search by reporter or reported user
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('reporter', function($q2) use ($search) {
                    $q2->where('username', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('reportedUser', function($q2) use ($search) {
                    $q2->where('username', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $reports = $query->latest()->paginate(20);

        $status_counts = Report::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'reports' => $reports,
                'status_counts' => $status_counts,
            ]
        ]);
    }

    public function show(Report $report)
    {
        $report->load(['reporter', 'reportedUser', 'task', 'reviewer']);
        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    public function updateStatus(Request $request, Report $report)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,reviewing,resolved,dismissed',
            'admin_notes' => 'nullable|string',
        ]);

        $oldStatus = $report->status;
        
        $report->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? $report->admin_notes,
            'reviewed_by' => null, // No authentication required
            'reviewed_at' => now(),
        ]);

        // Send notification to reported user only when status changes to "resolved"
        if ($data['status'] === 'resolved' && $oldStatus !== 'resolved') {
            Notification::create([
                'user_id' => $report->reported_user_id,
                'type' => 'report_warning',
                'title' => '⚠️ Warning: You Have Been Reported',
                'message' => 'You have been reported for ' . $report->report_type . ' behavior. Reason: ' . $report->reason . '. Please review our community guidelines. Repeated violations may result in account suspension.',
                'data' => json_encode([
                    'report_id' => $report->id,
                    'report_type' => $report->report_type,
                    'reason' => $report->reason,
                ]),
                'read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Report status updated successfully.',
            'data' => $report->fresh(['reporter', 'reportedUser', 'task', 'reviewer'])
        ]);
    }

    public function banUser(Request $request, Report $report)
    {
        $user = User::findOrFail($report->reported_user_id);
        $user->update(['is_active' => false]);

        $report->update([
            'status' => 'resolved',
            'admin_notes' => ($report->admin_notes ?? '') . "\n[Admin Action] User banned on " . now()->format('Y-m-d H:i:s'),
            'reviewed_by' => null, // No authentication required
            'reviewed_at' => now(),
        ]);

        // Send notification to the reporter about the ban action
        $actionText = 'User Banned';
        $adminMessage = "Your report has been resolved. The user '{$user->username}' has been banned from the platform due to violations of our community guidelines. Thank you for helping maintain a safe community.";
        
        Notification::create([
            'user_id' => $report->reporter_id,
            'type' => 'report_update',
            'title' => 'Report Resolved - User Banned',
            'message' => "📋 Title: Report Resolved - User Banned\n\n⚖️ Action Taken: {$actionText}\n\n💬 Admin Message:\n{$adminMessage}",
            'data' => json_encode([
                'report_id' => $report->id,
                'action_taken' => 'user_banned',
                'admin_notes' => "User '{$user->username}' has been permanently banned from the platform.",
                'style' => [
                    'background' => 'bg-blue-50',
                    'border' => 'border-blue-200',
                    'text' => 'text-blue-800',
                    'icon_bg' => 'bg-blue-100',
                    'icon_color' => 'text-blue-600'
                ]
            ]),
        ]);

        // Send notification to the reported user about the ban
        Notification::create([
            'user_id' => $report->reported_user_id,
            'type' => 'report_resolution',
            'title' => '⚠️ Account Suspended',
            'message' => "Your account has been suspended due to violations of our community guidelines. Report reason: {$report->reason}. If you believe this is an error, please contact support.",
            'data' => json_encode([
                'report_id' => $report->id,
                'action_taken' => 'account_banned',
                'reason' => $report->reason,
                'report_type' => $report->report_type,
            ]),
            'read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User has been banned successfully and reporter has been notified.',
            'data' => $report->fresh(['reporter', 'reportedUser', 'task', 'reviewer'])
        ]);
    }

    public function sendNotification(Request $request, Report $report)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_taken' => 'required|in:no_action,warning_sent,user_banned,content_removed,other',
        ]);

        // Create notification for the reporter
        $actionText = ucfirst(str_replace('_', ' ', $data['action_taken']));
        
        Notification::create([
            'user_id' => $report->reporter_id,
            'type' => 'report_update',
            'title' => $data['title'],
            'message' => "Action Taken: {$actionText}\n\n Admin Message: {$data['message']}",
            'data' => json_encode([
                'report_id' => $report->id,
                'action_taken' => $data['action_taken'],
                'admin_id' => null, // No authentication required
                'style' => [
                    'background' => 'bg-blue-50',
                    'border' => 'border-blue-200',
                    'text' => 'text-blue-800',
                    'icon_bg' => 'bg-blue-100',
                    'icon_color' => 'text-blue-600'
                ]
            ]),
        ]);

        // Update report status if it's still pending
        if ($report->status === 'pending') {
            $report->update([
                'status' => 'reviewing',
                'reviewed_by' => null, // No authentication required
                'reviewed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification sent to reporter successfully.',
            'data' => $report->fresh(['reporter', 'reportedUser', 'task', 'reviewer'])
        ]);
    }
}
