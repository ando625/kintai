
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout/default.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
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
        <div class="login-container">
        <h1 class="login-title">ログイン</h1>
        <form action="{{ route('login') }}" method="POST" class="login-form">
            @csrf
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス</label>
                <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}">
                @error('email')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label for="password" class="form-label">パスワード</label>
                <input type="password" id="password" name="password" class="form-input">
                @error('password')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>
            <button type="submit" class="submit-button">ログインする</button>
        </form>
        <div class="register-link">
            <a href="{{ route('register') }}">会員登録はこちら</a>
        </div>
    </div>
    </main>
</body>
</html>
