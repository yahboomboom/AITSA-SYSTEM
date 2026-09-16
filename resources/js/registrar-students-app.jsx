import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import StudentRegistryTable from './registrar-students/StudentRegistryTable';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            rows: Array.isArray(parsed.rows) ? parsed.rows : [],
            searchUrl: parsed.searchUrl ?? '',
        };
    } catch {
        return { rows: [], searchUrl: '' };
    }
}

const el = document.getElementById('registrar-students-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <StudentRegistryTable searchUrl={context.searchUrl} csrfToken={csrfToken} />
        </ErrorBoundary>
    );
}
