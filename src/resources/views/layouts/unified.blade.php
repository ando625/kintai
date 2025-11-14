<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'COACHTECH')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout/unified.css') }}">
    @yield('styles')
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <a href="{{ route('admin.index') }}">
                    <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH">
                </a>
            </div>
            <nav class="nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="{{ route('admin.index') }}" class="nav-link">勤怠一覧</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.staff.list') }}" class="nav-link">スタッフ一覧</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.requests') }}" class="nav-link">申請一覧</a>
                    </li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="logout-form">
                            @csrf
                            <button type="submit" class="nav-link logout-button">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        @yield('content')
    </main>
</body>
</html>