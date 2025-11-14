
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規会員登録 COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
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
        <div class="register-container">
            <h1 class="register-title">会員登録</h1>
            <form class="register-form" method="POST" action="{{ route('register') }}">
            @csrf
            <div class="form-group">
                <label for="name" class="form-label">名前</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}">
                @error('name')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">メールアドレス</label>
                <input type="text" id="email" name="email" class="form-input" value="{{ old('email') }}">
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

            <div class="form-group">
                <label for="password_confirmation" class="form-label">パスワード確認</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-input">
                @error('password_confirmation')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="submit-button">登録する</button>
            </form>

            <div class="login-link">
                <a href="{{ route('login') }}">ログインはこちら</a>
            </div>
        </div>
    </main>
</body>
</html>


