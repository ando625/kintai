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

                    <!-- 名前 -->
                    <div class="detail-row">
                        <dt class="detail-label">名前</dt>
                        <dd class="detail-value detail-value-name">{{ $user->name }}</dd>
                    </div>

                    <!-- 日付 -->
                    <div class="detail-row">
                        <dt class="detail-label">日付</dt>
                        <dd class="detail-value">
                            <span class="date-year">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                            <span class="date-month">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                        </dd>
                    </div>

                    <!-- 出勤・退勤 -->
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
                                <span class="text-red">出勤時間もしくは退勤時間が不適切な値です</span>
                            @endif
                        </dd>
                    </div>


                    <!-- 休憩 -->
                    <div class="detail-row">
                        <dt class="detail-label">休憩</dt>
                        <dd class="detail-value time-inputs">
                            <input type="text" name="break_times[0][start]" class="time-input"
                            value="{{ old('break_times.0.start', $display['breaks'][0]['start'] ?? '') }}"@if($isPending) readonly @endif>
                            <span class="time-separator">～</span>
                            <input type="text" name="break_times[0][end]" class="time-input"
                            value="{{ old('break_times.0.end', $display['breaks'][0]['end'] ?? '') }}"@if($isPending) readonly @endif>
                            @error('break_times.0')
                                <span class="text-red">{{ $message }}</span>
                            @enderror
                        </dd>
                    </div>
                    <!-- 休憩2 -->
                    @if(!$isPending || !empty($display['breaks'][1]['start']) || !empty($display['breaks'][1]['end']))
                    <div class="detail-row">
                        <dt class="detail-label">休憩2</dt>
                        <dd class="detail-value time-inputs">
                            <input type="text" name="break_times[1][start]" class="time-input"
                            value="{{ old('break_times.1.start', $display['breaks'][1]['start'] ?? '') }}"@if($isPending) readonly @endif>
                            <span class="time-separator">～</span>
                            <input type="text" name="break_times[1][end]" class="time-input"
                            value="{{ old('break_times.1.end', $display['breaks'][1]['end'] ?? '') }}"@if($isPending) readonly @endif>
                            @error('break_times.1')
                                <span class="text-red">{{ $message }}</span>
                            @enderror
                        </dd>
                    </div>
                    @endif


                    <!-- 備考 -->
                    <div class="detail-row">
                        <dt class="detail-label">備考</dt>
                        <dd class="detail-value">
                            <textarea name="remarks" class="remarks-input" @if($isPending) readonly @endif>{{ old('remarks', $display['remarks']) }}</textarea>
                            @error('remarks') <span class="text-red">{{ $message }}</span> @enderror
                        </dd>
                    </div>

                </dl>
            </section>

            @if ($isPending)
                <p class="tent-red">＊修正申請中のため編集できません。</p>
            @else
                <div class="button-wrapper">
                    <button type="submit" class="edit-button">修正</button>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection