@extends('layouts.app')

@section('title', 'VideoChat | Profile')
@section('page', 'app-profile')
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
    data-page-kind="profile"
>
    @include('app.partials.sidebar')

    <main class="app-main profile-main">
        <section class="profile-layout">
            <div class="profile-hero">
                <div class="profile-portrait">
                    <div class="profile-portrait__art"></div>
                    <button class="profile-camera-button" type="button"><span class="material-symbols-outlined">photo_camera</span></button>
                </div>
                <h1 id="profile-name">Alex Sterling</h1>
                <p id="profile-role">Design Director</p>
                <button class="profile-edit-button" type="button"><span class="material-symbols-outlined">edit</span><span>Edit Profile</span></button>
            </div>

            <div class="profile-content">
                <div class="profile-status-block">
                    <span>STATUS MESSAGE</span>
                    <input id="profile-status" type="text" value="Synthesizing clarity from complexity. Open for design syncs.">
                </div>
                <article class="profile-contact-card">
                    <div class="profile-contact-card__icon"><span class="material-symbols-outlined">alternate_email</span></div>
                    <div>
                        <span>CONTACT</span>
                        <strong id="profile-email">alex.sterling@velocity.io</strong>
                        <p id="profile-phone">LAN Workspace</p>
                    </div>
                </article>
            </div>
        </section>
    </main>
</div>
@endsection
