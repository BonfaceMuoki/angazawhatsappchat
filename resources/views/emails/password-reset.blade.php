<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your password</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #111;">Reset your password</h1>
    <p>Hello {{ $user->name }},</p>
    <p>We received a request to reset your password. Use the link below to set a new password:</p>
    <p><a href="{{ config('app.frontend_url', url('/')) }}/reset-password?token={{ $token }}" style="display: inline-block; padding: 12px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px;">Reset password</a></p>
    <p>Or copy this URL: {{ config('app.frontend_url', url('/')) }}/reset-password?token={{ $token }}</p>
    <p>This link expires in 60 minutes.</p>
    <p style="color: #666;">If you did not request a password reset, please ignore this email.</p>
</body>
</html>
