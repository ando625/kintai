
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ログイン COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout/default.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <a href="/dmin/login">
                    <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH">
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="admin-login-container">
        <h1 class="admin-login-title">管理者ログイン</h1>
        <form action="{{ route('admin.login.store') }}" method="POST" class="admin-login-form">
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
            <button type="submit" class="submit-button">管理者ログインする</button>
        </form>
    </div>
    </main>
</body>
</html>
