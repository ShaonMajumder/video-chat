import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const configuredScheme = import.meta.env.VITE_PUSHER_SCHEME;
const wsScheme = window.location.protocol === 'https:' || configuredScheme === 'https' ? 'wss' : 'ws';
const wsPort = Number(import.meta.env.VITE_PUSHER_PORT || (wsScheme === 'wss' ? 443 : 6001));
const wsHost = import.meta.env.VITE_PUSHER_PUBLIC_HOST || window.location.hostname;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    authEndpoint: '/broadcasting/auth',
    forceTLS: wsScheme === 'wss',
    wsHost,
    wsPort,
    wssPort: wsPort,
    enabledTransports: ["ws", "wss"],
    withCredentials: true,
});
