import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import AuditTable from './admin-audit/AuditTable';

const EMPTY_CONTEXT = {
    logs: [],
    pagination: { total: 0, firstItem: 0, lastItem: 0, prevPageUrl: null, nextPageUrl: null },
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            logs: Array.isArray(parsed.logs) ? parsed.logs : [],
            pagination: parsed.pagination ?? EMPTY_CONTEXT.pagination,
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

const el = document.getElementById('admin-audit-root');
if (el) {
    const context = parseContext(el.dataset.context);

    createRoot(el).render(
        <ErrorBoundary>
            <AuditTable logs={context.logs} pagination={context.pagination} />
        </ErrorBoundary>
    );
}
