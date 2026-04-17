# Video Chat Gap Analysis

## Context

Call connecting is unreliable.

This document analyzes gaps in the current video chat implementation across UX, architecture, codebase, and system behavior. It focuses on issues such as calls getting stuck on `CONNECTING`, users needing to accept twice, especially on mobile browsers, and other cases where the current flow can create a bad user experience.

## Findings

### 1. No STUN/TURN means call setup will fail or hang outside the easiest LAN cases

`RTCPeerConnection` is created with `iceServers: []` in [resources/js/app.js](/e:/Projects/Robist-Ventures/products/video-chat/resources/js/app.js:826).

That is a major system gap. On mobile networks, symmetric NATs, Wi-Fi to cellular, or many browser/device pairs, users will get stuck on `CONNECTING` even though signaling succeeded.

### 2. The “accept twice” problem is likely caused by the current accept flow clearing UI state before the answer path succeeds

`acceptIncomingCall()` clears the incoming call immediately, before `getUserMedia`, `setRemoteDescription`, `createAnswer`, and answer POST all complete: [resources/js/app.js](/e:/Projects/Robist-Ventures/products/video-chat/resources/js/app.js:1167).

Inference: on mobile, permission prompts, slow resume, or temporary network delay can make the first accept attempt look like it did nothing, because the banner is cleared early and there is no locked `accepting` state. If the offer is still present, the UI can surface again and users tap again.

### 3. Incoming calls on non-call pages force a redirect instead of presenting a stable incoming-call UX

On active incoming offer, non-`chat-call` pages immediately do `window.location.href = appRouteForPeer(peer.id)`: [resources/js/app.js](/e:/Projects/Robist-Ventures/products/video-chat/resources/js/app.js:988).

This is bad UX on desktop and worse on mobile:

- it interrupts whatever the user was doing
- it creates a two-step mental flow: “incoming call detected” then “accept on another page”
- it increases the chance that socket/page state resets during navigation

### 4. Fallback polling only runs on the chat/call page, so incoming calls can be missed when realtime is disconnected on other pages

`shouldUsePollingFallback()` only returns true for `pageKind === 'chat-call'`: [resources/js/app.js](/e:/Projects\Robist-Ventures/products/video-chat/resources/js/app.js:1094).

That means:

- on dashboard/settings/chat hub, if websocket drops, incoming calls may never surface
- the system currently has no reliable incoming-call path outside the call page when sockets are down

### 5. Offer TTL is too aggressive for mobile and page-transition realities

`OFFER_TTL_SECONDS = 8` in [app/Http/Controllers/ChatController.php](/e:/Projects/Robist-Ventures/products/video-chat/app/Http/Controllers/ChatController.php:15).

Eight seconds is short when you include:

- page redirect
- browser permission prompt
- background tab wake-up
- mobile CPU/network lag

This can easily create “call appeared, I accepted, then it disappeared / stayed connecting”.

### 6. Call state is stored in file cache, which is not robust for signaling at scale or across instances

`.env` uses `CACHE_DRIVER=file`: [.env](/e:/Projects/Robist-Ventures/products/video-chat/.env:24).

That is okay for a local demo, but poor for realtime signaling:

- no strong multi-instance behavior
- slower and less predictable than Redis
- harder to reason about race conditions and expiry

### 7. There is no call session ID/versioning, so stale or out-of-order state can be applied

The pairwise cache key is just `call:{low}:{high}`: [app/Http/Controllers/ChatController.php](/e:/Projects/Robist-Ventures/products/video-chat/app/Http/Controllers/ChatController.php:200).

The event payload broadcasts full state each time: [app/Http/Controllers/ChatController.php](/e:/Projects/Robist-Ventures/products/video-chat/app/Http/Controllers/ChatController.php:183).

There is no explicit `call_id`, no monotonic revision, and no server-enforced state machine. That can produce racey behavior around:

- rapid redial
- duplicate answer/candidate events
- end old call / start new call overlap

### 8. Local media tracks were not stopped on end, which hurts privacy, battery, and camera reliability

`closePeerConnection()` only closed the peer connection and did not originally stop local tracks: [resources/js/app.js](/e:/Projects/Robist-Ventures/products/video-chat/resources/js/app.js:791).

That can create:

- “camera still on” confusion
- battery drain
- next call/device-lock issues on mobile

### 9. No explicit autoplay recovery for remote media on mobile browsers

Remote video is just `autoplay playsinline`: [resources/views/app/chat-call.blade.php](/e:/Projects/Robist-Ventures/products/video-chat/resources/views/app/chat-call.blade.php:78).

There is no `remoteVideo.play()` retry after user gesture or connection state change.

Inference: on mobile Safari or Chrome, audio/video can remain silent or blank until another interaction, which users interpret as “I need to accept again”.

### 10. No busy-state, debounce, or timeout around accept/start/end actions

The accept button exists, but there is no disabling or loading state while processing: [resources/views/app/chat-call.blade.php](/e:/Projects/Robist-Ventures/products/video-chat/resources/views/app/chat-call.blade.php:60).

That leads to:

- double taps
- duplicate requests
- confusing transitions
- no visible difference between “processing” and “broken”

### 11. Incoming-call detection fallback scales poorly

When polling fallback is active and there is no peer connection, the code loops all contacts and fetches call state one by one: [resources/js/app.js](/e:/Projects/Robist-Ventures/products/video-chat/resources/js/app.js:1038).

That becomes noisy and slow as contact count grows, and call discovery latency gets worse.

### 12. Settings UX is misleading because device selection is display-only

Camera and microphone selectors are populated, but not used when acquiring media: [resources/views/app/chat-call.blade.php](/e:/Projects/Robist-Ventures/products/video-chat/resources/views/app/chat-call.blade.php:88), [resources/js/app.js](/e:/Projects/Robist-Ventures/products/video-chat/resources/js/app.js:765).

That creates false expectation: the UI suggests device control that does not exist.

## Why Users May Feel They Must Accept Twice

- A forced redirect happens first on non-call pages, then accept happens on the chat page.
- The first accept clears incoming-call state too early.
- There is no `Accepting...` lock or debounce.
- Mobile permission/autoplay behavior can make the first interaction only unlock media/browser restrictions, not visibly complete the call.

## Suggested Solutions

### 1. Add TURN/STUN immediately

Use a real `iceServers` list with at least public STUN and production TURN. This is the biggest reliability fix.

### 2. Change accept flow to be transactional

Do not clear the incoming banner at the start.

Use:

- `incomingCall.status = "accepting"`
- disable Accept and Decline buttons
- only clear banner after answer POST succeeds or remote description is applied
- restore banner with error if any step fails

### 3. Replace forced redirect with a global incoming-call overlay

Show accept and decline on any page. Only navigate to the call page after user accepts. That removes one whole UX step.

### 4. Keep a broader fallback path for incoming calls when sockets are down

If realtime is disconnected, non-call pages still need a lightweight incoming-call poll or dedicated server push fallback.

### 5. Increase offer lifetime and add explicit call timeouts

Move offer TTL from 8s to something like 25 to 45s.

Add separate UI timeouts:

- outgoing ringing timeout
- connecting timeout
- missed-call timeout

### 6. Introduce a call ID and revision

Every new call should get a unique ID. Every state mutation should increment a revision. Frontend should ignore older state and events.

### 7. Move signaling cache to Redis

Use Redis for call state and expiry, especially if you plan multi-user or multi-instance deployment.

### 8. Stop local tracks on hangup and when leaving the call page

Call `track.stop()` on all local tracks when ending the session unless intentionally preserving preview.

### 9. Add explicit remote media playback recovery

After remote stream attach, call `video.play()` and surface a “Tap to resume audio” fallback on mobile if autoplay is blocked.

### 10. Add button state management and error UX

Use:

- `Starting...`
- `Accepting...`
- `Ending...`
- retryable error messages
- disabled controls during async operations

### 11. Handle busy and concurrent call rules explicitly

If user is already in a call, reject new offers with busy status instead of silently overwriting UI state.

### 12. Make settings real or remove them

Either wire chosen `deviceId` into `getUserMedia` constraints or remove those selectors until implemented.

## Recommended Implementation Order

1. TURN/STUN
2. Accept-flow fix with loading and debounce
3. Global incoming-call overlay instead of redirect
4. `call_id` and revisioned state machine
5. Redis cache
6. Autoplay/media recovery and local-track cleanup

## Note

This analysis is from the current code paths and environment configuration. It was not runtime-tested end to end in this session.
