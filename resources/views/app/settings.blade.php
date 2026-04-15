@extends('layouts.app')

@section('title', 'VideoChat | Settings')
@section('page', 'app-settings')
@section('body_class', 'site-body app-body')

@section('content')
<div
    id="app-shell"
    class="app-shell"
    data-session-url="{{ route('api.session') }}"
    data-send-url="{{ route('chat.send') }}"
    data-logout-url="{{ route('logout') }}"
    data-call-state-url="{{ route('api.call.state') }}"
    data-call-offer-url="{{ route('api.call.offer') }}"
    data-call-answer-url="{{ route('api.call.answer') }}"
    data-call-candidate-url="{{ route('api.call.candidate') }}"
    data-call-end-url="{{ route('api.call.end') }}"
    data-page-kind="settings"
>
    @include('app.partials.sidebar')

    <main class="app-main settings-main">
        <header class="settings-header">
            <h1>Settings</h1>
            <p>Manage your workspace preferences and device connectivity.</p>
        </header>

        <section class="settings-grid">
            <article class="settings-card settings-card--profile">
                <div class="settings-profile-head">
                    <div class="app-avatar"><span id="settings-avatar-initial">V</span></div>
                    <div>
                        <h2>Account Profile</h2>
                        <p>Update your public information</p>
                    </div>
                </div>
                <div class="settings-form-grid">
                    <label>
                        <span>DISPLAY NAME</span>
                        <input id="settings-name" type="text" value="Alexander Wright">
                    </label>
                    <label>
                        <span>EMAIL ADDRESS</span>
                        <input id="settings-email" type="email" value="alex.wright@business.lan">
                    </label>
                    <label class="settings-form-grid__wide">
                        <span>BIO</span>
                        <textarea id="settings-bio" rows="2">Senior Project Manager at Visionary Labs. Focused on high-velocity team communications.</textarea>
                    </label>
                </div>
            </article>

            <article class="settings-card settings-card--theme">
                <h2>Visual Theme</h2>
                <p>Personalize your view</p>
                <div class="theme-toggle-grid">
                    <button id="theme-light" class="theme-toggle is-active" type="button"><span class="material-symbols-outlined">light_mode</span><span>Light</span></button>
                    <button id="theme-dark" class="theme-toggle" type="button"><span class="material-symbols-outlined">dark_mode</span><span>Dark</span></button>
                </div>
            </article>

            <article class="settings-card settings-card--hardware">
                <h2><span class="material-symbols-outlined">settings_input_component</span>Hardware Configuration</h2>
                <div class="hardware-block">
                    <label>
                        <span>CAMERA DEVICE</span>
                        <select id="camera-device">
                            <option>Detecting cameras...</option>
                        </select>
                    </label>
                    <div class="hardware-preview">PREVIEW</div>
                </div>
                <label>
                    <span>MICROPHONE INPUT</span>
                    <select id="microphone-device">
                        <option>Detecting microphones...</option>
                    </select>
                </label>
                <div class="audio-meter"><i></i><i></i><i></i><b></b></div>
            </article>

            <article class="settings-card settings-card--notifications">
                <h2>Notifications</h2>
                <div class="settings-toggle-list">
                    <div class="settings-toggle-row">
                        <div><strong>Chat Notifications</strong><p>Real-time alerts for new messages</p></div>
                        <button class="settings-switch is-on" type="button"><i></i></button>
                    </div>
                    <div class="settings-toggle-row">
                        <div><strong>Call Invitations</strong><p>Play sound for incoming calls</p></div>
                        <button class="settings-switch is-on" type="button"><i></i></button>
                    </div>
                    <div class="settings-toggle-row">
                        <div><strong>Email Digests</strong><p>Weekly activity summaries</p></div>
                        <button class="settings-switch" type="button"><i></i></button>
                    </div>
                </div>

                <h3>SOUND SETTINGS</h3>
                <div class="settings-toggle-list">
                    <div class="settings-toggle-row">
                        <div><strong>Chat Sounds</strong><p>Alert tones for messaging</p></div>
                        <button class="settings-switch is-on" type="button"><i></i></button>
                    </div>
                    <div class="settings-toggle-row">
                        <div><strong>Call Sounds</strong><p>Ringtones for incoming calls</p></div>
                        <button class="settings-switch is-on" type="button"><i></i></button>
                    </div>
                </div>
                <a class="settings-history-link" href="{{ route('app.chat') }}"><span class="material-symbols-outlined">history</span><span>Review notification history</span></a>
            </article>
        </section>
    </main>
</div>
@endsection
