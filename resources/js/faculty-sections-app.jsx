import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import SectionsTable from './faculty-sections/SectionsTable';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

const el = document.getElementById('faculty-sections-root');
if (el) {
    const sections = parseContext(el.dataset.context);

    createRoot(el).render(
        <ErrorBoundary>
            <SectionsTable sections={sections} />
        </ErrorBoundary>
    );
}
