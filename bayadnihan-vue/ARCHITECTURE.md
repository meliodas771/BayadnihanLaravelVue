# BayadNihan Vue Application Architecture

## Overview
This document provides a brief and precise explanation of the BayadNihan Vue application structure, focusing on where functions are processed, where HTTP API calls reside, and how the application is organized.

## Application Structure

### Core Files
- **`app.vue`** - Main application layout wrapper that handles sidebar visibility and authentication state
- **`utils/api.js`** - Centralized API service layer (all HTTP requests)
- **`composables/useUser.js`** - User state management composable

### Pages Directory Structure
```
pages/
├── auth/                    # Authentication pages
│   ├── login.vue           # User login
│   ├── register.vue        # User registration
│   ├── forgot-password.vue # Password recovery
│   ├── verify-code.vue     # Code verification
│   ├── reset-password.vue  # Password reset
│   ├── callback.vue        # OAuth callback
│   └── google-role-selection.vue # Google auth role selection
├── tasks/                   # Task management pages
│   ├── index.vue           # Task listing (browse tasks)
│   ├── create.vue          # Create new task
│   ├── [id].vue            # Task detail view
│   └── edit/[id].vue      # Edit existing task
├── profile/                 # User profile pages
│   ├── index.vue           # Current user's profile
│   └── [id].vue            # Public user profile view
├── notifications/           # Notifications
│   └── index.vue           # Notification list
├── report/                  # Reporting
│   └── create.vue          # Create report
├── footer/                   # Footer pages (static)
│   ├── contact.vue
│   ├── faq.vue
│   ├── privacy.vue
│   ├── safety.vue
│   └── terms.vue
├── index.vue                # Landing page
├── LandingPage.vue          # Alternative landing page
└── my-tasks.vue             # User's task management
```

## HTTP API Layer - `utils/api.js`

### Overview
All HTTP API calls are centralized in `utils/api.js`. This file exports a `useAPI()` composable that provides organized API methods grouped by functionality.

### API Base Configuration
- **Base URL**: Configured via `useRuntimeConfig().public.apiBaseUrl` (defaults to `http://localhost:8000/api`)
- **Authentication**: Uses Bearer token stored in `localStorage` as `auth_token`
- **Headers**: Automatically includes `Authorization: Bearer {token}` for authenticated requests

### Core Functions

#### 1. **Authentication Helpers**
- `getAuthToken()` - Retrieves token from localStorage
- `setAuthToken(token)` - Stores/removes token in localStorage
- `getHeaders(includeAuth, contentType)` - Builds request headers with optional auth

#### 2. **Generic Request Function**
- `apiRequest(endpoint, options)` - Core HTTP request handler
  - Handles GET, POST, PATCH, DELETE methods
  - Automatically handles FormData vs JSON
  - Error handling for Laravel validation (422) and other errors
  - Returns parsed JSON response

### API Groups

#### **authAPI** - Authentication
- `login(email, password, remember)` - POST `/login`
- `register(userData)` - POST `/register`
- `logout()` - POST `/logout`
- `checkAuth()` - GET `/auth/check`
- `forgotPassword(email)` - POST `/forgot-password`
- `verifyCode(email, code)` - POST `/verify-code`
- `resendCode(email)` - POST `/resend-code`
- `resetPassword(email, code, password, password_confirmation)` - POST `/reset-password`

#### **tasksAPI** - Task Management
- `getAll()` - GET `/tasks` - Fetch all published tasks
- `getById(id)` - GET `/tasks/{id}` - Get task details
- `getEdit(id)` - GET `/tasks/{id}/edit` - Get task for editing
- `create(taskData)` - POST `/tasks` - Create new task (FormData)
- `update(id, taskData)` - POST `/tasks/{id}` - Update task (FormData)
- `publish(id)` - POST `/tasks/{id}/publish` - Publish draft task
- `apply(id)` - POST `/tasks/{id}/apply` - Apply to task
- `acceptApplication(taskId, applicationId)` - POST `/tasks/{taskId}/applications/{applicationId}/accept`
- `updateStatus(id, status)` - PATCH `/tasks/{id}/status`
- `startTask(id)` - PATCH `/tasks/{id}/start`
- `pauseTask(id, reason)` - PATCH `/tasks/{id}/pause`
- `cancel(id)` - POST `/tasks/{id}/cancel`
- `getAttachment(taskId)` - Returns attachment URL

#### **userAPI** - User Profile & Actions
- `getProfile()` - GET `/profile` - Get current user profile
- `updateProfile(profileData)` - POST `/profile` - Update profile (FormData)
- `getPublicProfile(userId)` - GET `/user/{userId}` - Get public user profile
- `getMyTasks()` - GET `/my-tasks` - Get user's tasks
- `updateTaskCompletion(taskId, completionPercentage)` - PATCH `/tasks/{taskId}/completion`
- `updateUsername(username, email)` - POST `/user/update-username`

#### **notificationsAPI** - Notifications
- `getAll()` - GET `/notifications` - Get all notifications
- `markRead(id)` - POST `/notifications/{id}/read` - Mark as read
- `markAllRead()` - POST `/notifications/read-all` - Mark all as read
- `delete(id)` - DELETE `/notifications/{id}` - Delete notification
- `deleteAll()` - DELETE `/notifications` - Delete all notifications

#### **messagesAPI** - Task Messaging
- `send(taskId, message, image)` - POST `/tasks/{taskId}/chat` - Send message (FormData)
- `getMessages(taskId)` - GET `/tasks/{taskId}/chat/messages` - Get chat messages
- `markAsRead(taskId)` - POST `/tasks/{taskId}/chat/mark-read` - Mark messages as read

#### **feedbackAPI** - Task Feedback
- `create(taskId)` - GET `/feedbacks/task/{taskId}/create` - Get feedback form
- `store(taskId, feedbackData)` - POST `/feedbacks/task/{taskId}` - Submit feedback
- `storeDoerFeedback(taskId, feedbackData)` - POST `/feedbacks/doer/task/{taskId}` - Submit doer feedback
- `get(taskId)` - GET `/feedbacks/task/{taskId}` - Get feedback

#### **reportsAPI** - Reporting
- `create()` - GET `/report` - Get report form
- `store(reportData)` - POST `/report` - Submit report
- `getUserTasks(username)` - GET `/api/user-tasks?username={username}` - Get user's tasks for reporting
- `getInteractedUsers()` - GET `/report/interacted-users` - Get users interacted with

#### **googleAuthAPI** - Google Authentication
- `redirect()` - Redirects to `/api/auth/google` for OAuth
- `completeRegistration(role, googleUserData)` - POST `/auth/google/complete-registration` - Complete Google signup

## How Pages Use the API

### Pattern
All pages follow a consistent pattern:

1. **Import the API composable**:
   ```javascript
   import { useAPI } from '~/utils/api';
   const { authAPI, tasksAPI, userAPI } = useAPI();
   ```

2. **Make API calls in lifecycle hooks or methods**:
   ```javascript
   onMounted(async () => {
     const response = await tasksAPI.getAll();
     tasks.value = response.tasks;
   });
   ```

3. **Handle responses and errors**:
   ```javascript
   try {
     const response = await authAPI.login(email, password);
     if (response.success) {
       // Handle success
     }
   } catch (error) {
     // Handle error
   }
   ```

### Page-by-Page API Usage

#### Authentication Pages
- **`auth/login.vue`**: Uses `authAPI.login()`, `googleAuthAPI.redirect()`
- **`auth/register.vue`**: Uses `authAPI.register()`
- **`auth/forgot-password.vue`**: Uses `authAPI.forgotPassword()`
- **`auth/verify-code.vue`**: Uses `authAPI.verifyCode()`, `authAPI.resendCode()`
- **`auth/reset-password.vue`**: Uses `authAPI.resetPassword()`
- **`auth/callback.vue`**: Handles OAuth callback
- **`auth/google-role-selection.vue`**: Uses `googleAuthAPI.completeRegistration()`

