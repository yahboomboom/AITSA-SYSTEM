import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import TransactionsTable from './cashier-transactions/TransactionsTable';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return { rows: Array.isArray(parsed.rows) ? parsed.rows : [] };
    } catch {
        return { rows: [] };
    }
}

const el = document.getElementById('cashier-transactions-root');
if (el) {
    const context = parseContext(el.dataset.context);

    createRoot(el).render(
        <ErrorBoundary>
            <TransactionsTable rows={context.rows} />
        </ErrorBoundary>
    );
}
