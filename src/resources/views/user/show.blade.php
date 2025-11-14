

@extends('layouts.app')

@section('title', '勤怠詳細画面 - COACHTECH')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/user/show.css') }}">
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

        <form action="{{ route('user.attendance.request.store', $attendance->id) }}" method="POST">
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
                                value="{{ old('clock_in', $attendance->clock_in?->format('H:i')) }}"
                                @if($isPending) readonly @endif>
                            <span class="time-separator">～</span>
                            <input type="text" name="clock_out" class="time-input"
                                value="{{ old('clock_out', $attendance->clock_out?->format('H:i')) }}"
                                @if($isPending) readonly @endif>
                            @error('clock_in') <span class="text-red">{{ $message }}</span> @enderror
                            @error('clock_out') <span class="text-red">{{ $message }}</span> @enderror
                        </dd>
                    </div>

                    <!-- 休憩 -->
                    @foreach ($breakTimes as $i => $break)
                        @if($i === 1 && $isPending && empty($break->break_start) && empty($break->break_end))
                            @continue
                        @endif
                        <div class="detail-row">
                            <dt class="detail-label">休憩{{ $i + 1 }}</dt>
                            <dd class="detail-value time-inputs">
                                <input type="text" name="break_times[{{ $i }}][start]" class="time-input"
                                    value="{{ old("break_times.$i.start", $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '') }}"
                                    @if($isPending) readonly @endif>
                                <span class="time-separator">～</span>
                                <input type="text" name="break_times[{{ $i }}][end]" class="time-input"
                                    value="{{ old("break_times.$i.end", $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '') }}"
                                    @if($isPending) readonly @endif>
                                @error("break_times.$i") <span class="text-red">{{ $message }}</span> @enderror
                            </dd>
                        </div>
                    @endforeach

                    <!-- 備考 -->
                    <div class="detail-row">
                        <dt class="detail-label">備考</dt>
                        <dd class="detail-value">
                            <textarea name="remarks" class="remarks-input" @if($isPending) readonly @endif>{{ old('remarks', $attendance->remarks) }}</textarea>
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