#### Task Pages
- **`tasks/index.vue`**: Uses `tasksAPI.getAll()` - Fetches and displays all tasks
- **`tasks/create.vue`**: Uses `tasksAPI.create()` - Creates new task (draft or published)
- **`tasks/[id].vue`**: Uses `tasksAPI.getById()`, `tasksAPI.apply()`, `tasksAPI.acceptApplication()`, `tasksAPI.updateStatus()`, `tasksAPI.startTask()`, `tasksAPI.pauseTask()`, `tasksAPI.cancel()`, `tasksAPI.publish()`, `messagesAPI.send()`, `messagesAPI.getMessages()`, `messagesAPI.markAsRead()`, `feedbackAPI.store()`, `feedbackAPI.storeDoerFeedback()`
- **`tasks/edit/[id].vue`**: Uses `tasksAPI.getEdit()`, `tasksAPI.update()`

#### Profile Pages
- **`profile/index.vue`**: Uses `userAPI.getProfile()`, `userAPI.updateProfile()`, `userAPI.getMyTasks()`, `userAPI.updateTaskCompletion()`, `userAPI.updateUsername()`
- **`profile/[id].vue`**: Uses `userAPI.getPublicProfile()`, `reportsAPI.getUserTasks()`

#### Other Pages
- **`notifications/index.vue`**: Uses `notificationsAPI.getAll()`, `notificationsAPI.markRead()`, `notificationsAPI.markAllRead()`, `notificationsAPI.delete()`, `notificationsAPI.deleteAll()`
- **`my-tasks.vue`**: Uses `userAPI.getMyTasks()`
- **`report/create.vue`**: Uses `reportsAPI.create()`, `reportsAPI.store()`, `reportsAPI.getInteractedUsers()`, `reportsAPI.getUserTasks()`
- **`index.vue`**: Uses `authAPI.logout()` for logout functionality

## Data Flow

### Authentication Flow
1. User submits login form → `auth/login.vue`
2. Calls `authAPI.login(email, password)` → `utils/api.js`
3. `apiRequest()` sends POST to `/api/login`
4. Response contains `token` and `user`
5. Token stored in localStorage via `setAuthToken()`
6. User data stored in localStorage
7. `useUser()` composable updates user state

### Task Creation Flow
1. User fills form → `tasks/create.vue`
2. Form data converted to FormData (includes file attachment)
3. Calls `tasksAPI.create(taskData)` → `utils/api.js`
4. `apiRequest()` sends POST with FormData to `/api/tasks`
5. Content-Type set to `null` (browser sets boundary automatically)
6. Response contains created task
7. User redirected to task detail page

### Task Listing Flow
1. Page loads → `tasks/index.vue`
2. `onMounted()` calls `tasksAPI.getAll()`
3. `apiRequest()` sends GET to `/api/tasks`
4. Response contains `tasks` array and `draftTasks` array
5. Tasks displayed in grid with filtering

## Key Features

### FormData Handling
- Tasks and profile updates use FormData for file uploads
- `apiRequest()` automatically detects FormData and sets `Content-Type: null`
- Browser automatically sets `Content-Type: multipart/form-data` with boundary

### Error Handling
- Laravel validation errors (422) are parsed and displayed
- Error messages extracted from `data.errors` object
- Generic errors from `data.error` or `data.message`

### Authentication Token Management
- Token stored in `localStorage.getItem('auth_token')`
- Automatically included in headers: `Authorization: Bearer {token}`
- Token removed on logout

### Request Configuration
- Base URL from Nuxt runtime config
- Default Content-Type: `application/json`
- FormData requests: Content-Type set to `null` (browser handles)
- Auth token included by default (can be disabled with `includeAuth: false`)

## Summary

- **All HTTP API calls**: Located in `utils/api.js`
- **Function processing**: Each page component handles its own business logic
- **API organization**: Grouped by feature (auth, tasks, user, notifications, messages, feedback, reports, googleAuth)
- **Consistent pattern**: All pages import `useAPI()` and call specific API methods
- **Centralized configuration**: Base URL, headers, and authentication handled in one place
- **Type handling**: Automatic JSON/FormData detection and proper Content-Type setting

