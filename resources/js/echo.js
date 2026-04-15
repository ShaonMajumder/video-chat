import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const configuredScheme = import.meta.env.VITE_PUSHER_SCHEME;
const wsScheme = window.location.protocol === 'https:' || configuredScheme === 'https' ? 'wss' : 'ws';
const wsPort = Number(import.meta.env.VITE_PUSHER_PORT || (wsScheme === 'wss' ? 443 : 6001));
const wsHost = import.meta.env.VITE_PUSHER_PUBLIC_HOST || window.location.hostname;
const authEndpoint = '/api/realtime/auth';

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    authEndpoint,
    forceTLS: wsScheme === 'wss',
    wsHost,
    wsPort,
    wssPort: wsPort,
    enabledTransports: ["ws", "wss"],
    authorizer: (channel) => ({
        authorize: async (socketId, callback) => {
            try {
                const response = await fetch(authEndpoint, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams({
                        socket_id: socketId,
                        channel_name: channel.name,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    callback(data, null);
                    return;
                }

                callback(null, data);
            } catch (error) {
                callback(error, null);
            }
        },
    }),
});
