window._ = require('lodash');

window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Echo/Pusher is optional; skip when packages are not installed.
let EchoLib = null;
let PusherLib = null;

try {
    EchoLib = require('laravel-echo').default;
    PusherLib = require('pusher-js');
} catch (error) {
    EchoLib = null;
    PusherLib = null;
}

if (EchoLib && PusherLib) {
    window.Pusher = PusherLib;

    const wsHost = process.env.MIX_PUSHER_HOST || window.location.hostname;
    const wsPort = process.env.MIX_PUSHER_PORT || 6001;
    const wsScheme = process.env.MIX_PUSHER_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');

    window.Echo = new EchoLib({
        broadcaster: 'pusher',
        key: process.env.MIX_PUSHER_APP_KEY,
        cluster: process.env.MIX_PUSHER_APP_CLUSTER ?? 'mt1',
        wsHost,
        wsPort,
        wssPort: wsPort,
        forceTLS: wsScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
    });
}
