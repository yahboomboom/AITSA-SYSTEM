import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import SummaryCards from './registrar-reports/SummaryCards';
import ReportTable from './registrar-reports/ReportTable';

const EMPTY_SUMMARY = { total: 0, registrarSigned: 0, registrarPending: 0, fullyCleared: 0 };

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            summary: parsed.summary ?? EMPTY_SUMMARY,
            rows: Array.isArray(parsed.rows) ? parsed.rows : [],
            schoolYear: parsed.schoolYear ?? '2026-2027',
            semester: parsed.semester ?? 1,
        };
    } catch {
        return { summary: EMPTY_SUMMARY, rows: [], schoolYear: '2026-2027', semester: 1 };
    }
}

const el = document.getElementById('registrar-reports-root');
if (el) {
    const context = parseContext(el.dataset.context);

    createRoot(el).render(
        <ErrorBoundary>
            <div className="space-y-5">
                <SummaryCards summary={context.summary} />
                <ReportTable rows={context.rows} schoolYear={context.schoolYear} semester={context.semester} />
            </div>
        </ErrorBoundary>
    );
}
