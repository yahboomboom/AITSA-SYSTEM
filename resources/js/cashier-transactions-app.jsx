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
    const params = new URLSearchParams(window.location.search);
    const initialSearch = params.get('search') ?? '';
    const initialView = params.get('view') === 'full' ? 'full' : 'latest';

    createRoot(el).render(
        <ErrorBoundary>
            <TransactionsTable rows={context.rows} initialSearch={initialSearch} initialView={initialView} />
        </ErrorBoundary>
    );
}
