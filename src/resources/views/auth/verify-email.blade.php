
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メール認証 COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth/mail.css') }}">

</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <a href="/login">
                    <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH">
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="verification-container">
            <p class="verification-message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <a href="http://localhost:8025" class="verification-button" target="_blank">
                認証はこちらから
            </a>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="resend-link">
                    認証メールを再送する
                </button>
            </form>
        </div>
    </main>
</body>
</html>

