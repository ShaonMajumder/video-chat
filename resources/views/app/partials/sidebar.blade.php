<aside class="app-sidebar">
    <div class="app-sidebar__brand">
        <div class="app-sidebar__brand-mark">
            <span class="material-symbols-outlined">videocam</span>
        </div>
        <div>
            <a class="app-sidebar__brand-text" href="{{ route('app.dashboard') }}">VideoChat</a>
            <p>Business LAN</p>
        </div>
    </div>

    <a class="app-sidebar__cta" href="{{ route('app.chat') }}">
        <span class="material-symbols-outlined">add</span>
        <span>New Meeting</span>
    </a>

    <nav class="app-sidebar__nav">
        <a class="app-sidebar__link {{ request()->routeIs('app.chat', 'app.chat.show', 'app.dashboard') ? 'is-active' : '' }}" href="{{ route('app.chat') }}">
            <span class="material-symbols-outlined">chat</span>
            <span>Chats</span>
        </a>
        <a class="app-sidebar__link {{ request()->routeIs('app.profile') ? 'is-active' : '' }}" href="{{ route('app.profile') }}">
            <span class="material-symbols-outlined">person</span>
            <span>Profile</span>
        </a>
        <a class="app-sidebar__link {{ request()->routeIs('app.settings') ? 'is-active' : '' }}" href="{{ route('app.settings') }}">
            <span class="material-symbols-outlined">settings</span>
            <span>Settings</span>
        </a>
    </nav>

    <div class="app-sidebar__footer">
        <a class="app-sidebar__meta-link" href="{{ route('app.settings') }}">
            <span class="material-symbols-outlined">help</span>
            <span>Support</span>
        </a>
        <button id="logout-button" class="app-sidebar__meta-link" type="button">
            <span class="material-symbols-outlined">logout</span>
            <span>Sign Out</span>
        </button>
    </div>
</aside>
