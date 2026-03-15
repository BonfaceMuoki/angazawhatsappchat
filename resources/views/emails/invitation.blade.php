<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're invited</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #111;">You're invited</h1>
    <p>You have been invited to join {{ config('app.name') }}.</p>
    <p>Click the link below to accept the invitation and create your account:</p>
    <p><a href="{{ config('app.frontend_url', url('/')) }}/accept-invite?token={{ $invitation->token }}" style="display: inline-block; padding: 12px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px;">Accept invitation</a></p>
    <p>Or copy this URL: {{ config('app.frontend_url', url('/')) }}/accept-invite?token={{ $invitation->token }}</p>
    <p>This invitation expires on {{ $invitation->expires_at->format('F j, Y') }}.</p>
</body>
</html>
