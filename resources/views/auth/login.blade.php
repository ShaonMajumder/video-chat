@extends('layouts.app')

@section('title', 'VideoChat | Login')
@section('page', 'login')
@section('body_class', 'site-body auth-body')

@section('content')
<div class="login-page">
    <div class="login-card">
        <div class="login-card__content">
            <a class="marketing-logo" href="{{ route('landing') }}">VideoChat</a>
            <div class="login-copy">
                <span class="hero-pill">SECURE ACCESS</span>
                <h1>Enter the LAN workspace.</h1>
                <p>Business communication with fast chat, direct calls, and an interface tuned for focus.</p>
            </div>

            <form id="login-form" class="login-form" action="/api/login" method="post" novalidate>
                @csrf
                <label>
                    <span>Email</span>
                    <input type="email" name="email" placeholder="admin@admin.com" autocomplete="email" required>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
                </label>
                <button class="marketing-button marketing-button--primary login-submit" type="submit">Enter Workspace</button>
            </form>

            <div id="login-error" class="login-error" hidden></div>

            <div class="login-hint">
                <span>Seed account</span>
                <strong>admin@admin.com / 123456</strong>
            </div>

            <p class="login-note">
                Chat works over LAN IP on HTTP. Camera and microphone are most reliable over `https://your-lan-ip` or `http://localhost`.
            </p>
        </div>
        <div class="login-card__visual" aria-hidden="true">
            <div class="login-visual__screen">
                <div class="login-visual__badge">LAN READY</div>
                <div class="login-visual__panel"></div>
                <div class="login-visual__row">
                    <div class="login-visual__tile"></div>
                    <div class="login-visual__tile"></div>
                    <div class="login-visual__tile"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
