import axios from 'axios';

// The app isn't always served from the domain root (e.g. a subdirectory
// under XAMPP htdocs), so a bare '/api' would miss that prefix. Read the
// real base from the <meta name="api-base"> tag Laravel renders with the
// request's actual root, falling back to '/api' for a root-served app.
const apiBase = document.querySelector('meta[name="api-base"]')?.content ?? '/api';

// Same-origin: the session cookie authenticates; axios auto-sends the
// XSRF-TOKEN cookie as X-XSRF-TOKEN, which Sanctum's stateful mode verifies.
const api = axios.create({
    baseURL: apiBase,
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
});

export default api;
