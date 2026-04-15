import './bootstrap';
import './utils';

const page = document.body.dataset.page;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const sanitize = (value) => window.DOMPurify ? window.DOMPurify.sanitize(value) : value;

function autoResize(textarea) {
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
            const payload = {
                email: formData.get('email'),
                password: formData.get('password'),
            };

            const result = await sendJson(form.action, 'POST', payload);
            window.location.href = result.redirect || '/home';
        } catch (error) {
            errorBox.hidden = false;
            errorBox.textContent = error.message;
        }
    });
}

function initWorkspace() {
    const root = document.getElementById('workspace-app');

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

    const els = {
        currentUserName: document.getElementById('current-user-name'),
        currentUserEmail: document.getElementById('current-user-email'),
        peerName: document.getElementById('peer-name'),
        peerMeta: document.getElementById('peer-meta'),
        mediaNotice: document.getElementById('media-notice'),
        contactCount: document.getElementById('contact-count'),
        contactList: document.getElementById('contact-list'),
        callStatus: document.getElementById('call-status'),
        localMediaState: document.getElementById('local-media-state'),
        localVideo: document.getElementById('local-video'),
        remoteVideo: document.getElementById('remote-video'),
        localVideoEmpty: document.getElementById('local-video-empty'),
        localVideoEmptyCopy: document.getElementById('local-video-empty-copy'),
        remoteVideoEmpty: document.getElementById('remote-video-empty'),
        messageList: document.getElementById('message-list'),
        messageState: document.getElementById('message-state'),
        messageForm: document.getElementById('message-form'),
        messageInput: document.getElementById('message-input'),
        messageSubmit: document.getElementById('message-submit'),
        cameraToggle: document.getElementById('camera-toggle'),
        micToggle: document.getElementById('mic-toggle'),
        callToggle: document.getElementById('call-toggle'),
        logoutButton: document.getElementById('logout-button'),
    };

    const state = {
        currentUser: null,
        contacts: [],
        conversations: {},
        unread: {},
        selectedPeerId: null,
        onlineIds: new Set(),
        localStream: null,
        remoteStream: null,
        peerConnection: null,
        pollingTimer: null,
        signaling: {
            offerAt: null,
            answerAt: null,
            candidateCounts: {},
            endedAt: null,
        },
        media: {
            videoEnabled: false,
            audioEnabled: false,
        },
        secureContext: window.isSecureContext || ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname),
    };

    const query = new URLSearchParams(window.location.search);
    const insecureContextMessage = `Camera and mic are blocked on ${window.location.origin}. Open this app through HTTPS on your LAN IP or use localhost on this device.`;

    function updateCallStatus(label, tone = 'muted') {
        els.callStatus.textContent = label;
        els.callStatus.className = `status-pill ${tone}`.trim();
    }

    function setPeerMeta(text) {
        els.peerMeta.textContent = text;
    }

    function canUseMedia() {
        return state.secureContext && !!navigator.mediaDevices?.getUserMedia;
    }

    function updateMediaNotice(message = '') {
        if (!els.mediaNotice) {
            return;
        }

        els.mediaNotice.hidden = message === '';
        els.mediaNotice.textContent = message;
    }

    function selectedPeer() {
        return state.contacts.find((contact) => contact.id === state.selectedPeerId) || null;
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

    function renderContacts() {
        els.contactCount.textContent = String(state.contacts.length);
        els.contactList.innerHTML = '';

        state.contacts.forEach((contact) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `contact-card${contact.id === state.selectedPeerId ? ' active' : ''}`;
            button.dataset.peerId = String(contact.id);

            const unread = state.unread[contact.id] || 0;
            const online = state.onlineIds.has(contact.id);

            button.innerHTML = `
                <div class="contact-name-row">
                    <span class="contact-name">${sanitize(contact.name)}</span>
                    <span class="presence-dot ${online ? 'online' : ''}"></span>
                </div>
                <span class="muted-text">${sanitize(contact.email)}</span>
                <div class="contact-name-row">
                    <span class="muted-text">${online ? 'Available now' : 'Offline'}</span>
                    ${unread ? `<span class="unread-pill">${unread}</span>` : ''}
                </div>
            `;

            button.addEventListener('click', () => selectPeer(contact.id));
            els.contactList.appendChild(button);
        });
    }

    function renderMessages() {
        const peer = selectedPeer();
        els.messageList.innerHTML = '';

        if (!peer) {
            els.messageList.innerHTML = '<div class="empty-conversation"><strong>Select a peer</strong><span>Messages and call controls are activated when a contact is selected.</span></div>';
            els.messageState.textContent = 'No recipient selected';
            els.messageInput.disabled = true;
            els.messageSubmit.disabled = true;
            els.callToggle.disabled = true;
            return;
        }

        els.messageState.textContent = `Chatting with ${peer.name}`;
        els.messageInput.disabled = false;
        els.messageSubmit.disabled = false;
        els.callToggle.disabled = !canUseMedia();

        const items = state.conversations[peer.id] || [];

        if (!items.length) {
            els.messageList.innerHTML = `<div class="empty-conversation"><strong>No messages yet</strong><span>Start the conversation with ${sanitize(peer.name)}.</span></div>`;
            return;
        }

        items.forEach((entry) => {
            const item = document.createElement('article');
            item.className = `message-item${entry.self ? ' self' : ''}`;
            item.innerHTML = `
                <span class="message-meta">${sanitize(entry.self ? 'You' : entry.sender)} · ${sanitize(entry.timeLabel)}</span>
                <div class="message-bubble">${sanitize(entry.body)}</div>
            `;
            els.messageList.appendChild(item);
        });

        els.messageList.scrollTop = els.messageList.scrollHeight;
    }

    function updateVideoState() {
        els.localVideoEmpty.hidden = !!state.localStream;
        els.remoteVideoEmpty.hidden = !!state.remoteStream;
        els.localMediaState.textContent = state.media.videoEnabled ? 'Camera live' : 'Camera off';
        els.localMediaState.className = `status-pill ${state.media.videoEnabled ? 'live' : 'muted'}`;
        els.cameraToggle.textContent = state.media.videoEnabled ? 'Camera off' : 'Camera on';
        els.micToggle.textContent = state.media.audioEnabled ? 'Mic off' : 'Mic on';
        els.callToggle.textContent = state.peerConnection ? 'End call' : 'Start call';
        els.cameraToggle.disabled = !canUseMedia();
        els.micToggle.disabled = !canUseMedia();

        if (!state.localStream && els.localVideoEmptyCopy) {
            els.localVideoEmptyCopy.textContent = canUseMedia()
                ? 'Turn on camera when you are ready.'
                : 'Camera preview is unavailable on insecure LAN HTTP. Use HTTPS on the LAN IP or localhost.';
        }
    }

    async function ensureLocalStream() {
        if (!canUseMedia()) {
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
        els.localVideo.srcObject = stream;
        updateVideoState();

        return stream;
    }

    function closePeerConnection(resetRemote = true) {
        if (state.peerConnection) {
            state.peerConnection.onicecandidate = null;
            state.peerConnection.ontrack = null;
            state.peerConnection.onconnectionstatechange = null;
            state.peerConnection.close();
            state.peerConnection = null;
        }

        if (resetRemote) {
            state.remoteStream = null;
            els.remoteVideo.srcObject = null;
        }

        state.signaling = {
            offerAt: null,
            answerAt: null,
            candidateCounts: {},
            endedAt: null,
        };
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
        }

        connection.ontrack = (event) => {
            state.remoteStream = event.streams[0];
            els.remoteVideo.srcObject = state.remoteStream;
            updateVideoState();
            updateCallStatus('Connected', 'live');
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
                updateCallStatus('Connected', 'live');
            } else if (['connecting', 'new'].includes(connectionState)) {
                updateCallStatus('Connecting', 'warn');
            } else if (['disconnected', 'failed', 'closed'].includes(connectionState)) {
                updateCallStatus('Idle', 'muted');
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

    async function applyCandidates(call) {
        if (!call.candidates || !state.peerConnection || !state.currentUser) {
            return;
        }

        const peer = selectedPeer();
        if (!peer) {
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

    async function handleCallState(call) {
        const peer = selectedPeer();
        if (!peer || !call) {
            return;
        }

        if (call.ended_at && call.ended_at !== state.signaling.endedAt) {
            state.signaling.endedAt = call.ended_at;
            closePeerConnection();
            updateCallStatus('Idle', 'muted');
            return;
        }

        if (call.offer && call.offer.from === peer.id && call.offer.updated_at !== state.signaling.offerAt) {
            state.signaling.offerAt = call.offer.updated_at;
            await ensureLocalStream();
            const connection = await ensurePeerConnection();
            await syncLocalTracks();
            await connection.setRemoteDescription(new RTCSessionDescription(call.offer.sdp));
            const answer = await connection.createAnswer();
            await connection.setLocalDescription(answer);
            await sendJson(urls.answer, 'POST', {
                peer_id: peer.id,
                sdp: answer,
            });
            updateCallStatus('Answering', 'warn');
        }

        if (call.answer && call.answer.from === peer.id && call.answer.updated_at !== state.signaling.answerAt && state.peerConnection) {
            state.signaling.answerAt = call.answer.updated_at;
            await state.peerConnection.setRemoteDescription(new RTCSessionDescription(call.answer.sdp));
            updateCallStatus('Connected', 'live');
        }

        await applyCandidates(call);
    }

    async function pollCallState() {
        const peer = selectedPeer();

        if (!peer) {
            return;
        }

        try {
            const url = `${urls.callState}?peer_id=${peer.id}`;
            const result = await sendJson(url);
            await handleCallState(result.call || {});
        } catch (error) {
            window.log('poll error', error.message);
        }
    }

    function startPolling() {
        if (state.pollingTimer) {
            clearInterval(state.pollingTimer);
        }

        state.pollingTimer = window.setInterval(() => {
            pollCallState();
        }, 1800);
    }

    async function startCall() {
        const peer = selectedPeer();
        if (!peer) {
            return;
        }

        await ensureLocalStream();
        const connection = await ensurePeerConnection();
        await syncLocalTracks();

        const offer = await connection.createOffer();
        await connection.setLocalDescription(offer);
        await sendJson(urls.offer, 'POST', {
            peer_id: peer.id,
            sdp: offer,
        });

        updateCallStatus('Calling', 'warn');
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
        updateCallStatus('Idle', 'muted');
    }

    async function toggleCall() {
        if (!canUseMedia()) {
            throw new Error(insecureContextMessage);
        }

        if (state.peerConnection) {
            await endCall();
            return;
        }

        await startCall();
    }

    function formatTime() {
        return new Intl.DateTimeFormat([], {
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date());
    }

    function selectPeer(peerId) {
        if (state.selectedPeerId === peerId) {
            return;
        }

        if (state.peerConnection) {
            endCall();
        }

        state.selectedPeerId = peerId;
        state.unread[peerId] = 0;
        const peer = selectedPeer();
        query.set('peer', String(peerId));
        history.replaceState({}, '', `${window.location.pathname}?${query.toString()}`);

        els.peerName.textContent = peer?.name || 'Select a peer';
        setPeerMeta(peer ? `${peer.email} · ${state.onlineIds.has(peer.id) ? 'available on LAN' : 'currently offline'}` : 'Choose someone from the roster to begin chat or video.');
        renderContacts();
        renderMessages();
        updateCallStatus('Idle', 'muted');
        updateMediaNotice(canUseMedia() ? '' : insecureContextMessage);
    }

    async function handleMessageSubmit(event) {
        event.preventDefault();
        const peer = selectedPeer();
        const value = els.messageInput.value.trim();

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
            renderMessages();
        } catch (error) {
            els.messageState.textContent = error.message;
        }
    }

    function wireRealtime() {
        if (!window.Echo || !state.currentUser) {
            return;
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
                    renderContacts();
                } else {
                    renderMessages();
                }
            });

        window.Echo.join('presence.online')
            .here((users) => {
                state.onlineIds = new Set(users.map((user) => user.id).filter((id) => id !== state.currentUser.id));
                renderContacts();
                const peer = selectedPeer();
                if (peer) {
                    setPeerMeta(`${peer.email} · ${state.onlineIds.has(peer.id) ? 'available on LAN' : 'currently offline'}`);
                }
            })
            .joining((user) => {
                if (user.id !== state.currentUser.id) {
                    state.onlineIds.add(user.id);
                    renderContacts();
                }
            })
            .leaving((user) => {
                state.onlineIds.delete(user.id);
                renderContacts();
            });
    }

    async function bootstrapWorkspace() {
        try {
            const result = await sendJson(urls.session);
            state.currentUser = result.user;
            state.contacts = result.contacts || [];

            els.currentUserName.textContent = state.currentUser.name;
            els.currentUserEmail.textContent = state.currentUser.email;

            const initialPeerId = Number(query.get('peer')) || state.contacts[0]?.id || null;
            renderContacts();
            renderMessages();

            if (initialPeerId) {
                selectPeer(initialPeerId);
            }

            wireRealtime();
            startPolling();
            updateMediaNotice(canUseMedia() ? '' : insecureContextMessage);
            updateVideoState();
        } catch (error) {
            els.peerName.textContent = 'Session failed';
            setPeerMeta(error.message);
        }
    }

    els.messageInput.addEventListener('input', () => autoResize(els.messageInput));
    els.messageForm.addEventListener('submit', handleMessageSubmit);
    els.cameraToggle.addEventListener('click', () => toggleCamera().catch((error) => setPeerMeta(error.message)));
    els.micToggle.addEventListener('click', () => toggleMic().catch((error) => setPeerMeta(error.message)));
    els.callToggle.addEventListener('click', () => toggleCall().catch((error) => setPeerMeta(error.message)));
    els.logoutButton.addEventListener('click', async () => {
        try {
            await sendJson(urls.logout, 'POST');
        } finally {
            window.location.href = '/login';
        }
    });

    bootstrapWorkspace();
}

if (page === 'login') {
    initLoginPage();
}

if (page === 'workspace') {
    initWorkspace();
}

