<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your email</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #111;">Verify your email</h1>
    <p>Hello {{ $user->name }},</p>
    <p>Please verify your email address by clicking the link below:</p>
    <p><a href="{{ config('app.frontend_url', url('/')) }}/verify-email?token={{ $verificationToken }}" style="display: inline-block; padding: 12px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px;">Verify email</a></p>
    <p>Or copy this URL: {{ config('app.frontend_url', url('/')) }}/verify-email?token={{ $verificationToken }}</p>
    <p>This link expires in 24 hours.</p>
</body>
</html>
