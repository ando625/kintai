
@extends('layouts.default')

@section('title', 'メール認証 - COACHTECH'')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/mail.css') }}">
@endsection


@section('content')

        <div class="verification-container">
            <p class="verification-message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <a href="http://localhost:8025" class="verification-button" target="_blank">
                認証はこちらから
            </a>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="resend-link">
                    認証メールを再送する
                </button>
            </form>
        </div>
@endsection