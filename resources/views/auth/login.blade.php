@extends('layouts.app')

@section('title', 'LanCast | Sign In')
@section('page', 'login')
@section('body_class', 'shell-body auth-body')

@section('content')
<div class="auth-shell">
    <section class="auth-promo">
        <a class="brand" href="{{ route('landing') }}">
            <span class="brand-mark">LC</span>
            <span>LanCast</span>
        </a>
        <p class="eyebrow">Secure access</p>
        <h1>Enter the LAN workspace.</h1>
        <p>
            Built for direct internal communication: lightweight, fast to scan, and tuned for real-time chat plus browser-based video.
        </p>
        <div class="auth-feature-list">
            <div>
                <strong>Focused workspace</strong>
                <span>Roster, call stage, and chat without dashboard noise.</span>
            </div>
            <div>
                <strong>Peer-to-peer call path</strong>
                <span>Local signaling for quick LAN setup and low-friction meetings.</span>
            </div>
            <div>
                <strong>Modern visual system</strong>
                <span>Soft contrast, measured motion, and production-oriented spacing.</span>
            </div>
        </div>
    </section>

    <section class="auth-card-wrap">
        <div class="auth-card">
            <div>
                <p class="eyebrow">Sign in</p>
                <h2>Use your internal account</h2>
            </div>

            <form id="login-form" class="auth-form" action="/api/login" method="post" novalidate>
                @csrf
                <label>
                    <span>Email</span>
                    <input type="email" name="email" placeholder="admin@admin.com" autocomplete="email" required>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
                </label>
                <button class="button-primary" type="submit">Enter workspace</button>
            </form>

            <div id="login-error" class="form-alert" hidden></div>

            <div class="auth-hint">
                <span>Local seed account</span>
                <strong>admin@admin.com / 123456</strong>
            </div>

            <p class="auth-note">
                Text chat works on LAN IP over HTTP. Browser camera and mic access usually require `https://your-lan-ip` or `http://localhost`.
            </p>
        </div>
    </section>
</div>
@endsection
