import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import AdmissionsPipelineCard from './registrar-dashboard/AdmissionsPipelineCard';
import GradeSubmissionQueue from './components/GradeSubmissionQueue';
import PasswordResetRequestsCard from './registrar-dashboard/PasswordResetRequestsCard';
import TempPasswordModal from './registrar-dashboard/TempPasswordModal';
import NoShowsCard from './registrar-dashboard/NoShowsCard';
import WithdrawnStudentsCard from './registrar-dashboard/WithdrawnStudentsCard';

const EMPTY_CONTEXT = {
    applicants: [],
    tempPasswords: [],
    clearances: [],
    documentsPageUrl: '',
    gradeSubmissions: [],
    passwordResetRequests: [],
    canManageWithdrawals: false,
    noShows: [],
    noShowThresholdDays: 14,
    noShowWithdrawUrl: '',
    noShowThresholdUrl: '',
    withdrawn: [],
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            applicants: Array.isArray(parsed.applicants) ? parsed.applicants : [],
            clearances: Array.isArray(parsed.clearances) ? parsed.clearances : [],
            documentsPageUrl: parsed.documentsPageUrl ?? '',
            gradeSubmissions: Array.isArray(parsed.gradeSubmissions) ? parsed.gradeSubmissions : [],
            passwordResetRequests: Array.isArray(parsed.passwordResetRequests) ? parsed.passwordResetRequests : [],
            tempPasswords: Array.isArray(parsed.tempPasswords) ? parsed.tempPasswords : [],
            canManageWithdrawals: parsed.canManageWithdrawals === true,
            noShows: Array.isArray(parsed.noShows) ? parsed.noShows : [],
            noShowThresholdDays: Number(parsed.noShowThresholdDays) || 14,
            noShowWithdrawUrl: parsed.noShowWithdrawUrl ?? '',
            noShowThresholdUrl: parsed.noShowThresholdUrl ?? '',
            withdrawn: Array.isArray(parsed.withdrawn) ? parsed.withdrawn : [],
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
            <PasswordResetRequestsCard rows={context.passwordResetRequests} csrfToken={csrfToken} />
            {context.tempPasswords.length > 0 && <TempPasswordModal items={context.tempPasswords} csrfToken={csrfToken} />}
            {context.canManageWithdrawals && (
                <>
                    <NoShowsCard
                        rows={context.noShows}
                        thresholdDays={context.noShowThresholdDays}
                        withdrawUrl={context.noShowWithdrawUrl}
                        thresholdUrl={context.noShowThresholdUrl}
                        csrfToken={csrfToken}
                    />
                    <WithdrawnStudentsCard rows={context.withdrawn} csrfToken={csrfToken} />
                </>
            )}
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
