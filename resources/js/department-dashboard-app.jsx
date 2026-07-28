import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import ClearanceItemQueueTable from './department-dashboard/ClearanceItemQueueTable';

function parseRows(raw) {
    try {
        const parsed = JSON.parse(raw ?? '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

const el = document.getElementById('department-dashboard-root');
if (el) {
    const rows = parseRows(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <ClearanceItemQueueTable rows={rows} csrfToken={csrfToken} />
        </ErrorBoundary>
    );
}
