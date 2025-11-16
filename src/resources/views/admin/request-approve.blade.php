@extends('layouts.unified')

@section('title', '修正申請詳細 - COACHTECH')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin/request-approve.css') }}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <div class="content-wrapper">
        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <h1 class="page-title">修正申請詳細</h1>

        <section class="detail-card">
            <dl class="detail-list">

                <div class="detail-row">
                    <dt class="detail-label">名前</dt>
                    <dd class="detail-value">{{ $user->name }}</dd>
                </div>

                <div class="detail-row">
                    <dt class="detail-label">日付</dt>
                    <dd class="detail-value">
                        <span class="date-year">{{ $attendanceRequest->attendance->work_date->format('Y年') }}</span>
                        <span class="date-month">{{ $attendanceRequest->attendance->work_date->format('n月j日') }}</span>
                    </dd>
                </div>

                <div class="detail-row">
                    <dt class="detail-label">出勤・退勤</dt>
                    <span class="detail-value">
                        <span class="time-item">{{ $attendanceRequest->after_clock_in ? \Carbon\Carbon::parse($attendanceRequest->after_clock_in)->format('H:i') : '-' }}</span>
                        <span class="time-separator">～</span>
                        <span class="time-item">{{ $attendanceRequest->after_clock_out ? \Carbon\Carbon::parse($attendanceRequest->after_clock_out)->format('H:i') : '-' }}</span>
                    </dd>
                </div>

                <div class="detail-row">
                    <dt class="detail-label">休憩1</dt>
                    <dd class="detail-value">
                        @php
                            $break1 = $attendanceRequest->breakTimeRequests->get(0);
                            $break2 = $attendanceRequest->breakTimeRequests->get(1);
                        @endphp
                        <span class="time-item">{{ $break1 && $break1->after_start ? \Carbon\Carbon::parse($break1->after_start)->format('H:i') : '-' }}</span>
                        <span class="time-separator">～</span>

                        <span class="time-item">{{ $break1 && $break1->after_end ? \Carbon\Carbon::parse($break1->after_end)->format('H:i') : '-' }}</span>
                    </dd>
                </div>

                <div class="detail-row">
                    <dt class="detail-label">休憩2</dt>
                    <dd class="detail-value">
                        @if($break2 && $break2->after_start && $break2->after_end)
                        <span class="time-item">{{ $break2 && $break2->after_start
                            ? \Carbon\Carbon::parse($break2->after_start)->format('H:i')
                            : '' }}</span>
                        <span class="time-separator">～</span>

                        <span class="time-item">{{ $break2 && $break2->after_end
                            ? \Carbon\Carbon::parse($break2->after_end)->format('H:i')
                            : '' }}</span>
                        @endif
                    </dd>
                </div>

                <div class="detail-row">
                    <dt class="detail-label">備考</dt>
                    <dd class="detail-value">{{ $attendanceRequest->after_remarks }}</dd>
                </div>

            </dl>
        </section>

        <div class="button-wrapper">
            <form action="{{ route('admin.requests.approve.update', $attendanceRequest->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" 
                        class="approve-button"
                        @if($attendanceRequest->status === 'approved') disabled @endif>
                    @if($attendanceRequest->status === 'approved') 承認済み @else 承認 @endif
                </button>
            </form>
        </div>
    </div>
</div>
@endsection