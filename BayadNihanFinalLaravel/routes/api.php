<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;

// Public routes
// Serve profile pictures directly from storage
Route::get('/storage/profile_pics/{filename}', [UserController::class, 'getProfilePicture'])->where('filename', '.*');

Route::post('/login', [AuthController::class, 'login']);
// curl -X POST http://127.0.0.1:8000/api/login \
//   -H "Content-Type: application/json" \
//   -H "Accept: application/json" \
//   -d '{
//     "email": "tabania.joshuamarvin@gmail.com",
//     "password": "password123"
//   }'

Route::post('/register', [AuthController::class, 'register']);
// curl -X POST http://127.0.0.1:8000/api/register \
//   -H "Content-Type: application/json" \
//   -H "Accept: application/json" \
//   -d '{
//     "username": "Marvin Meliodas Tabania",
//     "email": "tabania.joshuamarvin@gmail.com",
//     "password": "password123",
//     "password_confirmation": "password123",
//     "role": "poster"
//   }'

Route::get('/auth/check', [AuthController::class, 'checkAuth']);


Route::post('/forgot-password', [AuthController::class, 'sendResetCode']);
# Send Reset Code
// curl -X POST http://127.0.0.1:8000/api/password/forgot-password \
//   -H "Content-Type: application/json" \
//   -H "Accept: application/json" \
//   -d '{
//     "email": "tabania.joshuamarvin@gmail.com"
//   }'

Route::post('/verify-code', [AuthController::class, 'verifyCode']);
# Verify Code
// curl -X POST http://127.0.0.1:8000/api/password/verify-code \
//   -H "Content-Type: application/json" \
//   -H "Accept: application/json" \
//   -d '{
//     "email": "tabania.joshuamarvin@gmail.com",
//     "code": "123456"
//   }'

Route::post('/resend-code', [AuthController::class, 'resendCode']);
# Resend Code
// curl -X POST http://127.0.0.1:8000/api/resend-code \
//   -H "Content-Type: application/json" \
//   -H "Accept: application/json" \
//   -d '{
//     "email": "tabania.joshuamarvin@gmail.com"
//   }'

Route::post('/reset-password', [AuthController::class, 'resetPassword']);
# Reset Password
// curl -X POST http://127.0.0.1:8000/api/reset-password \
//   -H "Content-Type: application/json" \
//   -H "Accept: application/json" \
//   -d '{
//     "email": "tabania.joshuamarvin@gmail.com",
//     "token": "123456",
//     "password": "newpassword123",
//     "password_confirmation": "newpassword123"
//   }'

// Email Verification - API route (returns JSON)
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify.api');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->name('verification.send');

// Contact form (public)
Route::post('/contact', [ContactController::class, 'send']);

// Chatbot questions (public)
Route::get('/chatbot/questions', [UserController::class, 'getChatbotQuestions']);

// Google OAuth ,redirectToGoogle and handleGoogleCallback moved to web.php for session support
// Only the complete-registration endpoint stays here as it's called from frontend
Route::post('/auth/google/complete-registration', [GoogleAuthController::class, 'completeRegistration'])->name('auth.google.complete');

// Broadcasting authentication endpoint for Sanctum
Route::post('/broadcasting/auth', function (Illuminate\Http\Request $request) {
    return Illuminate\Support\Facades\Broadcast::auth($request);
})->middleware('auth:sanctum');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
     //     curl -X POST http://127.0.0.1:8000/api/logout \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    // curl -X GET http://127.0.0.1:8000/api/tasks \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::post('/tasks', [TaskController::class, 'store']);
    //     curl -X POST http://127.0.0.1:8000/api/tasks \
    // -H "Accept: application/json" \
    // -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
    // -H "Content-Type: application/json" \
    // -d '{
    //     "title": "House Cleaning",
    //     "description": "Need help cleaning my apartment",
    //     "category": "cleaning",
    //     "price": 150.00,
    //     "payment_method": "cash"
    // }'

    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    // curl -X GET http://127.0.0.1:8000/api/tasks/5 \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::get('/tasks/{id}/edit', [TaskController::class, 'edit']);
    // curl -X GET http://127.0.0.1:8000/api/tasks/6/edit \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::post('/tasks/{id}', [TaskController::class, 'update']);
    Route::post('/tasks/{id}/publish', [TaskController::class, 'publish']);
    // curl -X POST http://127.0.0.1:8000/api/tasks/6/publish \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::post('/tasks/{id}/apply', [TaskController::class, 'apply']);
    //     curl -X POST http://127.0.0.1:8000/api/tasks/6/apply \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::post('/tasks/{taskId}/applications/{applicationId}/accept', [TaskController::class, 'acceptApplication']);
    //     curl -X POST http://127.0.0.1:8000/api/tasks/6/applications/6/accept \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
    
    Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);
    Route::patch('/tasks/{id}/start', [TaskController::class, 'startTask']);
    Route::patch('/tasks/{id}/pause', [TaskController::class, 'pauseTask']);

    Route::post('/tasks/{id}/cancel', [TaskController::class, 'cancel']);
    //     curl -X POST http://127.0.0.1:8000/api/tasks/1/cancel \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::get('/tasks/{taskId}/attachment', [TaskController::class, 'serveAttachment']);
    //     curl -X GET http://127.0.0.1:8000/api/tasks/1/attachment \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    
    // User
    Route::get('/my-tasks', [UserController::class, 'tasks']);
    //     curl -X GET http://127.0.0.1:8000/api/my-tasks \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::get('/profile', [UserController::class, 'profile']);
    # Get User Profile
    // curl -X GET http://127.0.0.1:8000/api/user/profile \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::post('/profile', [UserController::class, 'updateProfile']);
    
    Route::post('/user/update-username', [UserController::class, 'updateUsername']);

    Route::get('/user/{userId}', [UserController::class, 'viewProfile']);
    # View Public Profile
    // curl -X GET "http://127.0.0.1:8000/api/user/2?context=applicant" \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::patch('/tasks/{id}/completion', [UserController::class, 'updateTaskCompletion']);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    # Get Notifications
    // curl -X GET http://127.0.0.1:8000/api/notifications \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    # Mark Notification as Read
    // curl -X PUT http://127.0.0.1:8000/api/notifications/1/read \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    # Mark All Notifications as Read
    // curl -X PUT http://127.0.0.1:8000/api/notifications/read-all \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::delete('/notifications/{id}', [NotificationController::class, 'delete']);
    // curl -X DELETE http://127.0.0.1:8000/api/notifications/1 \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::delete('/notifications', [NotificationController::class, 'deleteAll']);
    // curl -X DELETE http://127.0.0.1:8000/api/notifications/deleteAll \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
    
    // Messages
    Route::post('/tasks/{taskId}/chat', [MessageController::class, 'store']);
    # Send Message
    // curl -X POST http://127.0.0.1:8000/api/tasks/1/chat \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
    //   -H "Content-Type: application/json" \
    //   -d '{
    //     "content": "Hello, when can you start the task?"
    //   }'

    Route::get('/tasks/{taskId}/chat/messages', [MessageController::class, 'messagesJson']);
     # Get Task Messages
    // curl -X GET http://127.0.0.1:8000/api/tasks/1/chat/messages \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::post('/tasks/{taskId}/chat/mark-read', [MessageController::class, 'markAsRead']);
    # Mark Messages as Read
    // curl -X PUT http://127.0.0.1:8000/api/tasks/1/chat/mark-read \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
    
    // Feedback
    Route::get('/feedbacks/task/{taskId}/create', [FeedbackController::class, 'create']);
    # Get Feedback Create Form
    // curl -X GET http://127.0.0.1:8000/apifeedbacks/tasks/6/create \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::post('/feedbacks/task/{taskId}', [FeedbackController::class, 'store']);
     # Submit Feedback (Poster to Doer)
    // curl -X POST http://127.0.0.1:8000/api/feedbacks/tasks/6 \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
    //   -H "Content-Type: application/json" \
    //   -d '{
    //     "rating": 5,
    //     "reviews": "Excellent work! Very professional."
    //   }'

    Route::post('/feedbacks/doer/task/{taskId}', [FeedbackController::class, 'storeDoerFeedback']);
      # Submit Doer Feedback (Doer to Poster)
    // curl -X POST http://127.0.0.1:8000/api/feedback/doer/tasks/6 \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
    //   -H "Content-Type: application/json" \
    //   -d '{
    //     "rating": 4,
    //     "reviews": "Good communication and clear instructions."
    //   }'
    Route::get('/feedbacks/task/{taskId}', [FeedbackController::class, 'show']);
     # Get Feedback
    // curl -X GET http://127.0.0.1:8000/api/tasks/1/feedback \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
    
    // Reports
    Route::get('/report', [ReportController::class, 'create']);
    # Get Report Create Form
    // curl -X GET "http://127.0.0.1:8000/api/report?user_id=2&task_id=1" \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::post('/report', [ReportController::class, 'store']);
    # Submit Report
    // curl -X POST http://127.0.0.1:8000/api/report \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
    //   -H "Content-Type: application/json" \
    //   -d '{
    //     "reported_username": "problem-user",
    //     "report_type": "doer",
    //     "reason": "Poor work quality",
    //     "description": "The user did not complete the task as agreed upon.",
    //     "task_id": 1
    //   }'

    Route::get('/api/user-tasks', [ReportController::class, 'getUserTasks']);
    # Get User Tasks for Report
    // curl -X GET "http://127.0.0.1:8000/api/api/user-tasks?username=regine" \
    //   -H "Accept: application/json" \
    //   -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

    Route::get('/report/interacted-users', [ReportController::class, 'getInteractedUsers']);
});

// Admin login (public route)
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login');

// Admin routes (no authentication required)
Route::prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    
    // Users management
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
    Route::post('/users/{user}/update-role', [AdminUserController::class, 'updateRole'])->name('admin.users.update-role');
    
    // Tasks management
    Route::get('/tasks', [AdminTaskController::class, 'index'])->name('admin.tasks.index');
    Route::get('/tasks/{task}', [AdminTaskController::class, 'show'])->name('admin.tasks.show');
    Route::delete('/tasks/{task}', [AdminTaskController::class, 'destroy'])->name('admin.tasks.destroy');
    Route::post('/tasks/{task}/update-status', [AdminTaskController::class, 'updateStatus'])->name('admin.tasks.update-status');
    Route::get('/tasks-statistics', [AdminTaskController::class, 'statistics'])->name('admin.tasks.statistics');
    
    // Statistics
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('admin.statistics.index');
    
    // Reports management
    Route::get('/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('admin.reports.show');
    Route::post('/reports/{report}/update-status', [AdminReportController::class, 'updateStatus'])->name('admin.reports.update-status');
    Route::post('/reports/{report}/ban-user', [AdminReportController::class, 'banUser'])->name('admin.reports.ban-user');
    Route::post('/reports/{report}/send-notification', [AdminReportController::class, 'sendNotification'])->name('admin.reports.send-notification');
});

