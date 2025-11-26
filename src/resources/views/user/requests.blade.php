@extends('layouts.app')

@section('title', '申請 - COACHTECH')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/user/requests.css') }}">
@endsection

@section('content')
<div class="application-container">
    <h1 class="page-title">申請一覧</h1>

    <ul class="border__list tab-container">
        @php
            $tab = request('tab', 'pending');
        @endphp
        <li class="tab-item">
            <a href="{{ route('user.request.list', ['tab' => 'pending']) }}"
            class="tab-button {{ $tab === 'pending' ? 'active' : '' }}">
            承認待ち
            </a>
        </li>
        <li class="tab-item">
            <a href="{{ route('user.request.list', ['tab' => 'approved']) }}"
            class="tab-button {{ $tab === 'approved' ? 'active' : '' }}">
            承認済み
            </a>
        </li>
    </ul>

    <div class="table-wrapper">
        <table class="application-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $attendanceRequests = $tab === 'pending' ? $pendingRequests : $approvedRequests;
                @endphp

                @forelse($attendanceRequests as $request)
                <tr>
                    <td>{{ $request->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ $request->attendance->work_date->format('Y/m/d') }}</td>
                    <td>{{ $request->after_remarks }}</td>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td><a href="{{ route('user.show', $request->attendance_id) }}" class="detail-link">詳細</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center">申請はありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection