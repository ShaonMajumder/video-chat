@extends('layouts.app')

@section('title', 'LanCast | LAN Video and Chat')
@section('page', 'landing')
@section('body_class', 'shell-body landing-body')

@section('content')
<div class="landing-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('landing') }}">
            <span class="brand-mark">LC</span>
            <span>LanCast</span>
        </a>
        <nav class="topbar-actions">
            <a class="ghost-link" href="#capabilities">Capabilities</a>
            <a class="ghost-link" href="#workflow">Workflow</a>
            <a class="primary-link" href="{{ route('login') }}">Open Workspace</a>
        </nav>
    </header>

    <main class="landing-main">
        <section class="hero-card">
            <div class="hero-copy">
                <p class="eyebrow">Laravel 12 · PHP 8.3 · LAN-first collaboration</p>
                <h1>Professional local video rooms with a chat workflow that feels intentional.</h1>
                <p class="hero-text">
                    LanCast is rebuilt as a lightweight internal communication app for office or lab networks.
                    Open the workspace, see who is available, start a peer session, and keep text chat and call controls in one focused surface.
                </p>
                <div class="hero-actions">
                    <a class="button-primary" href="{{ route('login') }}">Launch workspace</a>
                    <a class="button-secondary" href="#workflow">See flow</a>
                </div>
                <ul class="hero-metrics">
                    <li><strong>1:1</strong><span>direct call mode</span></li>
                    <li><strong>LAN</strong><span>optimized signaling path</span></li>
                    <li><strong>Lite UI</strong><span>clear, fast, production-minded</span></li>
                </ul>
            </div>
            <div class="hero-preview" aria-hidden="true">
                <div class="preview-window">
                    <div class="preview-chip">Roster synced</div>
                    <div class="preview-grid">
                        <article class="preview-panel preview-video">
                            <span class="panel-kicker">Call stage</span>
                            <h2>Meet face to face without leaving the network.</h2>
                            <div class="mock-video-frame">
                                <div class="mock-video-pill">HD local preview</div>
                                <div class="mock-video-badge">Peer stream ready</div>
                            </div>
                        </article>
                        <article class="preview-panel preview-chat">
                            <span class="panel-kicker">Message lane</span>
                            <ul class="mock-message-list">
                                <li><span>Rafi</span>Switching to camera two.</li>
                                <li><span>You</span>Connection stable. Starting review.</li>
                                <li><span>Ops</span>Latency under 40ms on the LAN.</li>
                            </ul>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section id="capabilities" class="info-grid">
            <article class="info-card">
                <p class="eyebrow">Presence</p>
                <h3>See available peers instantly</h3>
                <p>Users are surfaced in a clean roster with live status cues and fast switching between conversations.</p>
            </article>
            <article class="info-card">
                <p class="eyebrow">Call controls</p>
                <h3>Video-first workspace</h3>
                <p>Camera, mic, and session controls stay close to the stage so starting or ending a call is obvious.</p>
            </article>
            <article class="info-card">
                <p class="eyebrow">Messaging</p>
                <h3>Real-time chat beside the call</h3>
                <p>Text messages travel through Laravel broadcasting while the video lane keeps the meeting anchored.</p>
            </article>
        </section>

        <section id="workflow" class="workflow-card">
            <div>
                <p class="eyebrow">Workflow</p>
                <h2>Three steps. No clutter.</h2>
            </div>
            <ol class="workflow-list">
                <li><strong>Sign in.</strong> Use a seeded internal account or your local team credentials.</li>
                <li><strong>Pick a peer.</strong> The roster becomes your control center for chat and call focus.</li>
                <li><strong>Start the session.</strong> Turn on camera and mic, then launch the call and message in parallel.</li>
            </ol>
        </section>
    </main>
</div>
@endsection
