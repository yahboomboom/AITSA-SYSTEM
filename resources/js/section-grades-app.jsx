import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import GradesForm from './section-grades/GradesForm';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            students: Array.isArray(parsed.students) ? parsed.students : [],
            submissionStatus: parsed.submissionStatus ?? 'draft',
            rejectedBy: parsed.rejectedBy ?? null,
            remarks: parsed.remarks ?? null,
        };
    } catch {
        return { students: [], submissionStatus: 'draft', rejectedBy: null, remarks: null };
    }
}

const el = document.getElementById('section-grades-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const storeUrl = el.dataset.storeUrl ?? '';
    const submitUrl = el.dataset.submitUrl ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <GradesForm
                students={context.students}
                csrfToken={csrfToken}
                actionUrl={storeUrl}
                submitUrl={submitUrl}
                submissionStatus={context.submissionStatus}
                rejectedBy={context.rejectedBy}
                remarks={context.remarks}
            />
        </ErrorBoundary>
    );
}
