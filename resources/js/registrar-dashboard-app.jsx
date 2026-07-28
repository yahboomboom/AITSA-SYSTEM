import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import ApplicantQueueTable from './registrar-dashboard/ApplicantQueueTable';
import AdmissionStats from './registrar-dashboard/AdmissionStats';
import ClearanceQueueTable from './registrar-dashboard/ClearanceQueueTable';
import DocumentSubmissionsTable from './registrar-dashboard/DocumentSubmissionsTable';

const EMPTY_CONTEXT = {
    applicants: [],
    stats: { total: 0, approvedToday: 0, pending: 0 },
    clearances: [],
    documents: [],
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            applicants: Array.isArray(parsed.applicants) ? parsed.applicants : [],
            stats: parsed.stats ?? EMPTY_CONTEXT.stats,
            clearances: Array.isArray(parsed.clearances) ? parsed.clearances : [],
            documents: Array.isArray(parsed.documents) ? parsed.documents : [],
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

const el = document.getElementById('registrar-dashboard-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <div className="space-y-6">
                {context.applicants.length > 0 && (
                    <ApplicantQueueTable applicants={context.applicants} csrfToken={csrfToken} />
                )}
                <AdmissionStats
                    total={context.stats.total}
                    approvedToday={context.stats.approvedToday}
                    pending={context.stats.pending}
                />
                <ClearanceQueueTable rows={context.clearances} csrfToken={csrfToken} />
                <DocumentSubmissionsTable documents={context.documents} csrfToken={csrfToken} />
            </div>
        </ErrorBoundary>
    );
}
