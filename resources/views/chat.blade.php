@extends('layouts.app')

@section('title', 'LanCast | Workspace')
@section('page', 'workspace')
@section('body_class', 'shell-body workspace-body')

@section('content')
<div
    id="workspace-app"
    class="workspace-shell"
    data-session-url="{{ route('api.session') }}"
    data-send-url="{{ route('chat.send') }}"
    data-logout-url="{{ route('logout') }}"
    data-call-state-url="{{ route('api.call.state') }}"
    data-call-offer-url="{{ route('api.call.offer') }}"
    data-call-answer-url="{{ route('api.call.answer') }}"
    data-call-candidate-url="{{ route('api.call.candidate') }}"
    data-call-end-url="{{ route('api.call.end') }}"
>
    <aside class="workspace-sidebar">
        <div class="sidebar-brand-row">
            <a class="brand" href="{{ route('landing') }}">
                <span class="brand-mark">LC</span>
                <span>LanCast</span>
            </a>
            <button id="logout-button" class="icon-button" type="button" aria-label="Log out">Exit</button>
        </div>

        <section class="sidebar-card profile-card">
            <p class="eyebrow">Active user</p>
            <h2 id="current-user-name">Loading...</h2>
            <p id="current-user-email" class="muted-text">Preparing workspace</p>
            <div class="network-badge">LAN mode active</div>
        </section>

        <section class="sidebar-card roster-card">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Peers</p>
                    <h3>Available roster</h3>
                </div>
                <span id="contact-count" class="count-pill">0</span>
            </div>
            <div id="contact-list" class="contact-list" role="list"></div>
        </section>
    </aside>

    <main class="workspace-main">
        <section class="stage-card">
            <div class="stage-head">
                <div>
                    <p class="eyebrow">Call stage</p>
                    <h1 id="peer-name">Select a peer</h1>
                    <p id="peer-meta" class="muted-text">Choose someone from the roster to begin chat or video.</p>
                    <p id="media-notice" class="media-notice" hidden></p>
                </div>
                <div class="stage-actions">
                    <button id="camera-toggle" class="button-secondary" type="button">Camera on</button>
                    <button id="mic-toggle" class="button-secondary" type="button">Mic on</button>
                    <button id="call-toggle" class="button-primary" type="button" disabled>Start call</button>
                </div>
            </div>

            <div class="video-grid">
                <article class="video-panel primary-video">
                    <div class="video-label-row">
                        <span class="video-label">Peer feed</span>
                        <span id="call-status" class="status-pill">Idle</span>
                    </div>
                    <video id="remote-video" class="video-frame" autoplay playsinline></video>
                    <div id="remote-video-empty" class="video-empty-state">
                        <strong>Remote video will appear here</strong>
                        <span>Start a session after selecting a user. The signaling path stays inside your LAN app.</span>
                    </div>
                </article>
                <article class="video-panel secondary-video">
                    <div class="video-label-row">
                        <span class="video-label">Your preview</span>
                        <span id="local-media-state" class="status-pill muted">Camera off</span>
                    </div>
                    <video id="local-video" class="video-frame" autoplay playsinline muted></video>
                    <div id="local-video-empty" class="video-empty-state compact">
                        <strong>Local preview disabled</strong>
                        <span id="local-video-empty-copy">Turn on camera when you are ready.</span>
                    </div>
                </article>
            </div>
        </section>

        <section class="messaging-card">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Conversation</p>
                    <h3>Real-time chat</h3>
                </div>
                <span id="message-state" class="muted-text">No recipient selected</span>
            </div>

            <div id="message-list" class="message-list" aria-live="polite"></div>

            <form id="message-form" class="message-form">
                <label class="sr-only" for="message-input">Message</label>
                <textarea id="message-input" rows="1" placeholder="Write a clear update, then send." disabled></textarea>
                <button id="message-submit" class="button-primary" type="submit" disabled>Send</button>
            </form>
        </section>
    </main>
</div>
@endsection
