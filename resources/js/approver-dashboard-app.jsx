import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import ClearanceApprovalTable from './approver-dashboard/ClearanceApprovalTable';
import EnrollmentApprovalQueue from './approver-dashboard/EnrollmentApprovalQueue';
import MatriculationChangeQueue from './approver-dashboard/MatriculationChangeQueue';
import GradeSubmissionQueue from './components/GradeSubmissionQueue';

const EMPTY_CONTEXT = {
    clearances: [],
    enrollments: [],
    changes: [],
    gradeSubmissions: [],
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            clearances: Array.isArray(parsed.clearances) ? parsed.clearances : [],
            enrollments: Array.isArray(parsed.enrollments) ? parsed.enrollments : [],
            changes: Array.isArray(parsed.changes) ? parsed.changes : [],
            gradeSubmissions: Array.isArray(parsed.gradeSubmissions) ? parsed.gradeSubmissions : [],
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

const el = document.getElementById('approver-dashboard-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <>
                <ClearanceApprovalTable rows={context.clearances} csrfToken={csrfToken} />
                <EnrollmentApprovalQueue rows={context.enrollments} csrfToken={csrfToken} />
                <MatriculationChangeQueue rows={context.changes} csrfToken={csrfToken} />
                <GradeSubmissionQueue rows={context.gradeSubmissions} csrfToken={csrfToken} title="Grades Awaiting Chair Approval" />
            </>
        </ErrorBoundary>
    );
}
