<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7fb;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 580px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #4f46e5;
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #0f172a;
        }
        .text {
            font-size: 14px;
            color: #475569;
            margin-bottom: 24px;
        }
        .button-wrapper {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 28px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);
        }
        .footer {
            border-top: 1px solid #f1f5f9;
            padding: 24px 32px;
            font-size: 12px;
            color: #94a3b8;
            background: #fafafa;
        }
        .url-text {
            word-break: break-all;
            color: #64748b;
            font-size: 11px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>KooPik Admin Portal</h1>
        </div>
        <div class="content">
            <p class="greeting">Hello {{ $admin->name }},</p>
            <p class="text">You are receiving this email because we received a password reset request for your administrator account.</p>

            <div class="button-wrapper">
                <a href="{{ $resetUrl }}" class="btn" target="_blank">Reset Password</a>
            </div>

            <p class="text">This password reset link will expire in <strong>60 minutes</strong>.</p>
            <p class="text" style="margin-bottom: 0;">If you did not request a password reset, no further action is required and your account remains completely secure.</p>
        </div>
        <div class="footer">
            <p style="margin: 0;">If you're having trouble clicking the "Reset Password" button, copy and paste this URL into your browser:</p>
            <p class="url-text">{{ $resetUrl }}</p>
        </div>
    </div>
</body>
</html>
