<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'BayadNihan Notification' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fc;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .email-body {
            padding: 30px;
        }
        .email-content {
            color: #2e3a59;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        .email-content h2 {
            color: #2e3a59;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .email-content p {
            margin-bottom: 15px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 16px 36px;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(78, 115, 223, 0.2);
            letter-spacing: 0.5px;
        }
        .button:hover {
            background: linear-gradient(135deg, #224abe 0%, #4e73df 100%);
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(78, 115, 223, 0.4);
        }
        .button-arrow {
            margin-left: 8px;
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        .button:hover .button-arrow {
            transform: translateX(4px);
        }
        .email-footer {
            background-color: #f8f9fc;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #858796;
            border-top: 1px solid #e3e6f0;
        }
        .email-footer p {
            margin: 5px 0;
        }
        .task-details {
            background-color: #f8f9fc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4e73df;
        }
        .task-details strong {
            color: #2e3a59;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-body">
            <div class="email-content">
                {!! $content !!}
            </div>
            <div class="button-container">
                <a href="{{ $appUrl ?? env('APP_URL_EMAIL', 'http://localhost:3000') }}" class="button">Go to BayadNihan <span class="button-arrow">→</span></a>
            </div>
        </div>
        <div class="email-footer">
            <p><strong>BayadNihan</strong> - Campus-Based Earning Platform</p>
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; {{ date('Y') }} BayadNihan. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
