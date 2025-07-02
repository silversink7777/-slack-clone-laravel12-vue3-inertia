<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>チャンネル招待</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6b7280;
            font-size: 14px;
        }
        .content {
            margin-bottom: 30px;
        }
        .invitation-details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .channel-name {
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
        }
        .inviter-info {
            color: #6b7280;
            margin-bottom: 15px;
        }
        .expires-info {
            color: #dc2626;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .buttons {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #4f46e5;
            color: #ffffff;
        }
        .btn-secondary {
            background-color: #6b7280;
            color: #ffffff;
        }
        .btn-success {
            background-color: #10b981;
            color: #ffffff;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6b7280;
            font-size: 12px;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #92400e;
        }
        .info-box {
            background-color: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Slack Clone</div>
            <div class="title">チャンネル招待</div>
            <div class="subtitle">{{ $inviteeEmail }} 様</div>
        </div>

        <div class="content">
            <p>こんにちは！</p>
            
            <p>{{ $inviter->name }} さんがあなたをチャンネルに招待しました。</p>

            <div class="invitation-details">
                <div class="channel-name">📺 {{ $channel->name }}</div>
                <div class="inviter-info">招待者: {{ $inviter->name }}</div>
                <div class="expires-info">⏰ 有効期限: {{ $invitation->expires_at->format('Y年m月d日 H:i') }}</div>
            </div>

            <div class="warning">
                <strong>⚠️ 注意:</strong> この招待は {{ $invitation->expires_at->format('Y年m月d日 H:i') }} まで有効です。
            </div>

            @if($invitation->invitee_id)
                {{-- 既存ユーザー向け --}}
                <p>このチャンネルに参加するには、以下のボタンをクリックしてください。</p>
            @else
                {{-- 未登録ユーザー向け --}}
                <div class="info-box">
                    <strong>📝 アカウントをお持ちでない場合:</strong><br>
                    この招待を受けるには、まずSlack Cloneのアカウントを作成する必要があります。
                </div>
                <p>以下の手順でチャンネルに参加できます：</p>
                <ol style="margin-left: 20px; margin-bottom: 20px;">
                    <li>「アカウントを作成」ボタンをクリックしてユーザー登録を行ってください</li>
                    <li>登録完了後、自動的にこのチャンネルに参加します</li>
                </ol>
            @endif
        </div>

        <div class="buttons">
            @if($invitation->invitee_id)
                {{-- 既存ユーザー向け --}}
                <a href="{{ url('/invitations?invitation=' . $invitation->id) }}" class="btn btn-primary">招待を確認する</a>
                <a href="{{ url('/') }}" class="btn btn-secondary">アプリにアクセス</a>
            @else
                {{-- 未登録ユーザー向け --}}
                <a href="{{ url('/register?invitation=' . $invitation->id . '&email=' . urlencode($inviteeEmail)) }}" class="btn btn-success">アカウントを作成</a>
                <a href="{{ url('/login?email=' . urlencode($inviteeEmail)) }}" class="btn btn-secondary">既存アカウントでログイン</a>
            @endif
        </div>

        <div class="footer">
            <p>このメールは自動送信されています。返信はできません。</p>
            <p>ご不明な点がございましたら、システム管理者にお問い合わせください。</p>
            <p>&copy; {{ date('Y') }} Slack Clone. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 