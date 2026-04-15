@extends('layouts.app')

@section('title', 'VideoChat | Communication')
@section('page', 'app-chat-call')
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
    data-page-kind="chat-call"
    data-selected-peer-id="{{ $selectedPeerId }}"
>
    @include('app.partials.sidebar')

    <main class="app-main app-main--wide">
        <header class="conversation-topbar">
            <div class="conversation-topbar__user">
                <div class="app-avatar app-avatar--small"><span id="current-user-avatar">V</span></div>
                <div>
                    <strong id="current-user-name">Loading...</strong>
                    <p><span class="status-live-dot"></span> <span id="current-user-email">Preparing session</span></p>
                </div>
            </div>
            <div class="conversation-topbar__actions">
                <button class="app-icon-button" type="button"><span class="material-symbols-outlined">notifications</span></button>
                <button class="app-icon-button" type="button"><span class="material-symbols-outlined">settings</span></button>
            </div>
        </header>

        <section class="conversation-layout">
            <section class="conversation-panel">
                <header class="conversation-panel__header">
                    <div>
                        <h1 id="peer-name">Select a peer</h1>
                        <p id="peer-meta">Open a conversation from the chat hub to begin messaging and calls.</p>
                    </div>
                    <div id="incoming-call-banner" class="incoming-banner" hidden>
                        <div>
                            <span>INCOMING CALL</span>
                            <strong id="incoming-call-title">Someone is calling</strong>
                            <p id="incoming-call-text">Accept to join the live session.</p>
                        </div>
                        <div class="incoming-banner__actions">
                            <button id="decline-call" type="button">Decline</button>
                            <button id="accept-call" type="button">Accept</button>
                        </div>
                    </div>
                </header>

                <div id="message-list" class="conversation-thread"></div>

                <form id="message-form" class="conversation-input">
                    <button class="conversation-input__icon" type="button"><span class="material-symbols-outlined">add_circle</span></button>
                    <textarea id="message-input" rows="1" placeholder="Type a message..." disabled></textarea>
                    <button class="conversation-input__icon" type="button"><span class="material-symbols-outlined">sentiment_satisfied</span></button>
                    <button id="message-submit" class="conversation-send" type="submit" disabled><span class="material-symbols-outlined">send</span></button>
                </form>
            </section>

            <aside class="call-panel">
                <article class="video-stage">
                    <div class="video-stage__badge"><i></i><span id="call-status">Idle</span></div>
                    <video id="remote-video" class="video-stage__media" autoplay playsinline></video>
                    <div id="remote-video-empty" class="video-stage__placeholder">
                        <span class="material-symbols-outlined">play_arrow</span>
                    </div>
                    <div class="video-stage__footer">
                        <div class="video-stage__identity">
                            <strong id="video-peer-name">Peer Video</strong>
                            <span id="video-peer-role">Senior Strategist</span>
                        </div>
                        <div class="self-video">
                            <video id="local-video" autoplay playsinline muted></video>
                            <div id="local-video-empty" class="self-video__placeholder">
                                <span id="local-media-state">Camera off</span>
                                <small id="local-video-empty-copy">Preview unavailable</small>
                            </div>
                        </div>
                    </div>
                </article>

                <div class="call-controls">
                    <button id="mic-toggle" class="call-control-button" type="button"><span class="material-symbols-outlined">mic</span></button>
                    <button id="camera-toggle" class="call-control-button" type="button"><span class="material-symbols-outlined">videocam</span></button>
                    <button id="call-toggle" class="call-control-button call-control-button--danger" type="button" disabled><span class="material-symbols-outlined">call_end</span></button>
                    <button class="call-control-button" type="button"><span class="material-symbols-outlined">present_to_all</span></button>
                    <button class="call-control-button" type="button"><span class="material-symbols-outlined">more_vert</span></button>
                </div>
                <p id="media-notice" class="media-notice" hidden></p>
            </aside>
        </section>
    </main>
</div>
@endsection
