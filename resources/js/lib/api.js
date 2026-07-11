import axios from 'axios';

// Same-origin: the session cookie authenticates; axios auto-sends the
// XSRF-TOKEN cookie as X-XSRF-TOKEN, which Sanctum's stateful mode verifies.
const api = axios.create({
    baseURL: '/api',
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
});

export default api;
