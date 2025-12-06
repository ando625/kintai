@extends('layouts.app')

@section('title', '勤怠一覧画面 - COACHTECH')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/user/index.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <div class="attendance-wrapper">
        <h1 class="attendance-title">勤怠一覧</h1>

        <div class="month-navigation">
            <a href="{{ route('user.index', ['month' => $prevMonth]) }}" class="nav-button">
                <img class="arrow-icon left-arrow" src="{{ asset('images/arrow.png' )}}" alt="◀︎">
            <span class="nav-text">前日</span>
            </a>
            <div class="current-month">
                <span class="calendar-icon"><img src="{{ asset('images/calender.png') }}" alt="カレンダー"></span>
                {{ $currentMonth->format('Y/m') }}
            </div>
            <a href="{{ route('user.index', ['month' => $nextMonth]) }}" class="nav-button">
            <span class="nav-text">翌日</span>
            <img class="arrow-icon right-arrow" src="{{ asset('images/arrow.png')}}" alt="▶︎">
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
                    <tr @if($att->work_date->isWeekend()) @endif>
                        <td>{{ $att->work_date->translatedFormat('m/d(D)') }}</td>
                        @if($att->id)
                        <td>{{ $att->clock_in ? $att->clock_in->format('H:i') : '' }} </td>
                        <td>{{ $att->clock_out ? $att->clock_out->format('H:i') : '' }}</td>
                        <td>{{ $att->break_hours_formatted ?? '' }}</td>
                        <td>{{ $att->work_hours_formatted ?? ''}}</td>
                        <td>
                            <a href="{{ route('user.show', ['id' => $att->id]) }}" class="detail-link">詳細</a>
                        </td>
                        @else
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>
                            <span class="detail-link">詳細</span>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection