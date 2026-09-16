import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import CreateStudentForm from './admin-create-student/CreateStudentForm';

const EMPTY_CONTEXT = { applicant: null, programs: [], old: {} };

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            applicant: parsed.applicant ?? null,
            programs: Array.isArray(parsed.programs) ? parsed.programs : [],
            old: parsed.old ?? {},
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

const el = document.getElementById('admin-create-student-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const storeUrl = el.dataset.storeUrl ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <CreateStudentForm
                applicant={context.applicant}
                programs={context.programs}
                old={context.old}
                csrfToken={csrfToken}
                actionUrl={storeUrl}
            />
        </ErrorBoundary>
    );
}
