<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Team Invitation - {{ env('APP_NAME', 'Engagyo') }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f4f4;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background-color: #007bff;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header img {
            max-width: 150px;
            height: auto;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-body h1 {
            color: #333333;
            font-size: 24px;
            margin: 0 0 20px 0;
            font-weight: 600;
        }
        .email-body p {
            color: #666666;
            font-size: 16px;
            margin: 0 0 15px 0;
        }
        .invitation-details {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 25px 0;
        }
        .invitation-details p {
            margin: 5px 0;
            color: #333333;
        }
        .invitation-details strong {
            color: #007bff;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .invitation-button {
            display: inline-block;
            padding: 14px 40px;
            background-color: #007bff;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .invitation-button:hover {
            background-color: #0056b3;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .email-footer p {
            color: #999999;
            font-size: 14px;
            margin: 5px 0;
        }
        .email-footer a {
            color: #007bff;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
        }
        .alternative-link {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
        }
        .alternative-link p {
            font-size: 14px;
            color: #856404;
            margin: 0;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <img src="{{ asset('assets/frontend/images/logo.png') }}" alt="{{ env('APP_NAME', 'Engagyo') }}" onerror="this.style.display='none'">
        </div>
        
        <div class="email-body">
            <h1>You've Been Invited to Join a Team!</h1>
            
            <p>Hello,</p>
            
            <p>
                <strong>{{ $teamLeadName }}</strong> has invited you to join their team on <strong>{{ env('APP_NAME', 'Engagyo') }}</strong>.
            </p>
            
            <div class="invitation-details">
                <p><strong>Team Lead:</strong> {{ $teamLeadName }}</p>
                <p><strong>Your Email:</strong> {{ $teamMember->email }}</p>
            </div>
            
            <p>
                Click the button below to accept the invitation and create your account. Once you're set up, you'll be able to collaborate with your team.
            </p>
            
            <div class="button-container">
                <a href="{{ $invitationUrl }}" class="invitation-button">Accept Invitation</a>
            </div>
            
            <div class="divider"></div>
            
            <p style="font-size: 14px; color: #999999;">
                If the button doesn't work, copy and paste this link into your browser:
            </p>
            
            <div class="alternative-link">
                <p>{{ $invitationUrl }}</p>
            </div>
            
            <p style="font-size: 14px; color: #999999; margin-top: 30px;">
                This invitation will expire in 7 days. If you didn't expect this invitation, you can safely ignore this email.
            </p>
        </div>
        
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ env('APP_NAME', 'Engagyo') }}. All rights reserved.</p>
            <p>
                <a href="{{ route('frontend.home') }}">Visit our website</a> | 
                <a href="{{ route('frontend.terms') }}">Terms</a> | 
                <a href="{{ route('frontend.privacy') }}">Privacy</a>
            </p>
        </div>
    </div>
</body>
</html>

