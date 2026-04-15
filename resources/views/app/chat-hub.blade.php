@extends('layouts.app')

@section('title', 'VideoChat | Chat Hub')
@section('page', 'app-chat-hub')
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
    data-page-kind="chat-hub"
>
    @include('app.partials.sidebar')

    <main class="app-main app-main--wide">
        <header class="chat-hub-topbar">
            <h1>Chat Hub</h1>
            <div class="chat-hub-topbar__right">
                <div class="app-search app-search--compact">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" placeholder="Search conversations..." disabled>
                </div>
                <button class="app-icon-button" type="button"><span class="material-symbols-outlined">notifications</span></button>
            </div>
        </header>

        <section class="chat-hub-layout">
            <div class="chat-hub-main">
                <div class="chat-hub-header">
                    <h2>Recent Chats</h2>
                    <div class="chat-hub-header__actions">
                        <button class="chat-filter-button" type="button"><span class="material-symbols-outlined">filter_list</span></button>
                        <a class="chat-new-button" href="{{ route('app.chat') }}"><span class="material-symbols-outlined">add</span><span>New Message</span></a>
                    </div>
                </div>
                <div id="chat-hub-list" class="chat-card-list"></div>
            </div>

            <aside class="chat-hub-sidebar">
                <div class="chat-hub-sidebar__header">
                    <h2>Online Now</h2>
                    <a href="{{ route('app.chat') }}">View All</a>
                </div>
                <div id="chat-hub-online-list" class="chat-online-panel"></div>
            </aside>
        </section>
    </main>
</div>
@endsection
