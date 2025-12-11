<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Created</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f4f4f7;
            color: #333333;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f7;
            padding: 40px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 28px;
            font-weight: 600;
        }
        .email-body {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #333333;
            margin-bottom: 20px;
        }
        .content-text {
            font-size: 15px;
            line-height: 1.6;
            color: #555555;
            margin-bottom: 25px;
        }
        .credentials-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .credential-item {
            margin: 12px 0;
        }
        .credential-label {
            font-size: 13px;
            color: #666666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .credential-value {
            font-size: 16px;
            color: #333333;
            font-weight: 500;
            word-break: break-all;
        }
        .security-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .security-notice-icon {
            display: inline-block;
            font-size: 18px;
            margin-right: 8px;
        }
        .security-notice-text {
            font-size: 14px;
            color: #856404;
            line-height: 1.5;
        }
        .cta-button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .instructions {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .instructions-title {
            font-size: 15px;
            font-weight: 600;
            color: #1976D2;
            margin-bottom: 10px;
        }
        .instructions-list {
            margin: 0;
            padding-left: 20px;
            font-size: 14px;
            color: #555555;
            line-height: 1.8;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer-text {
            font-size: 13px;
            color: #6c757d;
            line-height: 1.6;
            margin: 5px 0;
        }
        .footer-links {
            margin-top: 15px;
        }
        .footer-link {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            margin: 0 10px;
        }
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>🧘 Himalayan Yogini</h1>
            </div>
            <div class="email-body">
                <p class="greeting">Hello <strong>{{ $name }}</strong>,</p>
                <p class="content-text">
                    Welcome to the Himalayan Yogini administrative team! Your admin account has been successfully created. 
                    You now have access to the admin dashboard where you can manage content, users, and platform operations.
                </p>
                <div class="credentials-box">
                    <div class="credential-item">
                        <div class="credential-label">Email Address</div>
                        <div class="credential-value">{{ $email }}</div>
                    </div>
                    <div class="credential-item">
                        <div class="credential-label">Temporary Password</div>
                        <div class="credential-value">{{ $password }}</div>
                    </div>
                </div>
                <div class="security-notice">
                    <span class="security-notice-icon">⚠️</span>
                    <span class="security-notice-text">
                        <strong>Important Security Notice:</strong> Please change your password immediately after your first login. 
                        Never share your credentials with anyone, and ensure you're using a strong, unique password.
                    </span>
                </div>
                <div style="text-align: center;">
                    <a href="{{ config('app.frontend_url') ?? 'https://himalayanyoga.com' }}/admin/login" class="cta-button">
                        Access Admin Dashboard
                    </a>
                </div>
                <div class="instructions">
                    <div class="instructions-title">📋 Getting Started:</div>
                    <ol class="instructions-list">
                        <li>Click the button above or visit the admin login page</li>
                        <li>Enter your email and temporary password</li>
                        <li>Change your password in the account settings</li>
                        <li>Explore the dashboard and familiarize yourself with the features</li>
                    </ol>
                </div>
                <div class="divider"></div>
                <p class="content-text">
                    If you have any questions or need assistance, please don't hesitate to contact the technical support team.
                </p>
                <p class="content-text" style="margin-bottom: 0;">
                    Best regards,<br>
                    <strong>Himalayan Yogini Team</strong>
                </p>
            </div>
            <div class="email-footer">
                <p class="footer-text">
                    This is an automated message. Please do not reply to this email.
                </p>
                <p class="footer-text">
                    © {{ date('Y') }} Himalayan Yogini. All rights reserved.
                </p>
                <div class="footer-links">
                    <a href="{{ config('app.frontend_url') ?? '#' }}/privacy-policy" class="footer-link">Privacy Policy</a>
                    <span style="color: #dee2e6;">|</span>
                    <a href="{{ config('app.frontend_url') ?? '#' }}/terms" class="footer-link">Terms of Service</a>
                    <span style="color: #dee2e6;">|</span>
                    <a href="{{ config('app.frontend_url') ?? '#' }}/support" class="footer-link">Support</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
