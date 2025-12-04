@extends('layouts.unified')

@section('title', '勤怠詳細画面 - COACHTECH')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin/show.css') }}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <div class="content-wrapper">
        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        <h1 class="page-title">勤怠詳細</h1>

        @php
            $isPending = $attendance->latestRequest?->status === 'pending';
        @endphp

        <form action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST">
            @csrf
            <section class="detail-card">
                <dl class="detail-list">
                    <div class="detail-row">
                        <dt class="detail-label">名前</dt>
                        <dd class="detail-value detail-value-name">{{ $user->name }}</dd>
                    </div>

                    <div class="detail-row">
                        <dt class="detail-label">日付</dt>
                        <dd class="detail-value">
                            <span class="date-year">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                            <span class="date-month">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                        </dd>
                    </div>

                    <div class="detail-row">
                        <dt class="detail-label">出勤・退勤</dt>
                        <dd class="detail-value time-inputs">
                            <input type="text" name="clock_in" class="time-input"
                                value="{{ old('clock_in', $display['clock_in']) }}"
                                @if($isPending) readonly @endif>
                            <span class="time-separator">～</span>
                            <input type="text" name="clock_out" class="time-input"
                                value="{{ old('clock_out', $display['clock_out']) }}"
                                @if($isPending) readonly @endif>
                        @if ($errors->has('clock_in') || $errors->has('clock_out'))
                        <div class="text-red time-error">出勤時間もしくは退勤時間が不適切な値です</div>
                        @endif
                        </dd>
                    </div>
                    @php
                        $break1Start = $display['breaks'][0]['start'] ?? '';
                        $break1End   = $display['breaks'][0]['end'] ?? '';
                        $break2Start = $display['breaks'][1]['start'] ?? '';
                        $break2End   = $display['breaks'][1]['end'] ?? '';
                    @endphp
                    <div class="detail-row">
                        <dt class="detail-label">休憩</dt>
                        <dd class="detail-value time-inputs">
                            <input type="text" name="break_times[0][start]" class="time-input"
                            value="{{ old('break_times.0.start', $display['breaks'][0]['start'] ?? '') }}"@if($isPending) readonly @endif>
                            @if(!$isPending || ($break1Start || $break1End))
                            <span class="time-separator">～</span>
                            @endif
                            <input type="text" name="break_times[0][end]" class="time-input"
                            value="{{ old('break_times.0.end', $display['breaks'][0]['end'] ?? '') }}"@if($isPending) readonly @endif>
                            @error('break_times.0')
                            <div class="text-red time-error">{{ $message }}</div>
                        @enderror
                        </dd>
                    </div>

                    @if(!$isPending || $break2Start || $break2End)
                    <div class="detail-row">
                        <dt class="detail-label">休憩2</dt>
                        <dd class="detail-value time-inputs">
                            <input type="text" name="break_times[1][start]" class="time-input"
                                value="{{ old('break_times.1.start', $break2Start) }}" @if($isPending) readonly @endif>
                            <span class="time-separator">～</span>
                            <input type="text" name="break_times[1][end]" class="time-input"
                                value="{{ old('break_times.1.end', $break2End) }}" @if($isPending) readonly @endif>
                            @error('break_times.1')
                                <div class="text-red time-error">{{ $message }}</div>
                            @enderror
                        </dd>
                    </div>
                    @endif

                    <div class="detail-row">
                        <dt class="detail-label">備考</dt>
                        <dd class="detail-value">
                            <textarea name="remarks" class="remarks-input" @if($isPending) readonly @endif>{{ old('remarks', $display['remarks']) }}</textarea>
                            @error('remarks') <div class="text-red">{{ $message }}</div> @enderror
                        </dd>
                    </div>
                </dl>
            </section>
            @if ($isPending)
                <p class="tent-red">*承認待ちのため修正はできません。</p>
            @else
                <div class="button-wrapper">
                    <button type="submit" class="edit-button">修正</button>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection