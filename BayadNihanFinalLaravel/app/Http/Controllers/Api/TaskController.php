<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class TaskController extends Controller
{
	public function index(Request $request)
	{
		$status = $request->query('status');
		$category = $request->query('category');
		$user = auth()->user();
		$q = Task::with('poster:id,username')->orderByDesc('created_at');
		
		// Exclude draft tasks from public view
		$q->where('is_draft', false);
		
		// If user is ONLY a poster (not 'both' or 'doer'), show only their own tasks
		// Users with 'both' or 'doer' role should see all available tasks
		if ($user && $user->role === 'poster') {
			$q->where('poster_id', $user->id);
		}
		
		// Only show open tasks by default
		if ($status) {
			$q->where('status', $status);
		} else {
			$q->where('status', 'open');
		}
		
		if ($category) $q->where('category', $category);
		$tasks = $q->get();
		
		// Get draft tasks for current user (poster only)
		$draftTasks = Task::with('poster:id,username')
			->where('is_draft', true)
			->where('poster_id', auth()->id())
			->orderByDesc('created_at')
			->get();
		
		// Check for unread report warnings
		$hasReportWarning = Notification::where('user_id', auth()->id())
			->where('type', 'report_warning')
			->where('read', false)
			->exists();
		
		return response()->json([
			'tasks' => $tasks,
			'draftTasks' => $draftTasks,
			'hasReportWarning' => $hasReportWarning
		]);
	}

	public function create() 
	{ 
		// Only poster and both roles can create tasks
		$user = auth()->user();
		if (!$user || $user->role === 'doer') {
			return response()->json(['error' => 'Only posters can create tasks. Please contact admin to change your role.'], 403);
		}
		return response()->json(['success' => true]);
	}

	public function store(Request $request)
	{
		// Only poster and both roles can create tasks
		$user = auth()->user();
		if (!$user || $user->role === 'doer') {
			return response()->json(['error' => 'Only posters can create tasks. Please contact admin to change your role.'], 403);
		}
		
		// Check if user has reached the limit of 5 active tasks
		// Active tasks are those with status: open, assigned, or in_progress
		// Only check limit if creating a non-draft task (drafts don't count)
		$isDraftInput = $request->input('is_draft', '0');
		$isDraft = ($isDraftInput === '1' || $isDraftInput === 1 || $isDraftInput === true);
		
		if (!$isDraft) {
			$activeTasksCount = Task::where('poster_id', $user->id)
				->whereIn('status', ['open', 'assigned', 'in_progress'])
				->where('is_draft', false)
				->count();
			
			if ($activeTasksCount >= 5) {
				return response()->json([
					'error' => 'You have reached the maximum limit of 5 active tasks. Please complete or cancel some tasks before posting new ones.',
					'active_tasks_count' => $activeTasksCount,
					'max_allowed' => 5
				], 403);
			}
		}
		
		$data = $request->validate([
			'title' => 'required|string',
			'description' => 'required|string',
			'category' => 'nullable|string',
			'price' => 'required|numeric',
			'payment_method' => 'nullable|string',
			'attachment' => [
				'nullable',
				'file',
				'mimes:jpg,jpeg,png,gif,pdf,doc,docx',
				'max:10240',
			],
		], [
			'attachment.mimes' => 'The attachment must be an image (JPG, PNG, GIF) or document (PDF, DOC, DOCX).',
			'attachment.max' => 'The attachment must not be larger than 10MB.',
		]);

		$attachmentPath = null;
		if ($request->hasFile('attachment')) {
			$file = $request->file('attachment');
			
			$blockedExtensions = [
				'php', 'js', 'bat', 'vbs', 'html', 'htm', 'css', 'py', 'rb', 'sh', 'exe', 'jar',
				'com', 'scr', 'vbe', 'jsp', 'asp', 'aspx', 'cgi', 'pl', 'ps1', 'psm1', 'psd1',
				'msi', 'dll', 'deb', 'rpm', 'pkg', 'dmg', 'app', 'apk', 'ipa', 'swf', 'fla'
			];
			
			$extension = strtolower($file->getClientOriginalExtension());
			
			if (in_array($extension, $blockedExtensions)) {
				return response()->json(['error' => 'File type not allowed.'], 400);
			}
			
			$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
			
			if (!in_array($extension, $allowedExtensions)) {
				return response()->json(['error' => 'Invalid file type. Only images (JPG, PNG, GIF) and documents (PDF, DOC, DOCX) are allowed.'], 400);
			}
			
			$allowedMimes = [
				'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
				'application/pdf',
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
			];
			$fileMime = $file->getMimeType();
			
			if (!in_array($fileMime, $allowedMimes)) {
				return response()->json(['error' => 'Invalid file type. Only images and documents are allowed.'], 400);
			}
			
			$randomName = uniqid('task_', true) . '_' . time() . '.' . $extension;
			$path = $file->storeAs('task_attachments', $randomName, 'local');
			$attachmentPath = $path;
		}

		$task = Task::create([
			'poster_id' => auth()->id(),
			'title' => $data['title'],
			'description' => $data['description'],
			'category' => $data['category'] ?? null,
			'price' => $data['price'],
			'payment_method' => $data['payment_method'] ?? null,
			'attachment_url' => $attachmentPath,
			'is_draft' => $isDraft,
			'status' => 'open',
		]);

		$message = $isDraft ? 'Task saved as draft' : 'Task created';
		return response()->json([
			'success' => true,
			'message' => $message,
			'task' => $task
		], 201);
	}

	public function show($id)
	{
		$userId = auth()->id();
		$task = Task::with(['poster:id,username', 'applications' => function($q) {
			$q->with('doer:id,username')->orderBy('applied_at', 'desc');
		}])->findOrFail($id);
		
		// If task is draft, only poster can view it
		if ($task->is_draft && $task->poster_id !== $userId) {
			return response()->json(['error' => 'You do not have permission to view this task'], 403);
		}
		
		// Check if current user has applied
		$userApplication = Application::where('task_id', $id)
			->where('doer_id', $userId)
			->first();
		
		// Count unread messages for this task
		$unreadMessageCount = Message::unreadForUser($id, $userId)->count();
		
		// Load messages for floating chat if user has access
		$messages = collect();
		$hasAccess = $task->poster_id === $userId || 
			($userApplication && $userApplication->status === 'accepted');
		
		if ($hasAccess && in_array($task->status, ['assigned', 'in_progress'])) {
			$messages = Message::where('task_id', $id)
				->with(['sender:id,username'])
				->orderBy('sent_at', 'asc')
				->get();
		}
		
		return response()->json([
			'task' => $task,
			'userApplication' => $userApplication,
			'unreadMessageCount' => $unreadMessageCount,
			'messages' => $messages
		]);
	}

	public function apply($id)
	{
		$userId = auth()->id();

		if (auth()->user()->role === 'poster') {
			return response()->json(['error' => 'Only doers can apply to tasks. Please contact admin to change your role.'], 403);
		}

		$task = Task::find($id);

		if (!$task || $task->status !== 'open') {
			return response()->json(['error' => 'Task not available'], 400);
		}

		if ($task->poster_id === $userId) {
			return response()->json(['error' => 'You cannot apply to your own task'], 400);
		}

		$existingApp = Application::where('task_id', $id)->where('doer_id', $userId)->first();
		if ($existingApp) {
			return response()->json(['error' => 'You have already applied to this task'], 400);
		}

		$hasAcceptedTask = Application::where('doer_id', $userId)
			->where('status', 'accepted')
			->whereHas('task', function($query) {
				$query->whereIn('status', ['assigned', 'in_progress']);
			})
			->exists();

		if ($hasAcceptedTask) {
			return response()->json(['error' => 'You have an assigned task. You cannot apply for another task until you complete your current task.'], 400);
		}

		$application = Application::create([
			'task_id' => $task->id,
			'doer_id' => $userId,
			'status' => 'pending',
			'applied_at' => now(),
		]);

		$doer = auth()->user();
		$doerName = $doer->username;

		Notification::create([
			'user_id' => $task->poster_id,
			'task_id' => $task->id,
			'message' => $doerName . ' has applied to your task "' . $task->title . '"',
		]);

		$poster = User::find($task->poster_id);
		try {
			$appUrl = env('APP_URL_EMAIL', 'http://localhost:3000');
			$content = "<h2>Hello {$poster->username},</h2>";
			$content .= "<p>{$doerName} has applied to your task:</p>";
			$content .= "<div class='task-details'>";
			$content .= "<p><strong>Title:</strong> {$task->title}</p>";
			$content .= "<p><strong>Description:</strong> {$task->description}</p>";
			$content .= "</div>";
			$content .= "<p>Please log in to BayadNihan to manage this application.</p>";
			
			Mail::send('emails.notification', [
				'content' => $content,
				'subject' => 'New Application on Your Task',
				'appUrl' => $appUrl
			], function ($message) use ($poster) {
				$message->to($poster->email)
						->subject('New Application on Your Task');
			});
		} catch (\Exception $e) {
			\Log::error('Failed to send application email: ' . $e->getMessage());
		}

		return response()->json(['success' => true, 'message' => 'Application submitted successfully!']);
	}

	public function acceptApplication($taskId, $applicationId)
	{
		$userId = auth()->id();
		
		return DB::transaction(function () use ($taskId, $applicationId, $userId) {
			$task = Task::lockForUpdate()->find($taskId);
			if (!$task) return response()->json(['error' => 'Task not found'], 404);
			
			if ($task->poster_id !== $userId) {
				return response()->json(['error' => 'Only the task poster can accept applications'], 403);
			}
			
			if ($task->status !== 'open') {
				return response()->json(['error' => 'Task is no longer open'], 400);
			}
			
			$application = Application::where('id', $applicationId)
				->where('task_id', $taskId)
				->where('status', 'pending')
				->first();
				
			if (!$application) {
				return response()->json(['error' => 'Application not found'], 404);
			}
			
			$application->update([
				'status' => 'accepted',
				'decision_at' => now(),
			]);
			
			$rejectedApplicationsForTask = Application::where('task_id', $taskId)
				->where('id', '!=', $applicationId)
				->where('status', 'pending')
				->get();
			
			Application::where('task_id', $taskId)
				->where('id', '!=', $applicationId)
				->where('status', 'pending')
				->update(['status' => 'rejected', 'decision_at' => now()]);
			
			foreach ($rejectedApplicationsForTask as $rejectedApp) {
				$rejectedUser = User::find($rejectedApp->doer_id);

				Notification::create([
					'user_id' => $rejectedApp->doer_id,
					'task_id' => $task->id,
					'message' => 'The poster has rejected your application for "' . $task->title . '"',
				]);

				try {
					$appUrl = env('APP_URL_EMAIL', 'http://localhost:3000');
					$content = "<h2>Hello {$rejectedUser->username},</h2>";
					$content .= "<p>Your application for the task <strong>\"{$task->title}\"</strong> was rejected.</p>";
					$content .= "<p>Thank you for applying. You may check other available tasks in BayadNihan.</p>";
					
					Mail::send('emails.notification', [
						'content' => $content,
						'subject' => 'Your Application Was Not Accepted',
						'appUrl' => $appUrl
					], function ($message) use ($rejectedUser) {
						$message->to($rejectedUser->email)
							->subject('Your Application Was Not Accepted');
					});
				} catch (\Exception $e) {
					\Log::error('Failed to send rejection email: ' . $e->getMessage());
				}
			}
			
			$doerOtherApplications = Application::where('doer_id', $application->doer_id)
				->where('id', '!=', $applicationId)
				->where('status', 'pending')
				->with('task')
				->get();
			
			Application::where('doer_id', $application->doer_id)
				->where('id', '!=', $applicationId)
				->where('status', 'pending')
				->update(['status' => 'rejected', 'decision_at' => now()]);
			
			foreach ($doerOtherApplications as $otherApp) {
				Notification::create([
					'user_id' => $application->doer_id,
					'task_id' => $otherApp->task_id,
					'message' => 'Your application for "' . $otherApp->task->title . '" has been automatically rejected because you were accepted for another task.',
				]);
			}
			
			$task->update([
				'status' => 'assigned',
				'doer_id' => $application->doer_id,
			]);
			
			Notification::create([
				'user_id' => $application->doer_id,
				'task_id' => $task->id,
				'message' => 'Your application for "' . $task->title . '" has been accepted!',
			]);

			$acceptedDoer = User::find($application->doer_id);

			try {
				$appUrl = env('APP_URL_EMAIL', 'http://localhost:3000');
				$content = "<h2>Hello {$acceptedDoer->username},</h2>";
				$content .= "<p><strong>Congratulations!</strong></p>";
				$content .= "<p>Your application for the task <strong>\"{$task->title}\"</strong> has been accepted.</p>";
				$content .= "<p>Please log in to BayadNihan to begin coordinating with the poster.</p>";
				
				Mail::send('emails.notification', [
					'content' => $content,
					'subject' => 'Your Application Has Been Accepted',
					'appUrl' => $appUrl
				], function ($message) use ($acceptedDoer) {
					$message->to($acceptedDoer->email)
							->subject('Your Application Has Been Accepted');
				});
			} catch (\Exception $e) {
				\Log::error('Failed to send accepted application email: ' . $e->getMessage());
			}

			return response()->json(['success' => true, 'message' => 'Application accepted successfully!']);
		});
	}

	public function updateStatus($id, Request $request)
	{
		$request->validate(['status' => 'required|string']);
		$userId = auth()->id();
		
		return DB::transaction(function () use ($id, $request, $userId) {
			$task = Task::where('id', $id)->lockForUpdate()->first();
			
			if (!$task) return response()->json(['error' => 'Task not found'], 404);
			
			if ($request->status === 'completed' && $task->poster_id !== $userId) {
				return response()->json(['error' => 'Only the task poster can mark the task as completed'], 403);
			}
			
		$updateData = ['status' => $request->status];
		
		// If status is being set to completed, automatically set completion_percentage to 100
		if ($request->status === 'completed') {
			$updateData['completion_percentage'] = 100;
		}
		
		$task->update($updateData);
		
		if ($request->status === 'completed') {
				Application::where('task_id', $task->id)
					->where('status', 'accepted')
					->update(['status' => 'completed']);
				
				$application = Application::where('task_id', $task->id)
					->where('status', 'completed')
					->first();
				
				if ($application) {
					$doer = User::find($application->doer_id);
					$doerName = $doer?->username;

					Notification::create([
						'user_id' => $application->doer_id,
						'task_id' => $task->id,
						'message' => 'The task "' . $task->title . '" has been marked as completed by the poster!',
					]);

					try {
						$appUrl = env('APP_URL_EMAIL', 'http://localhost:3000');
						$content = "<h2>Hello {$doerName},</h2>";
						$content .= "<p>The task <strong>\"{$task->title}\"</strong> you worked on has been marked as completed by the poster.</p>";
						$content .= "<p>Thank you for using BayadNihan!</p>";
						
						Mail::send('emails.notification', [
							'content' => $content,
							'subject' => 'Task Completed Notification',
							'appUrl' => $appUrl
						], function ($message) use ($doer) {
							$message->to($doer->email)
									->subject('Task Completed Notification');
						});
					} catch (\Exception $e) {
						\Log::error('Failed to send completion email: ' . $e->getMessage());
					}
				}
				
				Message::where('task_id', $task->id)->delete();
			}
			
			return response()->json(['success' => true, 'message' => 'Task marked as completed! Please provide feedback below.']);
		});
	}

	public function startTask($id)
	{
		$userId = auth()->id();
		
		return DB::transaction(function () use ($id, $userId) {
			$task = Task::lockForUpdate()->find($id);
			
			if (!$task) {
				return response()->json(['error' => 'Task not found'], 404);
			}
			
			if ($task->doer_id !== $userId) {
				return response()->json(['error' => 'Only the assigned doer can start this task'], 403);
			}
			
			if ($task->status !== 'assigned') {
				return response()->json(['error' => 'Task must be assigned before it can be started'], 400);
			}
			
			$task->update(['status' => 'in_progress']);
			
			$doer = auth()->user();
			$doerName = $doer->username;
			
			Notification::create([
				'user_id' => $task->poster_id,
				'task_id' => $task->id,
				'message' => $doerName . ' has started working on your task "' . $task->title . '"',
			]);
			
			$poster = User::find($task->poster_id);
			if ($poster) {
				try {
					$appUrl = env('APP_URL_EMAIL', 'http://localhost:3000');
					$content = "<h2>Hello {$poster->username},</h2>";
					$content .= "<p>{$doerName} has started working on your task:</p>";
					$content .= "<div class='task-details'>";
					$content .= "<p><strong>Title:</strong> {$task->title}</p>";
					$content .= "<p><strong>Description:</strong> {$task->description}</p>";
					$content .= "</div>";
					$content .= "<p>The task is now in progress. Please log in to BayadNihan to coordinate with the doer.</p>";
					
					Mail::send('emails.notification', [
						'content' => $content,
						'subject' => 'Task Started - ' . $task->title,
						'appUrl' => $appUrl
					], function ($message) use ($poster, $task) {
						$message->to($poster->email)
								->subject('Task Started - ' . $task->title);
					});
				} catch (\Exception $e) {
					\Log::error('Failed to send task started email: ' . $e->getMessage());
				}
			}
			
			return response()->json(['success' => true, 'message' => 'Task status updated to "In Progress"!']);
		});
	}

	public function pauseTask($id, Request $request)
	{
		$userId = auth()->id();
		
		$request->validate([
			'reason' => 'required|string|max:500',
		]);
		
		return DB::transaction(function () use ($id, $userId, $request) {
			$task = Task::lockForUpdate()->find($id);
			
			if (!$task) {
				return response()->json(['error' => 'Task not found'], 404);
			}
			
			if ($task->doer_id !== $userId) {
				return response()->json(['error' => 'Only the assigned doer can pause this task'], 403);
			}
			
			if ($task->status !== 'in_progress') {
				return response()->json(['error' => 'Task must be in progress before it can be paused'], 400);
			}
			
			$task->update(['status' => 'assigned']);
			
			$reason = $request->input('reason');
			$doer = auth()->user();
			$doerName = $doer->username;
			
			Notification::create([
				'user_id' => $task->poster_id,
				'task_id' => $task->id,
				'message' => $doerName . ' has paused working on your task "' . $task->title . '". Reason: ' . $reason,
			]);
			
			$poster = User::find($task->poster_id);
			if ($poster) {
				try {
					$appUrl = env('APP_URL_EMAIL', 'http://localhost:3000');
					$content = "<h2>Hello {$poster->username},</h2>";
					$content .= "<p>{$doerName} has paused working on your task:</p>";
					$content .= "<div class='task-details'>";
					$content .= "<p><strong>Title:</strong> {$task->title}</p>";
					$content .= "<p><strong>Description:</strong> {$task->description}</p>";
					$content .= "<p><strong>Reason for pausing:</strong> {$reason}</p>";
					$content .= "</div>";
					$content .= "<p>The task status has been changed back to 'Assigned'. Please log in to BayadNihan to coordinate with the doer.</p>";
					
					Mail::send('emails.notification', [
						'content' => $content,
						'subject' => 'Task Paused - ' . $task->title,
						'appUrl' => $appUrl
					], function ($message) use ($poster, $task) {
						$message->to($poster->email)
								->subject('Task Paused - ' . $task->title);
					});
				} catch (\Exception $e) {
					\Log::error('Failed to send task paused email: ' . $e->getMessage());
				}
			}
			
			return response()->json(['success' => true, 'message' => 'Task paused successfully! The poster has been notified of your reason.']);
		});
	}

	public function cancel($id)
	{
		$userId = auth()->id();
		
		return DB::transaction(function () use ($id, $userId) {
			$task = Task::lockForUpdate()->find($id);
			
			if (!$task) {
				return response()->json(['error' => 'Task not found'], 404);
			}
			
			if ($task->poster_id !== $userId) {
				return response()->json(['error' => 'Only the task poster can cancel this task'], 403);
			}
			
			if ($task->status !== 'open') {
				return response()->json(['error' => 'Can only cancel tasks with open status'], 400);
			}
			
			$task->update(['status' => 'cancelled']);
			
			$pendingApplications = Application::where('task_id', $id)
				->where('status', 'pending')
				->get();
			
			Application::where('task_id', $id)
				->where('status', 'pending')
				->update(['status' => 'rejected', 'decision_at' => now()]);
			
			foreach ($pendingApplications as $application) {
				Notification::create([
					'user_id' => $application->doer_id,
					'task_id' => $task->id,
					'message' => 'The task "' . $task->title . '" has been cancelled by the poster',
				]);

				$doer = User::find($application->doer_id);
				try {
					$appUrl = env('APP_URL_EMAIL', 'http://localhost:3000');
					$content = "<h2>Hello {$doer->username},</h2>";
					$content .= "<p>We wanted to inform you that the task <strong>\"{$task->title}\"</strong> you applied for has been cancelled by the poster.</p>";
					$content .= "<p>Thank you for using BayadNihan.</p>";
					
					Mail::send('emails.notification', [
						'content' => $content,
						'subject' => 'Task Cancelled Notification',
						'appUrl' => $appUrl
					], function ($message) use ($doer) {
						$message->to($doer->email)
								->subject('Task Cancelled Notification');
					});
				} catch (\Exception $e) {
					\Log::error('Failed to send cancellation email: ' . $e->getMessage());
				}
			}

			return response()->json(['success' => true, 'message' => 'Task cancelled successfully']);
		});
	}

	public function serveAttachment($taskId)
	{
		$task = Task::findOrFail($taskId);
		$userId = auth()->id();
		
		if (!$task->attachment_url) {
			return response()->json(['error' => 'Attachment not found'], 404);
		}
		
		$canView = $task->poster_id === $userId;
		
		if (!$canView) {
			$userApplication = Application::where('task_id', $taskId)
				->where('doer_id', $userId)
				->where('status', 'accepted')
				->first();
			$canView = $userApplication !== null;
		}
		
		if (!$canView) {
			return response()->json(['error' => 'You do not have permission to view this attachment.'], 403);
		}
		
		if (!Storage::disk('local')->exists($task->attachment_url)) {
			return response()->json(['error' => 'Attachment file not found.'], 404);
		}
		
		$filePath = Storage::disk('local')->path($task->attachment_url);
		$mimeType = mime_content_type($filePath);
		$extension = strtolower(pathinfo($task->attachment_url, PATHINFO_EXTENSION));
		$inlineExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
		$isInline = in_array($extension, $inlineExtensions);
		$disposition = $isInline ? 'inline' : 'attachment';
		
		return response()->file($filePath, [
			'Content-Type' => $mimeType,
			'Content-Disposition' => $disposition . '; filename="' . basename($task->attachment_url) . '"',
		]);
	}

	public function edit($id)
	{
		$task = Task::findOrFail($id);
		$userId = auth()->id();
		
		if ($task->poster_id !== $userId) {
			return response()->json(['error' => 'You can only edit your own tasks'], 403);
		}
		
		if (!$task->is_draft) {
			return response()->json(['error' => 'Only draft tasks can be edited'], 400);
		}
		
		return response()->json(['task' => $task]);
	}

	public function update($id, Request $request)
	{
		$task = Task::findOrFail($id);
		$userId = auth()->id();
		
		if ($task->poster_id !== $userId) {
			return response()->json(['error' => 'You can only update your own tasks'], 403);
		}
		
		if (!$task->is_draft) {
			return response()->json(['error' => 'Only draft tasks can be updated'], 400);
		}
		
		$data = $request->validate([
			'title' => 'required|string',
			'description' => 'required|string',
			'category' => 'nullable|string',
			'price' => 'required|numeric',
			'payment_method' => 'nullable|string',
			'attachment' => [
				'nullable',
				'file',
				'mimes:jpg,jpeg,png,gif,pdf,doc,docx',
				'max:10240',
			],
		], [
			'attachment.mimes' => 'The attachment must be an image (JPG, PNG, GIF) or document (PDF, DOC, DOCX).',
			'attachment.max' => 'The attachment must not be larger than 10MB.',
		]);

		$attachmentPath = $task->attachment_url;
		
		if ($request->hasFile('attachment')) {
			$file = $request->file('attachment');
			
			$blockedExtensions = [
				'php', 'js', 'bat', 'vbs', 'html', 'htm', 'css', 'py', 'rb', 'sh', 'exe', 'jar',
				'com', 'scr', 'vbe', 'jsp', 'asp', 'aspx', 'cgi', 'pl', 'ps1', 'psm1', 'psd1',
				'msi', 'dll', 'deb', 'rpm', 'pkg', 'dmg', 'app', 'apk', 'ipa', 'swf', 'fla'
			];
			
			$extension = strtolower($file->getClientOriginalExtension());
			
			if (in_array($extension, $blockedExtensions)) {
				return response()->json(['error' => 'File type not allowed.'], 400);
			}
			
			$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
			
			if (!in_array($extension, $allowedExtensions)) {
				return response()->json(['error' => 'Invalid file type. Only images (JPG, PNG, GIF) and documents (PDF, DOC, DOCX) are allowed.'], 400);
			}
			
			$allowedMimes = [
				'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
				'application/pdf',
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
			];
			$fileMime = $file->getMimeType();
			
			if (!in_array($fileMime, $allowedMimes)) {
				return response()->json(['error' => 'Invalid file type. Only images and documents are allowed.'], 400);
			}
			
			if ($task->attachment_url && Storage::disk('local')->exists($task->attachment_url)) {
				Storage::disk('local')->delete($task->attachment_url);
			}
			
			$randomName = uniqid('task_', true) . '_' . time() . '.' . $extension;
			$path = $file->storeAs('task_attachments', $randomName, 'local');
			$attachmentPath = $path;
		}
		
		$isDraftInput = $request->input('is_draft', '1');
		$isDraft = ($isDraftInput === '1' || $isDraftInput === 1 || $isDraftInput === true);
		
		// Check if user is trying to publish the task (changing from draft to published)
		// If so, check if they have reached the limit of 5 active tasks
		if (!$isDraft && $task->is_draft) {
			// Active tasks are those with status: open, assigned, or in_progress
			$activeTasksCount = Task::where('poster_id', $userId)
				->whereIn('status', ['open', 'assigned', 'in_progress'])
				->where('is_draft', false)
				->count();
			
			if ($activeTasksCount >= 5) {
				return response()->json([
					'error' => 'You have reached the maximum limit of 5 active tasks. Please complete or cancel some tasks before publishing this one.',
					'active_tasks_count' => $activeTasksCount,
					'max_allowed' => 5
				], 403);
			}
		}
		
		$task->update([
			'title' => $data['title'],
			'description' => $data['description'],
			'category' => $data['category'] ?? null,
			'price' => $data['price'],
			'payment_method' => $data['payment_method'] ?? null,
			'attachment_url' => $attachmentPath,
			'is_draft' => $isDraft,
		]);

		$message = $isDraft ? 'Draft task updated' : 'Task published successfully!';
		return response()->json([
			'success' => true,
			'message' => $message,
			'task' => $task
		]);
	}

	public function publish($id, Request $request)
	{
		$task = Task::findOrFail($id);
		$userId = auth()->id();
		
		if ($task->poster_id !== $userId) {
			return response()->json(['error' => 'You can only publish your own tasks'], 403);
		}
		
		if (!$task->is_draft) {
			return response()->json(['error' => 'Task is already published'], 400);
		}
		
		// Check if user has reached the limit of 5 active tasks before publishing
		// Active tasks are those with status: open, assigned, or in_progress
		$activeTasksCount = Task::where('poster_id', $userId)
			->whereIn('status', ['open', 'assigned', 'in_progress'])
			->where('is_draft', false)
			->count();
		
		if ($activeTasksCount >= 5) {
			return response()->json([
				'error' => 'You have reached the maximum limit of 5 active tasks. Please complete or cancel some tasks before publishing this one.',
				'active_tasks_count' => $activeTasksCount,
				'max_allowed' => 5
			], 403);
		}
		
		$task->update([
			'is_draft' => false,
		]);

		return response()->json(['success' => true, 'message' => 'Task published successfully!']);
	}
}

