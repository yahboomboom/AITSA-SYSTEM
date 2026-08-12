import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import MasterStatusBadge from './clearance/MasterStatusBadge';
import AccountingCard from './clearance/AccountingCard';
import RegistrarCard from './clearance/RegistrarCard';
import SubmittedDocumentsList from './clearance/SubmittedDocumentsList';
import SubmitRequirementModal from './clearance/SubmitRequirementModal';

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
    submissions: [],
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
            submissions: Array.isArray(parsed.submissions) ? parsed.submissions : [],
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function ClearanceApp({ context, csrfToken, submitUrl }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [modalIsResubmit, setModalIsResubmit] = useState(false);

    const openModal = (isResubmit) => {
        setModalIsResubmit(isResubmit);
        setModalOpen(true);
    };
    const closeModal = () => setModalOpen(false);

    return (
        <>
            <div className="space-y-6">
                <div className="text-center py-2 relative">
                    <h1 className="text-2xl font-bold tracking-tight text-brandNavy dark:text-white">Enrollment Clearance</h1>
{context.isCleared && context.printUrl && (
                        <div className="flex justify-center mt-3">
                            <a href={context.printUrl} target="_blank" rel="noreferrer"
                               className="inline-flex items-center gap-2 text-xs font-black bg-brandGreen text-white px-5 py-2.5 rounded-xl shadow-lg hover:bg-emerald-600 transition-all">
                                <i className="fa-solid fa-print" />
                                <span>PRINT CLEARANCE CERTIFICATE</span>
                            </a>
                        </div>
                    )}
                    <div className="hidden justify-center mt-3 animate-bounce">
                        <a href="/enrollment" className="inline-flex items-center space-x-2 text-xs font-black bg-brandGreen text-white px-5 py-2.5 rounded-xl shadow-lg hover:bg-emerald-600 transition-all">
                            <i className="fa-solid fa-rocket" />
                            <span>CONGRATULATIONS! CLICK HERE TO PROCEED TO ENROLLMENT</span>
                        </a>
                    </div>
                </div>

                <MasterStatusBadge
                    isCleared={context.isCleared}
                    cashierCleared={context.cashierCleared}
                    registrarCleared={context.registrarCleared}
                    chairCleared={context.chairCleared}
                    items={context.items}
                />

                {context.remarks && (
                    <div className="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 text-xs">
                        <i className="fa-solid fa-triangle-exclamation mr-2" /><strong>Remarks:</strong> {context.remarks}
                    </div>
                )}

                <div className="space-y-6">
                    <div className="flex items-center justify-between border-b border-brandNavy/5 dark:border-slate-800 pb-2">
                        <p className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">Clearance Details</p>
                        <p className="text-[10px] text-brandNavy/50 dark:text-slate-400 italic">Updates reflect immediately upon administrative action.</p>
                    </div>

                    <AccountingCard cashierCleared={context.cashierCleared} breakdown={context.breakdown} />
                    <RegistrarCard registrarCleared={context.registrarCleared} submission={context.submission} onOpenModal={openModal} />
                </div>

                <SubmittedDocumentsList submissions={context.submissions} />
            </div>

            <SubmitRequirementModal
                open={modalOpen}
                isResubmit={modalIsResubmit}
                onClose={closeModal}
                csrfToken={csrfToken}
                submitUrl={submitUrl}
            />
        </>
    );
}

const el = document.getElementById('clearance-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const submitUrl = el.dataset.submitUrl ?? '';
    createRoot(el).render(
        <ErrorBoundary>
            <ClearanceApp context={context} csrfToken={csrfToken} submitUrl={submitUrl} />
        </ErrorBoundary>
    );
}
