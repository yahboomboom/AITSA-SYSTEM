import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import SummaryCards from './admin-reports/SummaryCards';
import AdmissionPipeline from './admin-reports/AdmissionPipeline';
import ProgramBreakdown from './admin-reports/ProgramBreakdown';
import ReportTable from './admin-reports/ReportTable';

const EMPTY_CONTEXT = {
    summary: { total: 0, cleared: 0, pending: 0, cashierOk: 0 },
    pipeline: { pendingApplicants: 0, verifiedApplicants: 0, totalStudents: 0 },
    programBreakdown: [],
    rows: [],
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            summary: parsed.summary ?? EMPTY_CONTEXT.summary,
            pipeline: parsed.pipeline ?? EMPTY_CONTEXT.pipeline,
            programBreakdown: Array.isArray(parsed.programBreakdown) ? parsed.programBreakdown : [],
            rows: Array.isArray(parsed.rows) ? parsed.rows : [],
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

const el = document.getElementById('admin-reports-root');
if (el) {
    const context = parseContext(el.dataset.context);

    createRoot(el).render(
        <ErrorBoundary>
            <div className="space-y-6">
                <SummaryCards summary={context.summary} />
                <AdmissionPipeline pipeline={context.pipeline} />
                <ProgramBreakdown programBreakdown={context.programBreakdown} />
                <ReportTable rows={context.rows} />
            </div>
        </ErrorBoundary>
    );
}
