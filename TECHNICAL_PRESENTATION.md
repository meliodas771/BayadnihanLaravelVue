## BayadNihan – Technical System Presentation (Backend & Frontend)

This document is written as a **spoken script / outline** you can use to present BayadNihan professionally to your instructor. It focuses on the **most important technical decisions** in both the backend and frontend.

---

## 1. High-Level Overview

BayadNihan is a **campus-focused task marketplace** that connects CARSU students who need help with errands to fellow students who can complete them. It’s built as:

- A **Laravel API backend** that handles authentication, student verification against the school system, task management, messaging, notifications, feedback, and reporting.
- A **Nuxt 3 / Vue 3 SPA frontend** that consumes the API, manages state on the client, and delivers a real-time, modern user experience.

From a technical perspective, our goals were:

- To ensure only **legitimate, currently enrolled CARSU students** can access the system.
- To support **real-time communication** for chat and notifications.
- To enforce **privacy and security** around student data.
- To keep both backend and frontend **modular and maintainable**.

---

## 2. Backend Architecture (Laravel)

### 2.1 Core Domain Models

On the backend, the main business entities are represented as Eloquent models under `app/Models`:

- **`User`**
  - Represents a student account.
  - Stores username (student ID), email, password, role, phone number, profile picture, Google ID, and status flags like `is_active` and trial/subscription metadata.
  - Implements Laravel’s authentication contracts so it can participate in **token-based auth** (via Sanctum tokens generated in `AuthController`).
  - Includes helper methods around **trial status** (`isOnTrial`, `trialExpired`, `trialDaysRemaining`) for subscription logic.
  - Provides an email verification hook through `sendEmailVerificationNotification()`.

- **`Task`**
  - Represents a posted errand or job on the platform.
  - Tasks are connected to a **poster** (the student who creates the task), **applications** from doers, **messages**, **feedbacks**, and **reports**.
  - This model is central to almost every feature: posting, applying, chatting, feedback, and reporting.

- **`Application`**
  - Represents a student’s application to do a task.
  - Stores the relationship between **task** and **doer**, plus status (e.g., pending, accepted).
  - Used in authorization logic for real-time chat channels to ensure only the **poster** and the accepted **doer** can join.

- **`Message`**
  - Stores individual chat messages between students, tied to a specific task.
  - Contains sender/receiver, content, optional image URL, and timestamps.
  - Works together with the `MessageSent` event to provide **real-time task chat**.

- **`Notification`**
  - Represents in-app notifications (e.g., an application accepted, a new message, a task update).
  - Critically, its `booted()` method is wired so that **whenever a notification is created**, a broadcasting event is fired. This is the starting point for **real-time notifications**.

- **`Feedback`**
  - Stores feedback and ratings between users (poster ↔ doer).
  - Drives the reputation system that’s visible on public profile pages.

- **`Report`**
  - Represents reports submitted about users or tasks.
  - Integrates with the `ReportCreated` event to support admin monitoring and future dashboards.

These models are deliberately small and focused. Cross-cutting concerns like notifications and real-time events are handled via **events**, not by bloating the models.

---

### 2.2 Authentication, Authorization, and Student Verification

The central piece for authentication is:

- **`app/Http/Controllers/Api/AuthController.php`**

Key responsibilities:

- **Login**
  - Validates the login request and manually verifies credentials using `Hash::check`.
  - Protects against abuse with **rate limiting** (per email + IP) using Laravel’s `RateLimiter`.
  - On success:
    - Ensures the user’s account is active (`is_active` flag).
    - Issues a **Sanctum personal access token** that the frontend stores and sends on each request.
    - Returns a minimal user profile (id, username, email, role, profile picture) to initialize the frontend state.

- **Registration with School System Integration**
  - Enforces strict validation:
    - `username` must match the student ID format `000-00000`.
    - `email` must be a valid `@carsu.edu.ph` address.
  - Calls an **external school system API** (`SCHOOL_API_URL`) to:
    - Confirm the student ID exists.
    - Confirm the email belongs to that student.
    - Confirm the student is currently **enrolled**.
  - Only if all checks pass is a new user record created.
  - This ensures that **only real, enrolled CARSU students** can join BayadNihan.

- **Account State & Email Integration**
  - Supports account banning via `is_active`.
  - Provides commented hooks to enforce **email verification** before login using Laravel’s email verification notifications.
  - Manages password reset flows and integrates with Laravel’s `Mail` facade where needed.

This design gives you a **security-focused authentication layer** where every user is verified both by email domain and by the official school system.

---

### 2.3 Real-Time Messaging

Real-time messaging is built using **Laravel Broadcasting**, **Laravel Reverb** (WebSockets), and **Laravel Echo** on the frontend.

