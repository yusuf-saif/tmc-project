@extends('layouts.auth', ['title' => 'Verify Email'])

@section('content')
    <h1 class="tmc-auth-heading">Check your inbox</h1>
    <p class="tmc-auth-copy">Verify your email to unlock onboarding and enter the club.</p>

    @if (session('status') === 'verification-link-sent')
        <p class="mt-6 rounded-sm bg-gold-pale px-4 py-3 text-sm text-teal-dk">A fresh verification link has been sent to your email address.</p>
    @endif

    <div class="mt-8 space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="tmc-button-gold">Resend Verification Link</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="tmc-button-outline">Sign Out</button>
        </form>
    </div>
@endsection
