<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - BayadNihan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .logo {
            color: #2e3a59;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 30px;
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .success-icon {
            color: #48bb78;
        }
        
        .error-icon {
            color: #e53e3e;
        }
        
        .greeting {
            color: #2e3a59;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .message {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .success-message {
            color: #22543d;
            background-color: #c6f6d5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .error-message {
            color: #742a2a;
            background-color: #fed7d7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #2e3a59;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        
        .button:hover {
            background-color: #1a2332;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #718096;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">BayadNihan</div>
        
        @if($success)
            <div class="icon success-icon">✓</div>
            <div class="greeting">Hello {{ $username }}!</div>
            <div class="success-message">
                {{ $message }}
            </div>
            <a href="{{ env('APP_URL_EMAIL', 'http://localhost:3000') }}/auth/login" class="button">
                Go to Login
            </a>
        @else
            <div class="icon error-icon">✕</div>
            <div class="greeting">Verification Failed</div>
            <div class="error-message">
                {{ $message }}
            </div>
            <a href="{{ env('APP_URL_EMAIL', 'http://localhost:3000') }}" class="button">
                Return to Home
            </a>
        @endif
        
        <div class="footer">
            <p>Securely connecting students with campus-based earning opportunities</p>
            <p style="margin-top: 10px; font-size: 12px;">
                &copy; {{ date('Y') }} BayadNihan. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

