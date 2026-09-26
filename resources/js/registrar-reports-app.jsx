import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import SummaryCards from './registrar-reports/SummaryCards';
import ReportTable from './registrar-reports/ReportTable';
import AdmissionPipeline from './registrar-reports/AdmissionPipeline';
import ProgramBreakdown from './registrar-reports/ProgramBreakdown';

const EMPTY_SUMMARY = { total: 0, registrarSigned: 0, registrarPending: 0, fullyCleared: 0 };
const EMPTY_PIPELINE = { pendingApplicants: 0, verifiedApplicants: 0, totalStudents: 0 };
const EMPTY_AGREEMENTS = { signed: 0 };

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            summary: parsed.summary ?? EMPTY_SUMMARY,
            pipeline: parsed.pipeline ?? EMPTY_PIPELINE,
            agreements: parsed.agreements ?? EMPTY_AGREEMENTS,
            programBreakdown: Array.isArray(parsed.programBreakdown) ? parsed.programBreakdown : [],
            rows: Array.isArray(parsed.rows) ? parsed.rows : [],
            schoolYear: parsed.schoolYear ?? '2026-2027',
            semester: parsed.semester ?? 1,
        };
    } catch {
        return {
            summary: EMPTY_SUMMARY,
            pipeline: EMPTY_PIPELINE,
            agreements: EMPTY_AGREEMENTS,
            programBreakdown: [],
            rows: [],
            schoolYear: '2026-2027',
            semester: 1,
        };
    }
}

const el = document.getElementById('registrar-reports-root');
if (el) {
    const context = parseContext(el.dataset.context);

    createRoot(el).render(
        <ErrorBoundary>
            <div className="space-y-5">
                <SummaryCards summary={context.summary} />
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                    <AdmissionPipeline pipeline={context.pipeline} agreementsSigned={context.agreements.signed} />
                    <ProgramBreakdown programBreakdown={context.programBreakdown} />
                </div>
                <ReportTable rows={context.rows} schoolYear={context.schoolYear} semester={context.semester} />
            </div>
        </ErrorBoundary>
    );
}