#### Backend flow

1. **User sends a message** via the API
   - The `MessageController` receives the request, validates it, and creates a new `Message` record in the database.
   - If an image is attached, it is stored and the URL is saved with the message.

2. **Event: `MessageSent`** (`app/Events/MessageSent.php`)
   - The controller then fires `event(new MessageSent($message))`.
   - `MessageSent` implements `ShouldBroadcastNow`, so it is sent immediately over WebSockets.
   - It defines:
     - **Channel:** a private channel named `task.{taskId}` so only the poster and accepted doer can listen.
     - **Payload:** a clean JSON-friendly structure with message id, sender/receiver ids, sender name, content, image URL, and timestamp.
     - **Event name:** `MessageSent` (the frontend listens to `.MessageSent`).

3. **Channel authorization** (`routes/channels.php`)
   - For the `task.{taskId}` channel, authorization ensures that only two types of users can join:
     - The **poster** of the task.
     - The **accepted doer** (based on the `applications` table).
   - This guarantees **privacy**: only the two participants in a given task can see its chat messages.

The result is that whenever a message is saved, it is **immediately pushed** to connected clients who are authorized to be in that task’s private channel.

---

### 2.4 Real-Time Notifications

While chat focuses on one task’s participants, notifications are **user-centric** and designed to keep students informed of important events.

#### Backend flow

1. **Notification as a model** (`app/Models/Notification.php`)
   - Every time a new notification row is created (e.g., for new applications, accepted tasks, completed work), the model’s `booted()` method fires.
   - In `booted()`, on `created`, it calls:
     - `broadcast(new NotificationCreated($notification->load('task')))->toOthers();`
   - This triggers the `NotificationCreated` broadcasting event.

2. **Event: `NotificationCreated`** (`app/Events/NotificationCreated.php`)
   - Also implements `ShouldBroadcastNow`.
   - **Channel:** broadcasts on `user.{userId}` — one channel per user.
   - **Payload:** includes id, message, task id and title, read status, type, title, and creation timestamps (both ISO and human-readable).
   - **Event name:** `notification.created`, consumed on the frontend as `.notification.created`.

3. **User channel authorization** (`routes/channels.php`)
   - For `user.{userId}`, access is allowed only if the authenticated user’s id equals the `{userId}` in the channel name.
   - This ensures that **each user only receives their own notifications**.

Together, this pipeline means that whenever the system creates a notification in the database, the intended user’s UI updates **instantly** without manual refreshing.

---

### 2.5 Scheduling and Subscription Logic

- **`app/Console/Kernel.php`**
  - Uses Laravel’s task scheduling to enforce subscription and trial-related logic.
  - Schedules the command `subscription:check-trials` to run **every minute**, leveraging the helper methods on the `User` model (`isOnTrial`, `trialExpired`, `trialDaysRemaining`).
  - This allows BayadNihan to support **time-limited trial access** and later evolve into a subscription-based model.

This demonstrates that the backend is not just reactive to requests, but also proactively maintains the system state over time.

---

## 3. Frontend Architecture (Nuxt 3 / Vue 3)

On the frontend, the application is structured as a **single-page app** with a clear separation between layout, API communication, real-time integration, and feature pages.

### 3.1 Global Application Shell – `app.vue`

- Serves as the **top-level layout controller**.
- Decides when to render:
  - The full **dashboard layout** (with `Sidebar` + main content region), or
  - A **plain view** (for the landing page and all auth-related pages).
- Hides the dashboard layout on:
  - `/` and the landing page route.
  - All `/auth/*` and password recovery / verification routes.
- Integrates with `useUser()` to:
  - Load the current user on mount (`loadUser()`), preventing UI flicker.
  - Decide whether to render the sidebar based on authentication state and token presence.
- Defines global styles for a responsive, two-column layout with a fixed-width sidebar and flexible main content.

### 3.2 Centralized API Client – `bayadnihan-vue/utils/api.js`

- Implements a `useAPI()` factory that exposes multiple API clients grouped by feature:
  - `authAPI` – login, register, logout, Google auth, forgot/verify/reset password.
  - `tasksAPI` – CRUD operations on tasks, publish/cancel, apply/accept, update status, and attachments.
  - `userAPI` – profile get/update, public profiles, “my tasks”, username updates, task completion percentage.
  - `notificationsAPI` – list, mark read, mark all read, delete, delete all.
  - `messagesAPI` – send messages (including image uploads), fetch messages, mark as read.
  - `feedbackAPI` – create and fetch feedback for tasks and doers.
  - `reportsAPI` – generate and fetch data for the reporting module.
  - `googleAuthAPI` – complete Google-based onboarding.

