# Call Signaling Architecture

## Overview

The application now uses a socket-first signaling model for WebRTC call setup, with polling retained as a fallback and recovery path.

The backend broadcasts a realtime call-state event on a dedicated private channel whenever offer, answer, ICE candidates, or end-call state changes:

- [app/Events/CallSignalUpdated.php](/e:/Projects/Robist-Ventures/products/video-chat/app/Events/CallSignalUpdated.php:1)
- [routes/channels.php](/e:/Projects/Robist-Ventures/products/video-chat/routes/channels.php:40)
- [app/Http/Controllers/ChatController.php](/e:/Projects/Robist-Ventures/products/video-chat/app/Http/Controllers/ChatController.php:183)

The frontend subscribes to `call-signaling.{currentUserId}`, applies incoming signaling updates immediately, and only enables `/api/call-state` polling when realtime is unavailable:

- [resources/js/app.js](/e:/Projects/Robist-Ventures/products/video-chat/resources/js/app.js:952)
- [resources/js/app.js](/e:/Projects/Robist-Ventures/products/video-chat/resources/js/app.js:1084)
- [resources/js/app.js](/e:/Projects/Robist-Ventures/products/video-chat/resources/js/app.js:1273)

## Current Architecture

- Primary transport: websocket events over Echo/Pusher for call signaling.
- Backend state source: the same cached pairwise call state keyed by the two participants.
- Event model: every signaling mutation writes cache, then broadcasts the updated state to both participants.
- Frontend behavior: apply socket events immediately; if Echo is disconnected or the signaling channel is not subscribed, fall back to polling on the chat/call page.
- Recovery path: the chat page still does an initial `/api/call-state` sync on load and on fallback, so missed socket events can be recovered from cache.
