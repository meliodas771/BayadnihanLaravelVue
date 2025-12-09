<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'BayadNihan Notification' }}</title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f5f7fa;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            padding: 20px;
        }
        
        /* Email Container */
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
        }
        
        /* Header */
        .email-header {
            background: linear-gradient(135deg, #1a56db 0%, #1e429f 100%);
            padding: 28px 40px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .email-header a {
            color: #ffffff;
            text-decoration: none;
        }
        
        .logo {
            color: #ffffff;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-decoration: none;
            display: inline-block;
        }
        
        .logo-tagline {
            color: rgba(255, 255, 255, 0.85);
            font-size: 14px;
            margin-top: 6px;
            font-weight: 400;
        }
        
        /* Body */
        .email-body {
            padding: 40px;
        }
        
        .greeting {
            color: #111827;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 24px;
            line-height: 1.4;
        }
        
        .content {
            color: #4b5563;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 32px;
        }
        
        .content p {
            margin-bottom: 16px;
        }
        
        .content p:last-child {
            margin-bottom: 0;
        }
        
        /* Highlight Cards */
        .info-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 22px;
            margin: 28px 0;
            border-left: 4px solid #1a56db;
            border-right: 1px solid #e5e7eb;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-card strong {
            color: #111827;
            font-weight: 600;
        }
        
        /* CTA Button */
        .cta-section {
            text-align: center;
            margin: 40px 0 32px;
            padding: 32px 0;
            border-top: 1px solid #f3f4f6;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .cta-button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #1a56db 0%, #1e429f 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(26, 86, 219, 0.25);
            position: relative;
            overflow: hidden;
        }
        
        .cta-button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 86, 219, 0.35);
        }
        
        .cta-button:hover:before {
            left: 100%;
        }
        
        .button-text {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
        }
        
        .button-arrow {
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        
        .cta-button:hover .button-arrow {
            transform: translateX(4px);
        }
        
        /* Footer */
        .email-footer {
            background: #f8fafc;
            padding: 28px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-text {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        
        .footer-text strong {
            color: #374151;
            font-weight: 600;
        }
        
        .footer-links {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-link {
            color: #1a56db;
            text-decoration: none;
            font-size: 13px;
            margin: 0 12px;
            transition: color 0.2s ease;
        }
        
        .footer-link:hover {
            color: #1e429f;
            text-decoration: underline;
        }
        
        .copyright {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 20px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 600px) {
            body {
                padding: 12px;
            }
            
            .email-body,
            .email-header,
            .email-footer {
                padding: 24px;
            }
            
            .greeting {
                font-size: 18px;
            }
            
            .content {
                font-size: 14px;
            }
            
            .cta-button {
                padding: 14px 32px;
                font-size: 15px;
                width: 100%;
                max-width: 280px;
            }
            
            .footer-link {
                display: block;
                margin: 8px 0;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-wrapper {
                background: #1f2937;
                border-color: #374151;
            }
            
            .greeting {
                color: #f9fafb;
            }
            
            .content {
                color: #d1d5db;
            }
            
            .info-card {
                background: #374151;
                border-color: #4b5563;
            }
            
            .info-card strong {
                color: #f9fafb;
            }
            
            .email-footer {
                background: #111827;
                border-color: #374151;
            }
            
            .footer-text {
                color: #9ca3af;
            }
            
            .footer-text strong {
                color: #e5e7eb;
            }
            
            .copyright {
                color: #6b7280;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            <a href="{{ $appUrl ?? env('APP_URL_EMAIL', 'http://localhost:3000') }}" class="logo">BayadNihan</a>
            <div class="logo-tagline">Earn While You Learn. Help While You Study.</div>
        </div>
        
        <!-- Body -->
        <div class="email-body">
            
            <div class="content">
                {!! $content !!}
            </div>
            
            <!-- Optional Info Card -->
            @if(isset($details))
            <div class="info-card">
                <strong>Details:</strong><br>
                {{ $details }}
            </div>
            @endif
            
            <!-- CTA Section -->
            <div class="cta-section">
                <a href="{{ $appUrl ?? env('APP_URL_EMAIL', 'http://localhost:3000') }}" class="cta-button">
                    <span class="button-text">
                        Access BayadNihan
                        <span class="button-arrow">→</span>
                    </span>
                </a>
                <p style="color: #6b7280; font-size: 13px; margin-top: 12px;">
                    Click the button above to view your account
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <p class="footer-text"><strong>BayadNihan</strong> - Empowering Students Through Micro-Opportunities</p>
            <p class="footer-text">Securely connecting students with campus-based earning opportunities</p>
            
            <div class="footer-links">
                <a href="{{ $appUrl ?? env('APP_URL_EMAIL', 'http://localhost:3000') }}/help" class="footer-link">Help Center</a>
                <a href="{{ $appUrl ?? env('APP_URL_EMAIL', 'http://localhost:3000') }}/contact" class="footer-link">Contact Support</a>
                <a href="{{ $appUrl ?? env('APP_URL_EMAIL', 'http://localhost:3000') }}/privacy" class="footer-link">Privacy Policy</a>
            </div>
            
            <p class="copyright">
                This is an automated email. Please do not reply directly to this message.<br>
                &copy; {{ date('Y') }} BayadNihan. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>