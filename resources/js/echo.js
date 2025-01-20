import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    logToConsole: true, // Active le débogage
});

window.Echo.private(`channel-name`)
    .subscribed(() => {
        console.log('Abonné au channel : channel-name');
    })
    .listen('PodcastCreated', e => {
        console.log(e);
        console.log('PodcastCreated in private channel');
    })
    .listen('VideoCreated', e => {
        console.log(e);
        console.log('VideoCreated in private channel');
    });

window.Echo.channel(`my-channel`)
    .subscribed(() => {
        console.log('Abonné au channel : my-channel');
    })
    .listen('PodcastCreated', e => {
        console.log(e);
        console.log('PodcastCreated in public channel');
    })
    .listen('VideoCreated', e => {
        console.log(e);
        console.log('VideoCreated in public channel');
    });
