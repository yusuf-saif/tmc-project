<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $newsletter->subject }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FAF8F3; font-family: 'Nunito', 'Helvetica Neue', Arial, sans-serif; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: linear-gradient(135deg, #1A6B72, #0D3F44); padding: 32px 24px; text-align: center; }
        .header img { width: 48px; height: 48px; border-radius: 50%; margin-bottom: 12px; }
        .header h1 { color: #ffffff; font-family: 'Dancing Script', cursive; font-size: 28px; margin: 0; }
        .header p { color: rgba(255,255,255,0.7); font-size: 13px; margin: 4px 0 0; letter-spacing: 1px; text-transform: uppercase; }
        .body-content { padding: 32px 24px; color: #1C1A17; line-height: 1.7; font-size: 15px; }
        .body-content h2 { color: #1A6B72; font-size: 20px; margin-top: 0; }
        .divider { border: none; border-top: 1px solid #E4F2F3; margin: 24px 0; }
        .footer { background: #F5F5F0; padding: 24px; text-align: center; }
        .footer p { color: #6B6760; font-size: 12px; margin: 4px 0; }
        .footer a { color: #1A6B72; text-decoration: none; }
        .gold { color: #C8A84B; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <img src="{{ asset('images/img1.png') }}" alt="TMC" style="width:48px;height:48px;border-radius:50%;">
            <h1>The Muhsinat Club</h1>
            <p>Newsletter</p>
        </div>

        <div class="body-content">
            <h2>{{ $newsletter->subject }}</h2>

            {!! $newsletter->body !!}

            <hr class="divider">

            <p style="font-size:13px;color:#6B6760;">
                Assalamu Alaykum {{ $recipient->name }},
            </p>
        </div>

        <div class="footer">
            <p><strong class="gold">The Muhsinat Club</strong></p>
            <p>Building a community of virtue, one sister at a time.</p>
            <p style="margin-top:12px;">
                <a href="{{ url('/home') }}">Open App</a> &middot;
                <a href="{{ url('/profile/notifications') }}">Notification Settings</a>
            </p>
        </div>
    </div>
</body>
</html>
