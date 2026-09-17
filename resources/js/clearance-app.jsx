import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import MasterStatusBadge from './clearance/MasterStatusBadge';
import AccountingCard from './clearance/AccountingCard';
import RegistrarCard from './clearance/RegistrarCard';

const EMPTY_BREAKDOWN = {
    units: 0, rate: 0, tuition: 0, discount_name: null, discount_percent: 0,
    discount_amount: 0, misc: 0, assessment: 0, paid: 0, balance: 0, fully_paid: false,
};

const EMPTY_CONTEXT = {
    isCleared: false,
    printUrl: null,
    cashierCleared: false,
    registrarCleared: false,
    chairCleared: false,
    remarks: null,
    breakdown: EMPTY_BREAKDOWN,
    items: [],
    submission: { hasSubmission: false, submissionPending: false, createdAt: null, originalName: null },
    documentsUrl: '/documents',
    schoolYear: null,
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            isCleared: !!parsed.isCleared,
            printUrl: parsed.printUrl ?? null,
            cashierCleared: !!parsed.cashierCleared,
            registrarCleared: !!parsed.registrarCleared,
            chairCleared: !!parsed.chairCleared,
            remarks: parsed.remarks ?? null,
            breakdown: parsed.breakdown ?? EMPTY_BREAKDOWN,
            items: Array.isArray(parsed.items) ? parsed.items : [],
            submission: parsed.submission ?? EMPTY_CONTEXT.submission,
            documentsUrl: parsed.documentsUrl ?? EMPTY_CONTEXT.documentsUrl,
            schoolYear: parsed.schoolYear ?? null,
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function ClearanceApp({ context }) {
    return (
        <>
            <div className="space-y-6">
                <div className="text-center py-2">
                    <h1 className="font-heading text-2xl font-semibold text-brandNavy dark:text-white">Enrollment Clearance</h1>
                    {context.isCleared && context.printUrl && (
                        <div className="flex justify-center mt-3">
                            <a href={context.printUrl} target="_blank" rel="noreferrer"
                               className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors">
                                <i className="fa-solid fa-print" />
                                <span>Print clearance certificate</span>
                            </a>
                        </div>
                    )}
                </div>

                <MasterStatusBadge
                    isCleared={context.isCleared}
                    cashierCleared={context.cashierCleared}
                    registrarCleared={context.registrarCleared}
                    chairCleared={context.chairCleared}
                    items={context.items}
                />

                {context.remarks && (
                    <div className="p-4 rounded bg-red-600/10 border border-red-600/20 text-red-600 text-sm">
                        <i className="fa-solid fa-triangle-exclamation mr-2" /><strong>Remarks:</strong> {context.remarks}
                    </div>
                )}

                <div className="space-y-6">
                    <div className="flex items-center justify-between border-b border-brandNavy/8 dark:border-slate-800 pb-2">
                        <p className="text-sm font-medium text-brandNavy dark:text-slate-300">Clearance details</p>
                        <p className="text-xs text-brandNavy/50 dark:text-slate-400">Updates reflect immediately upon administrative action.</p>
                    </div>

                    <AccountingCard cashierCleared={context.cashierCleared} breakdown={context.breakdown} schoolYear={context.schoolYear} />
                    <RegistrarCard registrarCleared={context.registrarCleared} submission={context.submission} documentsUrl={context.documentsUrl} />
                </div>
            </div>
        </>
    );
}

const el = document.getElementById('clearance-root');
if (el) {
    const context = parseContext(el.dataset.context);
    createRoot(el).render(
        <ErrorBoundary>
            <ClearanceApp context={context} />
        </ErrorBoundary>
    );
}
