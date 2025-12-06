@extends('layouts.unified')

@section('title', '勤怠一覧画面 - COACHTECH')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <div class="attendance-wrapper">
    <h1 class="page-title">{{ $targetDate->format('Y年n月j日') }}の勤怠</h1>

    <div class="date-navigation">
        <a href="{{ route('admin.index', ['date' => $prevDate]) }}" class="attendance-nav-link">
            <img class="arrow-icon left-arrow" src="{{ asset('images/arrow.png' )}}" alt="◀︎">
            <span class="nav-text">前日</span>
        </a>
        <div class="current-date">
            <span class="calendar-icon"><img src="{{ asset('images/calender.png') }}" alt="カレンダー"></span>
            <span class="date-text">{{ $targetDate->format('Y/m/d') }}</span>
        </div>
        <a href="{{ route('admin.index', ['date' => $nextDate]) }}" class="attendance-nav-link">
            <span class="nav-text">翌日</span>
            <img class="arrow-icon right-arrow " src="{{ asset('images/arrow.png')}}" alt="▶︎">
        </a>
    </div>

    <div class="attendance-table-wrapper">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $att)
                <tr>
                    <td>{{ $att->user->name }}</td>
                    <td>{{ $att->clock_in ? $att->clock_in->format('H:i') : '' }}</td>
                    <td>{{ $att->clock_out ? $att->clock_out->format('H:i') : '' }}</td>
                    <td>{{ $att->break_hours_formatted }}</td>
                    <td>{{ $att->work_hours_formatted }}</td>
                    <td><a href="{{ route('admin.attendance.show', $att->id) }}" class="detail-link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
</div>
@endsection