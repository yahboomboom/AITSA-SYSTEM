import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import GradesForm from './section-grades/GradesForm';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return { students: Array.isArray(parsed.students) ? parsed.students : [] };
    } catch {
        return { students: [] };
    }
}

const el = document.getElementById('section-grades-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const storeUrl = el.dataset.storeUrl ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <GradesForm students={context.students} csrfToken={csrfToken} actionUrl={storeUrl} />
        </ErrorBoundary>
    );
}
