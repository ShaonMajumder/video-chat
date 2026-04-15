@extends('layouts.app')

@section('title', 'VideoChat | Dashboard')
@section('page', 'app-dashboard')
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
    data-page-kind="dashboard"
>
    @include('app.partials.sidebar')

    <main class="app-main dashboard-main">
        <header class="app-topbar">
            <div class="app-search">
                <span class="material-symbols-outlined">search</span>
                <input type="text" placeholder="Search messages, people..." disabled>
            </div>
            <div class="app-topbar__actions">
                <button class="app-icon-button" type="button"><span class="material-symbols-outlined">notifications</span><span class="app-dot"></span></button>
                <button class="app-icon-button" type="button"><span class="material-symbols-outlined">videocam</span></button>
                <div class="app-avatar app-avatar--small"><span id="dashboard-avatar-initial">V</span></div>
            </div>
        </header>

        <section class="dashboard-hero">
            <div>
                <h1 id="dashboard-greeting">Welcome back, teammate!</h1>
                <p>You have live chat access and direct calling ready across your LAN workspace.</p>
            </div>
            <div class="network-card">
                <div class="network-card__icon"><span class="material-symbols-outlined">network_check</span></div>
                <div>
                    <span>NETWORK STATUS</span>
                    <strong>1Gbps • 0.02ms</strong>
                </div>
                <i></i>
            </div>
        </section>

        <section class="dashboard-grid">
            <div class="dashboard-column">
                <div class="dashboard-section-label">QUICK ACTIONS</div>
                <div class="dashboard-actions">
                    <a class="dashboard-action dashboard-action--primary" href="{{ route('app.chat') }}"><span class="material-symbols-outlined">add_comment</span><span>Start New Chat</span></a>
                    <a id="dashboard-quick-call" class="dashboard-action dashboard-action--cyan" href="{{ route('app.chat') }}"><span class="material-symbols-outlined">bolt</span><span>Quick Call</span></a>
                    <a class="dashboard-action dashboard-action--ghost" href="{{ route('app.settings') }}"><span class="material-symbols-outlined">calendar_today</span><span>Schedule Meeting</span></a>
                </div>

                <div class="dashboard-section-label dashboard-section-label--spaced">ONLINE NOW <span id="online-count-pill">0 Active</span></div>
                <div id="dashboard-online-list" class="dashboard-online-list"></div>
            </div>

            <div class="dashboard-activity">
                <div class="dashboard-panel">
                    <div class="dashboard-panel__header">
                        <h2>Recent Activity</h2>
                        <button type="button">Mark all read</button>
                    </div>
                    <div class="activity-list">
                        <article class="activity-item">
                            <div class="activity-item__icon activity-item__icon--rose"><span class="material-symbols-outlined">call_missed</span></div>
                            <div>
                                <h3>Missed Video Call</h3>
                                <p>From Project Sync team. Lasted about 4 minutes.</p>
                            </div>
                            <time>12:45 PM</time>
                        </article>
                        <article class="activity-item">
                            <div class="activity-item__icon activity-item__icon--cyan"><span class="material-symbols-outlined">description</span></div>
                            <div>
                                <h3>New Document Shared</h3>
                                <p>`Q3_Performance_Review.pdf` was shared in chat.</p>
                            </div>
                            <time>10:15 AM</time>
                        </article>
                        <article class="activity-item">
                            <div class="activity-item__icon activity-item__icon--lavender"><span class="material-symbols-outlined">chat</span></div>
                            <div>
                                <h3>Unread Message</h3>
                                <p id="dashboard-unread-copy">New messages will appear here in real time.</p>
                            </div>
                            <time>09:30 AM</time>
                        </article>
                    </div>
                </div>

                <div class="dashboard-bottom">
                    <article class="meeting-card">
                        <div class="meeting-card__overlay">
                            <span>UPCOMING MEETING</span>
                            <h3>Design System Sync</h3>
                            <p>Starts in 15m</p>
                        </div>
                    </article>
                    <article class="insight-card">
                        <span>VELOCITY INSIGHTS</span>
                        <h3>Performance High</h3>
                        <p>Your call quality has been exceptional this week with 99.8% uptime.</p>
                        <div class="insight-bars">
                            <i></i><i></i><i></i><i></i><i></i>
                        </div>
                        <a href="{{ route('app.settings') }}">View Report</a>
                    </article>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
