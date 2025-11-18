@extends('layouts.unified')

@section('title', '勤怠一覧画面 - COACHTECH')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin/staff-show.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <div class="attendance-wrapper">
        <h1 class="attendance-title">{{ $user->name }}さんの勤怠</h1>

        <div class="month-navigation">
            <a href="{{ route('admin.staff.show', ['id' => $user->id, 'month' => $prevMonth]) }}" class="nav-button">
                <img class="arrow-icon left-arrow" src="{{ asset('images/arrow.png' )}}" alt="◀︎">  前月
            </a>
            <div class="current-month">
                <span class="calendar-icon"><img src="{{ asset('images/calender.png') }}" alt="カレンダー"></span>
                {{ $currentMonth->format('Y/m') }}
            </div>
            <a href="{{ route('admin.staff.show', ['id' => $user->id, 'month' => $nextMonth]) }}" class="nav-button">
                翌月 <img class="arrow-icon right-arrow" src="{{ asset('images/arrow.png')}}" alt="▶︎">
            </a>
        </div>

        <div class="attendance-table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                    <tbody>
                        @foreach ($daysInMonth as $att)
                        <tr @if($att->work_date->isWeekend()) class="weekend" @endif>
                            <td>{{ $att->work_date->translatedFormat('m/d(D)') }}</td>
                            <td>{{ $att->clock_in ? $att->clock_in->format('H:i') : '' }}</td>
                            <td>{{ $att->clock_out ? $att->clock_out->format('H:i') : '' }}</td>
                            <td>{{ $att->break_hours_formatted ?? '' }}</td>
                            <td>{{ $att->work_hours_formatted ?? '' }}</td>
                            <td>
                                @if($att->id)
                                    <a href="{{ route('admin.attendance.show', ['id' => $att->id]) }}" class="detail-link">詳細</a>
                                @else
                                    <span class="no-detail">詳細</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
            </table>
        </div>

        <form action="{{ route('admin.attendance.staff.csv', ['id' => $user->id])}}" method="get">
            <div class="csv-button-wrapper">
                <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}">
                <button class="csv-button" type="submit">CSV出力</button>
            </div>
        </form>
    </div>
</div>
@endsection