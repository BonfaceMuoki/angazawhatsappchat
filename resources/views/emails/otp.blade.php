<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login verification</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #111;">Login verification</h1>
    <p>Hello {{ $user->name }},</p>
    <p>Your one-time verification code is:</p>
    <p style="font-size: 24px; font-weight: bold; letter-spacing: 4px; padding: 16px; background: #f5f5f5; border-radius: 8px;">{{ $code }}</p>
    <p>This code expires in 5 minutes. Do not share it with anyone.</p>
    <p style="color: #666;">If you did not request this, please ignore this email.</p>
</body>
</html>
