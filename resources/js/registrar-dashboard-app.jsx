import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import AdmissionsPipelineCard from './registrar-dashboard/AdmissionsPipelineCard';
import GradeSubmissionQueue from './components/GradeSubmissionQueue';

const EMPTY_CONTEXT = {
    applicants: [],
    clearances: [],
    documentsPageUrl: '',
    gradeSubmissions: [],
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            applicants: Array.isArray(parsed.applicants) ? parsed.applicants : [],
            clearances: Array.isArray(parsed.clearances) ? parsed.clearances : [],
            documentsPageUrl: parsed.documentsPageUrl ?? '',
            gradeSubmissions: Array.isArray(parsed.gradeSubmissions) ? parsed.gradeSubmissions : [],
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function RegistrarDashboardApp({ context, csrfToken }) {
    return (
        <div className="space-y-6">
            <AdmissionsPipelineCard
                applicants={context.applicants}
                clearances={context.clearances}
                csrfToken={csrfToken}
                documentsPageUrl={context.documentsPageUrl}
            />
            <GradeSubmissionQueue rows={context.gradeSubmissions} csrfToken={csrfToken} title="Grades Awaiting Registrar Approval" approveLabel="Finalize" />
        </div>
    );
}

const el = document.getElementById('registrar-dashboard-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <RegistrarDashboardApp context={context} csrfToken={csrfToken} />
        </ErrorBoundary>
    );
}
