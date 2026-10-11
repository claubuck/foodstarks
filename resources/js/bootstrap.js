import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

// El navegador debe conectar a localhost (o dominio público), nunca a "reverb" (solo resuelve en Docker).
const reverbHost = typeof window !== 'undefined' && window.REVERB_WS_HOST != null
    ? window.REVERB_WS_HOST
    : (import.meta.env.VITE_REVERB_HOST ?? 'localhost');
const reverbPort = typeof window !== 'undefined' && window.REVERB_WS_PORT != null
    ? Number(window.REVERB_WS_PORT)
    : (Number(import.meta.env.VITE_REVERB_PORT) || 8080);
const reverbScheme = typeof window !== 'undefined' && window.REVERB_WS_SCHEME != null
    ? window.REVERB_WS_SCHEME
    : (import.meta.env.VITE_REVERB_SCHEME ?? 'http');

window.Echo = new Echo({
    broadcaster: 'reverb',
    // La key llega en runtime desde el servidor: la imagen se compila en CI sin .env.
    key: typeof window !== 'undefined' && window.REVERB_APP_KEY != null
        ? window.REVERB_APP_KEY
        : import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
});
