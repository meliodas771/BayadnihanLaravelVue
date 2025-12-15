BayadNihan - Campus Task Marketplace

Overview
BayadNihan is a real-time, peer-to-peer task marketplace platform designed specifically for university students. It connects students who need help with daily tasks (errands, tutoring, etc.) with fellow students who can complete them for fair compensation.

Built with a modern tech stack featuring Laravel 11 for the backend API and Vue 3/Nuxt 3 for the frontend, BayadNihan provides instant notifications, real-time chat, and a secure environment verified through school system integration.

Features
    For Students (Users)
    Task Management: Create, browse, and apply for tasks in categories like grocery shopping, laundry, tutoring, PowerPoint creation, and more
    Real-Time Communication: Instant messaging with image sharing between task posters and doers
    Application System: Apply to tasks, review applicants, and accept the best candidate
    Feedback & Ratings: Build reputation through dual rating system (both parties rate each other)
    Notifications: Real-time alerts for task updates, applications, and messages
    Profile Management: Public profiles with ratings, completed tasks, and feedback history
     
For Administrators
    Dashboard Analytics: Real-time statistics on users, tasks, and platform activity
    User Management: Search, view, ban/activate, and delete user accounts
    Task Monitoring: View and moderate all tasks across the platform
    Report Management: Real-time alerts and resolution system for user reports
    Visual Charts: Interactive doughnut and bar charts for data visualization

Technology Stack

Backend
    Framework: Laravel 11 (PHP 8.2+)
    Authentication: Laravel Sanctum (Token-based)
    Real-Time: Laravel Reverb (WebSockets)
    Database: SQLite/MySQL
    Queue System: Laravel Queues for background jobs
    Email: Laravel Mail with SMTP support

Frontend
    User App: Vue 3 + Nuxt 3 (JavaScript)
    Admin App: Vue 3 + Nuxt 3 (TypeScript) + Nuxt UI
    State Management: Pinia
    Real-Time Client: Laravel Echo + Pusher.js
    Styling: TailwindCSS + Nuxt UI components

Key Dependencies
    Laravel Reverb: WebSocket server for real-time features
    Laravel Sanctum: API authentication
    Laravel Socialite: Social authentication integration
    Vue 3 Composition API: Modern reactive frontend

Prerequisites
System Requirements
    PHP: 8.2 or higher
    Node.js: 18.0 or higher
    Composer: Latest version
    npm: Latest version
    Database: SQLite (default) or MySQL 5.7+

Development Environment
    Web Server: Apache or Nginx (for production)
    Local Development: Laravel Valet, Homestead, or XAMPP
    Git: For version control

Running the Application
Development Mode (5 Terminals Required)

# Terminal 1: Backend API Server
cd BayadNihanFinalLaravel
php artisan serve
# → http://localhost:8000

# Terminal 2: WebSocket Server
cd BayadNihanFinalLaravel
php artisan reverb:start
# → ws://localhost:8080

# Terminal 3: Queue Worker (Background Jobs)
cd BayadNihanFinalLaravel
php artisan queue:work

# Terminal 4: User Frontend
cd bayadnihan-vue
npm run dev
# → http://localhost:3000

# Terminal 5: Admin Dashboard (Optional)
cd BayadnihanAdminDashboard-vue
npm run dev
# → http://localhost:3001

License
This project is licensed under the MIT License.

Support
For technical support or questions, please refer to the technical documentation or contact the development team.