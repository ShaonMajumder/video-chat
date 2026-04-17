import './bootstrap';
import './utils';

const page = document.body.dataset.page;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const sanitize = (value) => window.DOMPurify ? window.DOMPurify.sanitize(String(value ?? '')) : String(value ?? '');

function autoResize(textarea) {
    if (!textarea) {
        return;
    }

    textarea.style.height = 'auto';
    textarea.style.height = `${Math.min(textarea.scrollHeight, 170)}px`;
}

async function sendJson(url, method = 'GET', payload = null) {
    const options = {
        method,
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    };

    if (method !== 'GET') {
        options.headers['Content-Type'] = 'application/json';
        options.headers['X-CSRF-TOKEN'] = csrfToken;
    }

    if (payload) {
        options.body = JSON.stringify(payload);
    }

    const response = await fetch(url, options);
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || 'Request failed');
    }

    return data;
}

function buildAvatarMarkup(name = 'User') {
    const initial = sanitize(name.trim().charAt(0).toUpperCase() || 'U');
    return initial;
}

function appRouteForPeer(peerId) {
    return new URL(`/chat/${peerId}`, window.location.origin).toString();
}

function appCallRouteForPeer(peerId) {
    return new URL(`/chat/${peerId}?call=1`, window.location.origin).toString();
}

function appUrl(path = '/dashboard') {
    return new URL(path, window.location.origin).toString();
}

function localPath(path = '/dashboard') {
    return path.startsWith('/') ? path : `/${path}`;
}

function initLoginPage() {
    const form = document.getElementById('login-form');
    const errorBox = document.getElementById('login-error');

    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.hidden = true;
        errorBox.textContent = '';

        try {
            const formData = new FormData(form);
            const result = await sendJson(form.action, 'POST', {
                email: formData.get('email'),
                password: formData.get('password'),
            });

            window.location.href = localPath(result.redirect || '/dashboard');
        } catch (error) {
            errorBox.hidden = false;
            errorBox.textContent = error.message;
        }
    });
}

function initAppPages() {
    const root = document.getElementById('app-shell');

    if (!root) {
        return;
    }

    const urls = {
        session: root.dataset.sessionUrl,
        send: root.dataset.sendUrl,
        logout: root.dataset.logoutUrl,
        callState: root.dataset.callStateUrl,
        offer: root.dataset.callOfferUrl,
        answer: root.dataset.callAnswerUrl,
        candidate: root.dataset.callCandidateUrl,
        endCall: root.dataset.callEndUrl,
    };

    const pageKind = root.dataset.pageKind;
    const selectedPeerIdFromRoute = Number(root.dataset.selectedPeerId || 0) || null;
    const els = {
        logoutButton: document.getElementById('logout-button'),
        dashboardGreeting: document.getElementById('dashboard-greeting'),
        dashboardAvatarInitial: document.getElementById('dashboard-avatar-initial'),
        dashboardQuickCall: document.getElementById('dashboard-quick-call'),
        dashboardOnlineList: document.getElementById('dashboard-online-list'),
        onlineCountPill: document.getElementById('online-count-pill'),
        dashboardUnreadCopy: document.getElementById('dashboard-unread-copy'),
        chatHubList: document.getElementById('chat-hub-list'),
        chatHubOnlineList: document.getElementById('chat-hub-online-list'),
        currentUserName: document.getElementById('current-user-name'),
        currentUserEmail: document.getElementById('current-user-email'),
        currentUserAvatar: document.getElementById('current-user-avatar'),
        peerName: document.getElementById('peer-name'),
        peerMeta: document.getElementById('peer-meta'),
        videoPeerName: document.getElementById('video-peer-name'),
        incomingCallBanner: document.getElementById('incoming-call-banner'),
        incomingCallTitle: document.getElementById('incoming-call-title'),
        incomingCallText: document.getElementById('incoming-call-text'),
        acceptCall: document.getElementById('accept-call'),
        declineCall: document.getElementById('decline-call'),
        messageList: document.getElementById('message-list'),
        messageForm: document.getElementById('message-form'),
        messageInput: document.getElementById('message-input'),
        messageSubmit: document.getElementById('message-submit'),
        localMediaState: document.getElementById('local-media-state'),
        localVideo: document.getElementById('local-video'),
        remoteVideo: document.getElementById('remote-video'),
        localVideoEmpty: document.getElementById('local-video-empty'),
        localVideoEmptyCopy: document.getElementById('local-video-empty-copy'),
        remoteVideoEmpty: document.getElementById('remote-video-empty'),
        callStatus: document.getElementById('call-status'),
        callPanel: document.getElementById('call-panel'),
        callLaunch: document.getElementById('call-launch'),
        cameraToggle: document.getElementById('camera-toggle'),
        micToggle: document.getElementById('mic-toggle'),
        callToggle: document.getElementById('call-toggle'),
        mediaNotice: document.getElementById('media-notice'),
        profileName: document.getElementById('profile-name'),
        profileRole: document.getElementById('profile-role'),
        profileStatus: document.getElementById('profile-status'),
        profileEmail: document.getElementById('profile-email'),
        profilePhone: document.getElementById('profile-phone'),
        settingsAvatarInitial: document.getElementById('settings-avatar-initial'),
        settingsName: document.getElementById('settings-name'),
        settingsEmail: document.getElementById('settings-email'),
        settingsBio: document.getElementById('settings-bio'),
        themeLight: document.getElementById('theme-light'),
        themeDark: document.getElementById('theme-dark'),
        cameraDevice: document.getElementById('camera-device'),
        microphoneDevice: document.getElementById('microphone-device'),
    };

    const state = {
        currentUser: null,
        contacts: [],
        selectedPeerId: selectedPeerIdFromRoute,
        conversations: {},
        unread: {},
        onlineIds: new Set(),
        localStream: null,
        remoteStream: null,
        peerConnection: null,
        pollingTimer: null,
        ringtoneTimer: null,
        audioContext: null,
        incomingCall: null,
        incomingCallMisses: 0,
        signaling: {
            offerAt: null,
            answerAt: null,
            candidateCounts: {},
            endedAt: null,
            localOfferSentAt: null,
        },
        media: {
            videoEnabled: false,
            audioEnabled: false,
        },
        callUiVisible: false,
        outgoingCallPending: false,
        secureContext: window.isSecureContext || ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname),
        realtime: {
            connected: false,
            signalSubscribed: false,
        },
    };

    const query = new URLSearchParams(window.location.search);
    const shouldAutostartCall = query.get('call') === '1';
    const insecureContextMessage = `Camera and mic are blocked on ${window.location.origin}. Open this app through HTTPS on your LAN IP or use localhost on this device.`;
    const incomingOfferMaxAgeMs = 8_000;
    const outgoingOfferRefreshMs = 3_000;

    function selectedPeer() {
        return state.contacts.find((contact) => contact.id === state.selectedPeerId) || null;
    }

    function peerById(peerId) {
        return state.contacts.find((contact) => contact.id === peerId) || null;
    }

    function ensureConversation(peerId) {
        if (!state.conversations[peerId]) {
            state.conversations[peerId] = [];
        }
    }

    function pushMessage(peerId, entry) {
        ensureConversation(peerId);
        state.conversations[peerId].push({
            ...entry,
            id: `${peerId}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        });
    }

    function formatTime() {
        return new Intl.DateTimeFormat([], {
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date());
    }

    function canUseMedia() {
        return state.secureContext && !!navigator.mediaDevices?.getUserMedia;
    }

    function canCaptureLocalMedia() {
        return canUseMedia();
    }

    function updateMediaNotice(message = '') {
        if (!els.mediaNotice) {
            return;
        }

        els.mediaNotice.hidden = message === '';
        els.mediaNotice.textContent = message;
    }

    function updateCallStatus(label) {
        if (els.callStatus) {
            els.callStatus.textContent = label;
        }
    }

    function isFreshOffer(offer, peerId = null) {
        if (!offer || !offer.updated_at) {
            return false;
        }

        if (peerId !== null && offer.from !== peerId) {
            return false;
        }

        const updatedAt = Date.parse(offer.updated_at);

        if (Number.isNaN(updatedAt)) {
            return false;
        }

        return (Date.now() - updatedAt) <= incomingOfferMaxAgeMs;
    }

    async function refreshOutgoingOfferIfNeeded() {
        const peer = selectedPeer();

        if (!state.outgoingCallPending || !state.peerConnection || !peer) {
            return;
        }

        const localDescription = state.peerConnection.localDescription;

        if (!localDescription) {
            return;
        }

        const lastSentAt = state.signaling.localOfferSentAt ? Date.parse(state.signaling.localOfferSentAt) : 0;

        if (!lastSentAt || Number.isNaN(lastSentAt) || (Date.now() - lastSentAt) < outgoingOfferRefreshMs) {
            return;
        }

        try {
            const result = await sendJson(urls.offer, 'POST', {
                peer_id: peer.id,
                sdp: sanitizeSessionDescription(localDescription),
            });

            state.signaling.offerAt = result.call?.offer?.updated_at || new Date().toISOString();
            state.signaling.localOfferSentAt = state.signaling.offerAt;
        } catch (error) {
            window.log('offer refresh error', error.message);
        }
    }

    function setCallUiVisible(visible) {
        state.callUiVisible = visible;

        if (els.callPanel) {
            els.callPanel.classList.toggle('is-hidden', !visible);
            els.callPanel.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }

        if (els.callLaunch) {
            els.callLaunch.hidden = visible;
        }
    }

    function syncCallLaunchButton() {
        if (!els.callLaunch) {
            return;
        }

        const peer = selectedPeer();
        els.callLaunch.disabled = !peer || !!state.peerConnection || !!state.incomingCall;
    }

    function updatePeerText() {
        const peer = selectedPeer();

        if (els.peerName) {
            els.peerName.textContent = peer?.name || 'Select a peer';
        }

        if (els.videoPeerName) {
            els.videoPeerName.textContent = peer?.name || 'Peer Video';
        }

        if (els.peerMeta) {
            els.peerMeta.textContent = peer
                ? `${peer.email} · ${state.onlineIds.has(peer.id) ? 'Active now' : 'Offline'}`
                : 'Open a conversation from the chat hub to begin messaging and calls.';
        }
    }

    function renderDashboard() {
        if (!els.dashboardGreeting || !state.currentUser) {
            return;
        }

        const firstName = state.currentUser.name.split(' ')[0];
        els.dashboardGreeting.textContent = `Welcome back, ${firstName}!`;

        if (els.dashboardAvatarInitial) {
            els.dashboardAvatarInitial.textContent = buildAvatarMarkup(state.currentUser.name);
        }

        if (els.dashboardQuickCall) {
            const target = state.contacts[0];
            els.dashboardQuickCall.href = target ? appCallRouteForPeer(target.id) : appUrl('/chat');
        }

        if (els.onlineCountPill) {
            els.onlineCountPill.textContent = `${state.onlineIds.size} Active`;
        }

        if (els.dashboardOnlineList) {
            els.dashboardOnlineList.innerHTML = '';
            const pool = state.contacts.slice(0, 4);

            pool.forEach((contact) => {
                const item = document.createElement('a');
                item.className = 'online-person';
                item.href = appRouteForPeer(contact.id);
                item.innerHTML = `
                    <div class="online-person__avatar">${buildAvatarMarkup(contact.name)}</div>
                    <div>
                        <strong>${sanitize(contact.name)}</strong>
                        <p>${state.onlineIds.has(contact.id) ? 'Available' : 'Offline'}</p>
                    </div>
                `;
                els.dashboardOnlineList.appendChild(item);
            });
        }
    }

    function renderChatHub() {
        if (els.chatHubList) {
            els.chatHubList.innerHTML = '';

            state.contacts.forEach((contact) => {
                const unread = state.unread[contact.id] || 0;
                const card = document.createElement('article');
                card.className = 'chat-card';
                card.innerHTML = `
                    <a class="chat-card__avatar" href="${appRouteForPeer(contact.id)}">${buildAvatarMarkup(contact.name)}</a>
                    <a class="chat-card__body" href="${appRouteForPeer(contact.id)}">
                        <strong>${sanitize(contact.name)}</strong>
                        <p>${unread ? `${unread} unread message${unread > 1 ? 's' : ''}` : sanitize(contact.email)}</p>
                    </a>
                    <div class="chat-card__meta">
                        <span class="chat-card__time">${unread ? 'NOW' : 'LIVE'}</span>
                        <div class="chat-card__actions">
                            <a class="chat-card__button" href="${appCallRouteForPeer(contact.id)}"><span class="material-symbols-outlined">call</span></a>
                            <a class="chat-card__button chat-card__button--video" href="${appCallRouteForPeer(contact.id)}"><span class="material-symbols-outlined">videocam</span></a>
                        </div>
                    </div>
                `;
                els.chatHubList.appendChild(card);
            });
        }

        if (els.chatHubOnlineList) {
            els.chatHubOnlineList.innerHTML = '';

            state.contacts.filter((contact) => state.onlineIds.has(contact.id) || state.contacts.length <= 5).slice(0, 5).forEach((contact) => {
                const item = document.createElement('a');
                item.className = 'chat-online-person';
                item.href = appRouteForPeer(contact.id);
                item.innerHTML = `
                    <div class="chat-online-person__avatar">${buildAvatarMarkup(contact.name)}</div>
                    <div class="chat-online-person__body">
                        <strong>${sanitize(contact.name)}</strong>
                        <small>${state.onlineIds.has(contact.id) ? 'Active' : 'Offline'}</small>
                    </div>
                    <span class="material-symbols-outlined">chat_bubble</span>
                `;
                els.chatHubOnlineList.appendChild(item);
            });
        }
    }

    function renderConversation() {
        if (!els.messageList) {
            return;
        }

        const peer = selectedPeer();
        els.messageList.innerHTML = '';

        if (!peer) {
            els.messageList.innerHTML = '<div class="empty-conversation"><strong>Select a peer</strong><span>Messages and call controls activate when a contact is selected.</span></div>';
            if (els.messageInput) {
                els.messageInput.disabled = true;
            }
            if (els.messageSubmit) {
                els.messageSubmit.disabled = true;
            }
            if (els.callToggle) {
                els.callToggle.disabled = true;
            }
            syncCallLaunchButton();
            setCallUiVisible(false);
            updatePeerText();
            return;
        }

        updatePeerText();
        if (els.messageInput) {
            els.messageInput.disabled = false;
        }
        if (els.messageSubmit) {
            els.messageSubmit.disabled = false;
        }
        if (els.callToggle) {
            els.callToggle.disabled = false;
        }
        syncCallLaunchButton();

        const date = document.createElement('div');
        date.className = 'conversation-date-separator';
        date.textContent = 'MONDAY, OCT 23';
        els.messageList.appendChild(date);

        const items = state.conversations[peer.id] || [];

        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'empty-conversation';
            empty.innerHTML = `<strong>No messages yet</strong><span>Start the conversation with ${sanitize(peer.name)}.</span>`;
            els.messageList.appendChild(empty);
            return;
        }

        items.forEach((entry) => {
            const row = document.createElement('div');
            row.className = `message-row${entry.self ? ' message-row--self' : ''}`;
            row.innerHTML = `
                ${entry.self ? '' : `<div class="message-avatar">${buildAvatarMarkup(entry.sender)}</div>`}
                <div class="message-stack">
                    <div class="message-bubble">${sanitize(entry.body)}</div>
                    <span class="message-meta">${sanitize(entry.timeLabel)}</span>
                </div>
            `;
            els.messageList.appendChild(row);
        });

        els.messageList.scrollTop = els.messageList.scrollHeight;
    }

    function renderProfile() {
        if (!state.currentUser) {
            return;
        }

        if (els.profileName) {
            els.profileName.textContent = state.currentUser.name;
        }
        if (els.profileRole) {
            els.profileRole.textContent = 'Business LAN User';
        }
        if (els.profileEmail) {
            els.profileEmail.textContent = state.currentUser.email;
        }
        if (els.profilePhone) {
            els.profilePhone.textContent = 'Internal workspace account';
        }
    }

    function renderSettings() {
        if (!state.currentUser) {
            return;
        }

        if (els.settingsAvatarInitial) {
            els.settingsAvatarInitial.textContent = buildAvatarMarkup(state.currentUser.name);
        }
        if (els.settingsName) {
            els.settingsName.value = state.currentUser.name;
        }
        if (els.settingsEmail) {
            els.settingsEmail.value = state.currentUser.email;
        }
        if (els.settingsBio) {
            els.settingsBio.value = `${state.currentUser.name} uses VideoChat for fast internal communication.`;
        }
    }

    function renderAll() {
        renderDashboard();
        renderChatHub();
        renderConversation();
        renderProfile();
        renderSettings();
    }

    function updateVideoState() {
        if (els.localVideoEmpty) {
            els.localVideoEmpty.hidden = !!state.localStream;
        }
        if (els.remoteVideoEmpty) {
            els.remoteVideoEmpty.hidden = !!state.remoteStream;
        }
        if (els.localMediaState) {
            els.localMediaState.textContent = state.media.videoEnabled ? 'Camera live' : 'Camera off';
        }

        if (els.cameraToggle) {
            els.cameraToggle.dataset.active = String(state.media.videoEnabled);
        }
        if (els.micToggle) {
            els.micToggle.dataset.active = String(state.media.audioEnabled);
        }

        if (!state.localStream && els.localVideoEmptyCopy) {
            els.localVideoEmptyCopy.textContent = canCaptureLocalMedia()
                ? 'Preview unavailable'
                : 'Receive-only until HTTPS or localhost is available.';
        }

        if (els.callToggle) {
            const callIcon = els.callToggle.querySelector('.material-symbols-outlined');
            if (callIcon) {
                callIcon.textContent = state.peerConnection ? 'call_end' : 'videocam';
            }
        }
    }

    function playRingBurst() {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            return;
        }

        if (!state.audioContext) {
            state.audioContext = new AudioContextClass();
        }

        const context = state.audioContext;
        if (context.state === 'suspended') {
            context.resume().catch(() => {});
        }

        const now = context.currentTime;
        const gain = context.createGain();
        gain.connect(context.destination);
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.06, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.28);

        [0, 0.34].forEach((offset) => {
            const oscillator = context.createOscillator();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, now + offset);
            oscillator.connect(gain);
            oscillator.start(now + offset);
            oscillator.stop(now + offset + 0.24);
        });
    }

    function stopRingtone() {
        if (state.ringtoneTimer) {
            clearInterval(state.ringtoneTimer);
            state.ringtoneTimer = null;
        }
    }

    function startRingtone() {
        if (state.ringtoneTimer) {
            return;
        }

        playRingBurst();
        state.ringtoneTimer = window.setInterval(playRingBurst, 1800);
    }

    function clearIncomingCall() {
        state.incomingCall = null;
        state.incomingCallMisses = 0;
        if (els.incomingCallBanner) {
            els.incomingCallBanner.hidden = true;
        }
        stopRingtone();
        if (!state.peerConnection && !state.outgoingCallPending) {
            setCallUiVisible(false);
        }
        syncCallLaunchButton();
    }

    function setIncomingCall(peer, offer) {
        state.incomingCall = {
            peerId: peer.id,
            name: peer.name,
            email: peer.email,
            offer,
            canReceiveOnly: !canCaptureLocalMedia(),
        };
        state.incomingCallMisses = 0;

        if (els.incomingCallBanner) {
            els.incomingCallBanner.hidden = false;
        }
        if (els.incomingCallTitle) {
            els.incomingCallTitle.textContent = `${peer.name} is calling`;
        }
        if (els.incomingCallText) {
            els.incomingCallText.textContent = state.incomingCall.canReceiveOnly
                ? 'This device can join in receive-only mode.'
                : 'Accept to join the live audio and video session.';
        }
        updateCallStatus('REC • 00:00');
        setCallUiVisible(true);
        syncCallLaunchButton();
        startRingtone();
    }

    function sanitizeSessionDescription(description) {
        if (!description || typeof description.sdp !== 'string') {
            return description;
        }

        const normalizedSdp = description.sdp.replace(/\r?\n/g, '\r\n');
        const lines = normalizedSdp.split('\r\n');
        const blockedCodecNames = new Set(['rtx', 'red', 'ulpfec', 'flexfec']);
        const sanitizedLines = [];
        let currentSection = null;

        function flushSection() {
            if (!currentSection) {
                return;
            }

            const payloads = currentSection.mediaLineParts.slice(3);
            const codecByPayload = new Map();

            currentSection.lines.forEach((line) => {
                const match = line.match(/^a=rtpmap:(\d+)\s+([^/\s]+)/i);

                if (match) {
                    codecByPayload.set(match[1], match[2].toLowerCase());
                }
            });

            const blockedPayloads = new Set(
                payloads.filter((payloadType) => blockedCodecNames.has(codecByPayload.get(payloadType)))
            );

            const allowedPayloads = payloads.filter((payloadType) => !blockedPayloads.has(payloadType));

            sanitizedLines.push('m=' + [
                currentSection.mediaLineParts[0],
                currentSection.mediaLineParts[1],
                currentSection.mediaLineParts[2],
                ...allowedPayloads,
            ].join(' '));

            currentSection.lines.forEach((line) => {
                if (line.startsWith('a=ssrc:') || line.startsWith('a=ssrc-group:')) {
                    return;
                }

                const payloadMatch = line.match(/^a=(?:fmtp|rtpmap|rtcp-fb):(\d+)\b/i);

                if (payloadMatch) {
                    const payloadType = payloadMatch[1];

                    if (!allowedPayloads.includes(payloadType)) {
                        return;
                    }

                    if (/^a=fmtp:\d+\s+repair-window=\d+$/i.test(line)) {
                        return;
                    }
                }

                sanitizedLines.push(line);
            });

            currentSection = null;
        }

        for (const rawLine of lines) {
            const line = rawLine.trimEnd();

            if (line.startsWith('m=')) {
                flushSection();
                currentSection = {
                    mediaLineParts: line.slice(2).trim().split(/\s+/),
                    lines: [],
                };
                continue;
            }

            if (currentSection) {
                currentSection.lines.push(line);
            } else {
                sanitizedLines.push(line);
            }
        }

        flushSection();

        return {
            type: description.type,
            sdp: `${sanitizedLines.join('\r\n')}\r\n`,
        };
    }

    async function ensureLocalStream() {
        if (!canCaptureLocalMedia()) {
            throw new Error(insecureContextMessage);
        }

        if (state.localStream) {
            return state.localStream;
        }

        const stream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true,
        });

        state.localStream = stream;
        state.media.videoEnabled = true;
        state.media.audioEnabled = true;

        if (els.localVideo) {
            els.localVideo.srcObject = stream;
        }

        updateVideoState();
        return stream;
    }

    function stopLocalStream() {
        if (!state.localStream) {
            state.media.videoEnabled = false;
            state.media.audioEnabled = false;
            return;
        }

        state.localStream.getTracks().forEach((track) => track.stop());
        state.localStream = null;
        state.media.videoEnabled = false;
        state.media.audioEnabled = false;

        if (els.localVideo) {
            els.localVideo.srcObject = null;
        }
    }

    function closePeerConnection(resetRemote = true) {
        if (state.peerConnection) {
            state.peerConnection.onicecandidate = null;
            state.peerConnection.ontrack = null;
            state.peerConnection.onconnectionstatechange = null;
            state.peerConnection.close();
            state.peerConnection = null;
        }

        stopLocalStream();

        if (resetRemote) {
            state.remoteStream = null;
            if (els.remoteVideo) {
                els.remoteVideo.srcObject = null;
            }
        }

        state.signaling = {
            offerAt: null,
            answerAt: null,
            candidateCounts: {},
            endedAt: null,
            localOfferSentAt: null,
        };
        state.outgoingCallPending = false;
        updateCallStatus('Idle');
        setCallUiVisible(false);
        syncCallLaunchButton();
        updateVideoState();
    }

    async function ensurePeerConnection() {
        if (state.peerConnection) {
            return state.peerConnection;
        }

        const connection = new RTCPeerConnection({ iceServers: [] });
        const stream = state.localStream;

        if (stream) {
            stream.getTracks().forEach((track) => connection.addTrack(track, stream));
        } else {
            connection.addTransceiver('audio', { direction: 'recvonly' });
            connection.addTransceiver('video', { direction: 'recvonly' });
        }

        connection.ontrack = (event) => {
            state.remoteStream = event.streams[0];
            if (els.remoteVideo) {
                els.remoteVideo.srcObject = state.remoteStream;
            }
            state.outgoingCallPending = false;
            updateCallStatus('REC • LIVE');
            setCallUiVisible(true);
            syncCallLaunchButton();
            updateVideoState();
        };

        connection.onicecandidate = async (event) => {
            if (!event.candidate || !state.selectedPeerId) {
                return;
            }

            try {
                await sendJson(urls.candidate, 'POST', {
                    peer_id: state.selectedPeerId,
                    candidate: event.candidate.toJSON(),
                });
            } catch (error) {
                window.log('candidate error', error.message);
            }
        };

        connection.onconnectionstatechange = () => {
            const connectionState = connection.connectionState;

            if (connectionState === 'connected') {
                state.outgoingCallPending = false;
                updateCallStatus('REC • LIVE');
                setCallUiVisible(true);
            } else if (['connecting', 'new'].includes(connectionState)) {
                updateCallStatus('CONNECTING');
                setCallUiVisible(true);
            } else if (['disconnected', 'failed', 'closed'].includes(connectionState)) {
                closePeerConnection(connectionState !== 'closed');
            }
        };

        state.peerConnection = connection;
        updateVideoState();
        return connection;
    }

    async function syncLocalTracks() {
        if (!state.peerConnection || !state.localStream) {
            return;
        }

        const senders = state.peerConnection.getSenders();
        const tracks = state.localStream.getTracks();

        tracks.forEach((track) => {
            const sender = senders.find((item) => item.track && item.track.kind === track.kind);
            if (!sender) {
                state.peerConnection.addTrack(track, state.localStream);
            }
        });
    }

    async function toggleCamera() {
        if (!state.localStream) {
            await ensureLocalStream();
            await syncLocalTracks();
            return;
        }

        const [videoTrack] = state.localStream.getVideoTracks();
        if (!videoTrack) {
            return;
        }

        videoTrack.enabled = !videoTrack.enabled;
        state.media.videoEnabled = videoTrack.enabled;
        updateVideoState();
    }

    async function toggleMic() {
        if (!state.localStream) {
            await ensureLocalStream();
            await syncLocalTracks();
            return;
        }

        const [audioTrack] = state.localStream.getAudioTracks();
        if (!audioTrack) {
            return;
        }

        audioTrack.enabled = !audioTrack.enabled;
        state.media.audioEnabled = audioTrack.enabled;
        updateVideoState();
    }

    async function applyCandidates(call, peer = selectedPeer()) {
        if (!call.candidates || !state.peerConnection || !peer) {
            return;
        }

        const peerCandidates = call.candidates[peer.id] || [];
        const seen = state.signaling.candidateCounts[peer.id] || 0;
        const nextCandidates = peerCandidates.slice(seen);

        for (const candidate of nextCandidates) {
            await state.peerConnection.addIceCandidate(candidate);
        }

        state.signaling.candidateCounts[peer.id] = peerCandidates.length;
    }

    async function fetchCallState(peerId) {
        const url = `${urls.callState}?peer_id=${peerId}`;
        const result = await sendJson(url);
        return result.call || {};
    }

    async function handleCallState(call, peer = selectedPeer()) {
        if (!peer || !call) {
            return;
        }

        const activeIncomingOffer = isFreshOffer(call.offer, peer.id) && !call.answer && !call.ended_at;
        const isSelectedPeer = peer.id === state.selectedPeerId;

        if (activeIncomingOffer) {
            state.incomingCallMisses = 0;
        }

        if (!activeIncomingOffer && state.incomingCall?.peerId === peer.id && !state.peerConnection) {
            state.incomingCallMisses += 1;

            if (state.incomingCallMisses >= 2) {
                clearIncomingCall();
            }
        }

        if (call.ended_at && call.ended_at !== state.signaling.endedAt) {
            state.signaling.endedAt = call.ended_at;
            clearIncomingCall();

            if (isSelectedPeer || state.incomingCall?.peerId === peer.id) {
                closePeerConnection();
            }

            return;
        }

        if (activeIncomingOffer && call.offer.updated_at !== state.signaling.offerAt) {
            if (pageKind !== 'chat-call' && !state.peerConnection) {
                window.location.href = appRouteForPeer(peer.id);
                return;
            }

            if (!state.peerConnection && (!state.incomingCall || state.incomingCall.offer.updated_at !== call.offer.updated_at)) {
                setIncomingCall(peer, call.offer);
                return;
            }
        }

        if (isSelectedPeer && call.answer && call.answer.from === peer.id && call.answer.updated_at !== state.signaling.answerAt && state.peerConnection) {
            state.signaling.answerAt = call.answer.updated_at;
            clearIncomingCall();
            await state.peerConnection.setRemoteDescription(new RTCSessionDescription(sanitizeSessionDescription(call.answer.sdp)));
            updateCallStatus('REC • LIVE');
        }

        if (isSelectedPeer) {
            await applyCandidates(call, peer);
        }
    }

    async function syncPeerCallState(peerId) {
        const peer = peerById(peerId);

        if (!peer) {
            return;
        }

        const call = await fetchCallState(peerId);
        await handleCallState(call, peer);
    }

    async function handleCallSignal(event) {
        const participants = Array.isArray(event.participants) ? event.participants.map((id) => Number(id)) : [];
        const peerId = participants.find((id) => id !== Number(state.currentUser?.id));

        if (!peerId) {
            return;
        }

        const peer = peerById(peerId);

        if (!peer) {
            return;
        }

        await handleCallState(event.call || {}, peer);
    }

    async function detectIncomingCall() {
        let foundIncoming = false;

        for (const peer of state.contacts) {
            try {
                const call = await fetchCallState(peer.id);

                if (
                    isFreshOffer(call.offer, peer.id) &&
                    !call.answer &&
                    !call.ended_at &&
                    (!state.incomingCall || state.incomingCall.offer.updated_at !== call.offer.updated_at)
                ) {
                    foundIncoming = true;
                    setIncomingCall(peer, call.offer);
                    return;
                }
            } catch (error) {
                window.log('incoming call detection error', error.message);
            }
        }

        if (!foundIncoming && !state.peerConnection && state.incomingCall) {
            state.incomingCallMisses += 1;

            if (state.incomingCallMisses >= 2) {
                clearIncomingCall();
            }
        }
    }

    async function pollCallState() {
        const peer = selectedPeer();

        try {
            await refreshOutgoingOfferIfNeeded();

            if (peer) {
                await syncPeerCallState(peer.id);
            }

            if (!state.peerConnection) {
                await detectIncomingCall();
            }
        } catch (error) {
            window.log('poll error', error.message);
        }
    }

    function stopPolling() {
        if (state.pollingTimer) {
            clearInterval(state.pollingTimer);
            state.pollingTimer = null;
        }
    }

    function shouldUsePollingFallback() {
        return pageKind === 'chat-call' && (!state.realtime.connected || !state.realtime.signalSubscribed);
    }

    function syncPollingMode() {
        if (!shouldUsePollingFallback()) {
            stopPolling();
            return;
        }

        startPolling();
    }

    function startPolling() {
        if (state.pollingTimer) {
            return;
        }

        pollCallState();

        state.pollingTimer = window.setInterval(() => {
            pollCallState();
        }, 1800);
    }

    async function startCall() {
        const peer = selectedPeer();
        if (!peer) {
            return;
        }

        setCallUiVisible(true);
        state.outgoingCallPending = true;
        syncCallLaunchButton();

        if (canCaptureLocalMedia()) {
            try {
                await ensureLocalStream();
            } catch (error) {
                window.log('local media unavailable, starting receive-only call', error.message);
            }
        }

        const connection = await ensurePeerConnection();
        await syncLocalTracks();

        const offer = await connection.createOffer();
        await connection.setLocalDescription(offer);
        const result = await sendJson(urls.offer, 'POST', {
            peer_id: peer.id,
            sdp: sanitizeSessionDescription(connection.localDescription ?? offer),
        });

        state.signaling.offerAt = result.call?.offer?.updated_at || new Date().toISOString();
        state.signaling.localOfferSentAt = state.signaling.offerAt;

        updateCallStatus('RINGING');
    }

    async function endCall() {
        const peer = selectedPeer();
        if (peer) {
            try {
                await sendJson(urls.endCall, 'POST', { peer_id: peer.id });
            } catch (error) {
                window.log('end call error', error.message);
            }
        }

        closePeerConnection();
        clearIncomingCall();
    }

    async function acceptIncomingCall() {
        const incoming = state.incomingCall;
        if (!incoming) {
            return;
        }

        state.selectedPeerId = incoming.peerId;
        state.signaling.offerAt = incoming.offer.updated_at;
        state.outgoingCallPending = false;
        clearIncomingCall();
        renderAll();
        setCallUiVisible(true);

        if (canCaptureLocalMedia() && !state.localStream) {
            try {
                await ensureLocalStream();
            } catch (error) {
                updateMediaNotice('Camera and microphone access was not granted. Joining in receive-only mode.');
            }
        }

        const connection = await ensurePeerConnection();
        await syncLocalTracks();
        await connection.setRemoteDescription(new RTCSessionDescription(sanitizeSessionDescription(incoming.offer.sdp)));
        const answer = await connection.createAnswer();
        await connection.setLocalDescription(answer);
        await sendJson(urls.answer, 'POST', {
            peer_id: incoming.peerId,
            sdp: sanitizeSessionDescription(connection.localDescription ?? answer),
        });

        updateCallStatus('CONNECTING');
    }

    async function declineIncomingCall() {
        const incoming = state.incomingCall;
        if (!incoming) {
            return;
        }

        try {
            await sendJson(urls.endCall, 'POST', { peer_id: incoming.peerId });
        } catch (error) {
            window.log('decline call error', error.message);
        }

        clearIncomingCall();
    }

    async function toggleCall() {
        if (state.peerConnection) {
            await endCall();
            return;
        }

        await startCall();
    }

    async function handleMessageSubmit(event) {
        event.preventDefault();

        const peer = selectedPeer();
        const value = els.messageInput?.value.trim();

        if (!peer || !value) {
            return;
        }

        try {
            await sendJson(urls.send, 'POST', {
                receiver_id: peer.id,
                message: value,
            });

            pushMessage(peer.id, {
                self: true,
                sender: 'You',
                body: value,
                timeLabel: formatTime(),
            });

            els.messageInput.value = '';
            autoResize(els.messageInput);
            renderConversation();
        } catch (error) {
            updateMediaNotice(error.message);
        }
    }

    function selectPeer(peerId) {
        state.selectedPeerId = peerId;
        state.unread[peerId] = 0;
        state.outgoingCallPending = false;

        if (pageKind === 'chat-call') {
            updatePeerText();
            renderConversation();
            syncCallLaunchButton();
            updateMediaNotice(canCaptureLocalMedia() ? '' : 'This device can join calls in receive-only mode until camera/mic access is available over HTTPS or localhost.');

            if (state.realtime.connected) {
                syncPeerCallState(peerId).catch((error) => window.log('peer sync error', error.message));
            }
        }
    }

    function wireRealtime() {
        if (!state.currentUser) {
            return;
        }

        if (!window.Echo) {
            syncPollingMode();
            return;
        }

        const connection = window.Echo.connector?.pusher?.connection;

        if (connection) {
            state.realtime.connected = connection.state === 'connected';

            connection.bind('connected', () => {
                state.realtime.connected = true;
                syncPollingMode();

                if (pageKind === 'chat-call') {
                    pollCallState();
                }
            });

            connection.bind('disconnected', () => {
                state.realtime.connected = false;
                syncPollingMode();
            });

            connection.bind('unavailable', () => {
                state.realtime.connected = false;
                syncPollingMode();
            });

            connection.bind('failed', () => {
                state.realtime.connected = false;
                syncPollingMode();
            });
        }

        window.Echo.private(`message-box.${state.currentUser.id}`)
            .listen('NewMessage', (event) => {
                const sender = state.contacts.find((contact) => contact.id === event.sender_id);
                if (!sender) {
                    return;
                }

                pushMessage(sender.id, {
                    self: false,
                    sender: sender.name,
                    body: event.message,
                    timeLabel: formatTime(),
                });

                if (state.selectedPeerId !== sender.id) {
                    state.unread[sender.id] = (state.unread[sender.id] || 0) + 1;
                }

                if (els.dashboardUnreadCopy) {
                    els.dashboardUnreadCopy.textContent = `${sender.name}: "${event.message}"`;
                }

                renderAll();
            });

        window.Echo.join('online')
            .here((users) => {
                state.onlineIds = new Set(users.map((user) => user.id).filter((id) => id !== state.currentUser.id));
                renderAll();
                updatePeerText();
            })
            .joining((user) => {
                if (user.id !== state.currentUser.id) {
                    state.onlineIds.add(user.id);
                    renderAll();
                    updatePeerText();
                }
            })
            .leaving((user) => {
                state.onlineIds.delete(user.id);
                renderAll();
                updatePeerText();
            })
            .error((error) => {
                window.log('presence subscription error', error);
            });

        window.Echo.private(`call-signaling.${state.currentUser.id}`)
            .subscribed(() => {
                state.realtime.signalSubscribed = true;
                syncPollingMode();

                if (pageKind === 'chat-call') {
                    pollCallState();
                }
            })
            .listen('.call.signal.updated', (event) => {
                handleCallSignal(event).catch((error) => {
                    window.log('call signal error', error.message);
                });
            })
            .error((error) => {
                state.realtime.signalSubscribed = false;
                window.log('call signaling subscription error', error);
                syncPollingMode();
            });

        syncPollingMode();
    }

    async function populateDevices() {
        if (!navigator.mediaDevices?.enumerateDevices) {
            return;
        }

        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cameras = devices.filter((device) => device.kind === 'videoinput');
            const microphones = devices.filter((device) => device.kind === 'audioinput');

            if (els.cameraDevice) {
                els.cameraDevice.innerHTML = cameras.length
                    ? cameras.map((device, index) => `<option>${sanitize(device.label || `Camera ${index + 1}`)}</option>`).join('')
                    : '<option>No camera detected</option>';
            }

            if (els.microphoneDevice) {
                els.microphoneDevice.innerHTML = microphones.length
                    ? microphones.map((device, index) => `<option>${sanitize(device.label || `Microphone ${index + 1}`)}</option>`).join('')
                    : '<option>No microphone detected</option>';
            }
        } catch (error) {
            window.log('device enumeration error', error.message);
        }
    }

    function wireThemeToggles() {
        if (!els.themeLight || !els.themeDark) {
            return;
        }

        const applyThemeState = (theme) => {
            els.themeLight.classList.toggle('is-active', theme !== 'dark');
            els.themeDark.classList.toggle('is-active', theme === 'dark');
            document.documentElement.classList.toggle('dark', theme === 'dark');
        };

        const saved = localStorage.getItem('videochat-theme') || 'light';
        applyThemeState(saved);

        els.themeLight.addEventListener('click', () => {
            localStorage.setItem('videochat-theme', 'light');
            applyThemeState('light');
        });

        els.themeDark.addEventListener('click', () => {
            localStorage.setItem('videochat-theme', 'dark');
            applyThemeState('dark');
        });
    }

    async function bootstrap() {
        try {
            const result = await sendJson(urls.session);
            state.currentUser = result.user;
            state.contacts = result.contacts || [];

            state.contacts.forEach((contact) => {
                ensureConversation(contact.id);
            });

            if (!state.selectedPeerId) {
                state.selectedPeerId = Number(query.get('peer')) || state.contacts[0]?.id || null;
            }

            if (els.currentUserName) {
                els.currentUserName.textContent = state.currentUser.name;
            }
            if (els.currentUserEmail) {
                els.currentUserEmail.textContent = state.currentUser.email;
            }
            if (els.currentUserAvatar) {
                els.currentUserAvatar.textContent = buildAvatarMarkup(state.currentUser.name);
            }

            renderAll();
            wireRealtime();
            wireThemeToggles();
            populateDevices();
            updateVideoState();
            syncCallLaunchButton();

            if (pageKind === 'chat-call' && state.selectedPeerId) {
                selectPeer(state.selectedPeerId);
                pollCallState();
                if (shouldAutostartCall) {
                    startCall().catch((error) => updateMediaNotice(error.message));
                }
            }
        } catch (error) {
            window.log('bootstrap error', error.message);
            if (page.startsWith('app-')) {
                window.location.href = appUrl('/login');
            }
        }
    }

    if (els.messageInput) {
        els.messageInput.addEventListener('input', () => autoResize(els.messageInput));
    }
    if (els.messageForm) {
        els.messageForm.addEventListener('submit', handleMessageSubmit);
    }
    if (els.cameraToggle) {
        els.cameraToggle.addEventListener('click', () => toggleCamera().catch((error) => updateMediaNotice(error.message)));
    }
    if (els.micToggle) {
        els.micToggle.addEventListener('click', () => toggleMic().catch((error) => updateMediaNotice(error.message)));
    }
    if (els.callToggle) {
        els.callToggle.addEventListener('click', () => toggleCall().catch((error) => updateMediaNotice(error.message)));
    }
    if (els.callLaunch) {
        els.callLaunch.addEventListener('click', () => startCall().catch((error) => updateMediaNotice(error.message)));
    }
    if (els.acceptCall) {
        els.acceptCall.addEventListener('click', () => acceptIncomingCall().catch((error) => updateMediaNotice(error.message)));
    }
    if (els.declineCall) {
        els.declineCall.addEventListener('click', () => declineIncomingCall().catch((error) => updateMediaNotice(error.message)));
    }
    if (els.logoutButton) {
        els.logoutButton.addEventListener('click', async () => {
            try {
                await sendJson(urls.logout, 'POST');
            } finally {
                stopRingtone();
                window.location.href = appUrl('/login');
            }
        });
    }

    bootstrap();
}

if (page === 'login') {
    initLoginPage();
}

if (page.startsWith('app-')) {
    initAppPages();
}