- Responsibilities:
  - Build the API base URL from runtime configuration.
  - Manage the **auth token** via `localStorage` (`auth_token`), including:
    - Getting the token for each request.
    - Setting or clearing the token on login/logout.
  - Centralize **HTTP logic**:
    - Set `Accept` and `Content-Type` headers.
    - Attach `Authorization: Bearer <token>` when needed.
    - Correctly handle JSON vs `FormData` for file uploads.
  - Normalize **error handling**, especially for Laravel validation errors (422).

This design ensures that Vue components never deal with raw `fetch()` calls directly; they all go through `useAPI()`, making the frontend cleaner and easier to maintain.

### 3.3 Real-Time Client Plugin – `bayadnihan-vue/plugins/echo.client.js`

- Initializes **Laravel Echo** on the client side using `laravel-echo` and `pusher-js` configured for **Laravel Reverb**.
- Reads the auth token from `localStorage` and attaches it as a Bearer token in the `Authorization` header for the `/broadcasting/auth` endpoint.
- Restricts transports to websockets (`ws`, `wss`) and sets the host/port accordingly.
- Binds connection error events for better debugging.
- Exposes the configured Echo instance globally as `$echo` so any component can subscribe to channels and listen for events.

This plugin is the **front-end counterpart** to the backend Broadcasting setup, enabling real-time behaviors.

### 3.4 Sidebar and Notifications – `bayadnihan-vue/components/Sidebar.vue`

- Acts as the **main navigation hub** in the authenticated dashboard.
- Shows:
  - User information (avatar, username).
  - Navigation links to tasks, my tasks, notifications, profile, and footer pages.
  - A **live notification badge** for unread notifications.

- On mount, it:
  - Fetches unread notifications via `notificationsAPI`.
  - Subscribes to the `user.{id}` channel through `$echo`.
  - Listens to `.notification.created` events:
    - Increments the unread notification count as soon as a new notification is broadcast.
    - Optionally refreshes the notification list to reflect the new item.

This component demonstrates the integration of **API-driven state** with **real-time updates**, providing immediate feedback without reloading the page.

### 3.5 Task Detail & Chat – `bayadnihan-vue/pages/tasks/[id].vue`

- Responsible for showing a single task’s details along with its **embedded real-time chat**.
- Fetches:
  - Full task data from `tasksAPI`.
  - Historical chat messages from `messagesAPI`.
- Then joins a **private Echo channel**:
  - `task.{id}`, consistent with the backend’s `MessageSent` event.
- Listens to `.MessageSent`:
  - As soon as a new message is broadcast by the backend, it:
    - Pushes the message into the `messages` array.
    - Increments unread message counts when appropriate.
    - Scrolls the chat to the bottom if the chat window is open.

This page is a key example of **full-stack real-time functionality**: the backend broadcasts domain events, and the frontend updates live.

### 3.6 Profile & Privacy – `bayadnihan-vue/pages/profile/[id].vue`

- Displays a user’s public profile, including:
  - Ratings as poster and as doer.
  - Completed and posted tasks.
  - Feedback from other users.
- Implements a **`maskUsername`** function on the frontend to hide sensitive details of student IDs when showing them publicly.
  - If a username looks like an ID number (`digits` and optional dash), it masks the middle part, e.g. `231-00123` becomes something like `2**-00***`.
  - This demonstrates a **privacy-by-design** approach: even though the system uses real student IDs, public views do not expose full identifiers.

---

## 4. Summary – How to Present This Concisely

When presenting to your instructor, you can summarize the system like this:

- **BayadNihan** is a **real-time, student-verified task marketplace** for CARSU, built on a clean separation between a Laravel API backend and a Nuxt/Vue SPA frontend.
- On the **backend**, highlight:
  - **Strict student verification** using the school’s own API and domain-specific validation rules.
  - A focused set of **domain models** (`User`, `Task`, `Application`, `Message`, `Notification`, `Feedback`, `Report`).
  - **Real-time messaging and notifications** implemented via Laravel Broadcasting, Reverb, and dedicated events (`MessageSent`, `NotificationCreated`) with secure channel authorization.
  - **Email and trial support** via Laravel’s notifications, Mail facade, and a scheduled command for trial checks.
- On the **frontend**, highlight:
  - A **global layout** (`app.vue`) that cleanly separates public/auth pages from the authenticated dashboard.
  - A **centralized API layer** (`utils/api.js`) that manages all HTTP communication, tokens, and error handling.
  - A **real-time client plugin** (`echo.client.js`) and its integration in the `Sidebar` and task chat to provide live notifications and messaging.
  - Thoughtful **privacy features**, such as masked usernames on public profiles.

This framing shows not just that the system works, but that it’s been designed with **security, scalability, and user experience** in mind—exactly what you want to convey in a professional presentation.
