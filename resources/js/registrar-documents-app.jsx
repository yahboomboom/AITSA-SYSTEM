import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import DocumentSubmissionsTable from './registrar-documents/DocumentSubmissionsTable';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            documentsSearchUrl: parsed.documentsSearchUrl ?? '',
            documentsPendingCount: parsed.documentsPendingCount ?? 0,
            documentsRejectedCount: parsed.documentsRejectedCount ?? 0,
        };
    } catch {
        return { documentsSearchUrl: '', documentsPendingCount: 0, documentsRejectedCount: 0 };
    }
}

const el = document.getElementById('registrar-documents-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <DocumentSubmissionsTable
                searchUrl={context.documentsSearchUrl}
                pendingCount={context.documentsPendingCount}
                rejectedCount={context.documentsRejectedCount}
                csrfToken={csrfToken}
            />
        </ErrorBoundary>
    );
}
