
@extends('layouts.app')

@section('title', '勤怠 - COACHTECH')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/user/check-in.css') }}">
@endsection


@section('content')
<div class="attendance-container">
    <div class="attendance-card">
        <!-- ステータスバッジ -->
        <div class="status-badge 
            @if($status === 'working') status-working 
            @elseif($status === 'break') status-break 
            @endif">
            <span>
                @if($status === 'off_duty')
                    勤務外
                @elseif($status === 'working')
                    出勤中
                @elseif($status === 'break')
                    休憩中
                @elseif($status === 'finished')
                    退勤済
                @endif
            </span>
        </div>

        <!-- 日付表示 -->
        <div class="date-display" id="dateDisplay"></div>

        <!-- 時刻表示 -->
        <div class="time-display" id="timeDisplay"></div>

        <!-- ボタングループ -->
        <div class="button-group">
            <!-- 出勤前 -->
            @if($status === 'off_duty')
                <form action="{{ route('user.clockIn') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">出勤</button>
                </form>
            
            <!-- 出勤中 -->
            @elseif($status === 'working')
                <form action="{{ route('user.clockOut') }}" method="POST" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-primary">退勤</button>
                </form>
                <form action="{{ route('user.breakStart') }}" method="POST" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-secondary">休憩入</button>
                </form>
            
            <!-- 休憩中 -->
            @elseif($status === 'break')
                <form action="{{ route('user.breakEnd') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary">休憩戻</button>
                </form>
            
            <!-- 退勤後 -->
            @elseif($status === 'finished')
                <p class="message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>

<script>
const serverTime = new Date("{{ $serverTime }}"); // PHPのサーバー時刻を JS に渡す

// 日時更新
function updateDateTime() {
    const now = serverTime;

    // 日付フォーマット
    const year = now.getFullYear();
    const month = now.getMonth() + 1;
    const day = now.getDate();
    const weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    const weekday = weekdays[now.getDay()];

    document.getElementById('dateDisplay').textContent = 
        `${year}年${month}月${day}日(${weekday})`;

    // 時刻フォーマット
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('timeDisplay').textContent = `${hours}:${minutes}`;

    // 1秒ごとに時刻を進める
    serverTime.setSeconds(serverTime.getSeconds() + 1);
}

// 初期化
updateDateTime();
setInterval(updateDateTime, 1000); // 1分ごとに更新
</script>
@endsection