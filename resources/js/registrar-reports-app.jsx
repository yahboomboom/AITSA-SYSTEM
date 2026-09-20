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
            pendingApplicants: parsed.pendingApplicants ?? 0,
            dashboardUrl: parsed.dashboardUrl ?? '',
            rows: Array.isArray(parsed.rows) ? parsed.rows : [],
            schoolYear: parsed.schoolYear ?? '2026-2027',
            semester: parsed.semester ?? 1,
        };
    } catch {
        return { summary: EMPTY_SUMMARY, pendingApplicants: 0, dashboardUrl: '', rows: [], schoolYear: '2026-2027', semester: 1 };
    }
}

const el = document.getElementById('registrar-reports-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <div className="space-y-5">
                <SummaryCards summary={context.summary} />
                {context.pendingApplicants > 0 && (
                    <div className="flex items-center gap-4 bg-white dark:bg-panelDark border border-brandGold/30 dark:border-brandGold/20 rounded-lg shadow-sm px-5 py-4">
                        <div className="w-9 h-9 rounded bg-brandGold/10 flex items-center justify-center flex-shrink-0">
                            <i className="fa-solid fa-user-clock text-brandGold text-sm" />
                        </div>
                        <div className="flex-1">
                            <p className="text-sm font-medium text-brandNavy dark:text-slate-200">{context.pendingApplicants} application(s) awaiting your review</p>
                            <p className="text-xs text-brandNavy/60 dark:text-slate-400 mt-0.5">These applicants have not yet been verified. Go to the Registrar Workspace to process them.</p>
                        </div>
                        <a href={context.dashboardUrl} className="ui-btn-primary flex-shrink-0 bg-brandGold text-white hover:bg-brandNavy transition-colors">
                            Review now
                        </a>
                    </div>
                )}
                <ReportTable rows={context.rows} csrfToken={csrfToken} schoolYear={context.schoolYear} semester={context.semester} />
            </div>
        </ErrorBoundary>
    );
}
