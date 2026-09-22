import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import AccountsTable from './cashier-accounts/AccountsTable';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            rows: Array.isArray(parsed.rows) ? parsed.rows : [],
            reviewUrl: parsed.reviewUrl ?? '',
            historyUrl: parsed.historyUrl ?? '',
        };
    } catch {
        return { rows: [], reviewUrl: '', historyUrl: '' };
    }
}

const el = document.getElementById('cashier-accounts-root');
if (el) {
    const context = parseContext(el.dataset.context);

    createRoot(el).render(
        <ErrorBoundary>
            <AccountsTable rows={context.rows} reviewUrl={context.reviewUrl} historyUrl={context.historyUrl} />
        </ErrorBoundary>
    );
}
