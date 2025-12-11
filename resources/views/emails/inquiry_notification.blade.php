<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Inquiry Notification</title>
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
        .alert-badge {
            display: inline-block;
            background-color: #ff6b6b;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
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
        .inquiry-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .inquiry-item {
            margin: 15px 0;
        }
        .inquiry-label {
            font-size: 13px;
            color: #666666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .inquiry-value {
            font-size: 16px;
            color: #333333;
            font-weight: 500;
            word-break: break-word;
        }
        .message-box {
            background-color: #fff;
            border: 1px solid #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            line-height: 1.6;
        }
        .action-notice {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .action-notice-text {
            font-size: 14px;
            color: #1976D2;
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
            <!-- Header -->
            <div class="email-header">
                <h1>🔔 Himalayan Yogini</h1>
            </div>

            <!-- Body -->
            <div class="email-body">
                <span class="alert-badge">NEW INQUIRY</span>
                
                <p class="greeting">Hello Admin,</p>
                
                <p class="content-text">
                    You have received a new inquiry from a potential customer. Please review the details below and respond promptly.
                </p>

                <!-- Inquiry Details Box -->
                <div class="inquiry-box">
                    <div class="inquiry-item">
                        <div class="inquiry-label">Customer Name</div>
                        <div class="inquiry-value">{{ $name }}</div>
                    </div>
                    
                    <div class="inquiry-item">
                        <div class="inquiry-label">Email Address</div>
                        <div class="inquiry-value">{{ $email }}</div>
                    </div>
                    
                    <div class="inquiry-item">
                        <div class="inquiry-label">Phone Number</div>
                        <div class="inquiry-value">{{ $phone }}</div>
                    </div>
                    
                    @if(isset($service_name) && $service_name)
                    <div class="inquiry-item">
                        <div class="inquiry-label">Service Interested In</div>
                        <div class="inquiry-value">{{ $service_name }}</div>
                    </div>
                    @endif
                    
                    <div class="inquiry-item">
                        <div class="inquiry-label">Message</div>
                        <div class="message-box">{{ $inquiryMessage ?? $message }}</div>
                    </div>
                    
                    <div class="inquiry-item">
                        <div class="inquiry-label">Received At</div>
                        <div class="inquiry-value">{{ $received_at ?? now()->format('F d, Y h:i A') }}</div>
                    </div>
                </div>

                <!-- Action Notice -->
                <div class="action-notice">
                    <div class="action-notice-text">
                        <strong>Action Required:</strong> Please respond to this inquiry within 24 hours to maintain excellent customer service.
                    </div>
                </div>

                <!-- CTA Button -->
                <div style="text-align: center;">
                    <a href="{{ config('app.frontend_url') ?? 'https://himalayanyogini.com' }}/admin/inquiries" class="cta-button">
                        View in Dashboard
                    </a>
                </div>

                <div class="divider"></div>

                <p class="content-text" style="margin-bottom: 0;">
                    Best regards,<br>
                    <strong>Himalayan Yogini System</strong>
                </p>
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <p class="footer-text">
                    This is an automated notification. Please do not reply to this email.
                </p>
                <p class="footer-text">
                    © {{ date('Y') }} Himalayan Yogini. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>