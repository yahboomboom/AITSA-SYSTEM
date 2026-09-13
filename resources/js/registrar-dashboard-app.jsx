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
    documentsSearchUrl: '',
    documentsPendingCount: 0,
    documentsRejectedCount: 0,
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            applicants: Array.isArray(parsed.applicants) ? parsed.applicants : [],
            stats: parsed.stats ?? EMPTY_CONTEXT.stats,
            clearances: Array.isArray(parsed.clearances) ? parsed.clearances : [],
            documentsSearchUrl: parsed.documentsSearchUrl ?? '',
            documentsPendingCount: parsed.documentsPendingCount ?? 0,
            documentsRejectedCount: parsed.documentsRejectedCount ?? 0,
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
                <DocumentSubmissionsTable
                    searchUrl={context.documentsSearchUrl}
                    pendingCount={context.documentsPendingCount}
                    rejectedCount={context.documentsRejectedCount}
                    csrfToken={csrfToken}
                />
            </div>
        </ErrorBoundary>
    );
}
