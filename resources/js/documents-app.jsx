import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import RequirementRow from './documents/RequirementRow';
import SubmittedDocumentsList from './clearance/SubmittedDocumentsList';
import SubmitRequirementModal from './clearance/SubmitRequirementModal';

const EMPTY_CONTEXT = { requirements: [], submissions: [] };

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            requirements: Array.isArray(parsed.requirements) ? parsed.requirements : [],
            submissions: Array.isArray(parsed.submissions) ? parsed.submissions : [],
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function DocumentsApp({ context, csrfToken, submitUrl }) {
    const [modal, setModal] = useState(null); // { type, label } | null

    return (
        <>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-brandNavy dark:text-white">Documents</h1>
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                        Submit each requirement below. The Registrar's Office reviews submissions within 1–3 business days.
                    </p>
                </div>

                <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        Required Documents
                    </div>
                    <div className="divide-y divide-slate-100 dark:divide-slate-800">
                        {context.requirements.map((req) => (
                            <RequirementRow
                                key={req.type}
                                requirement={req}
                                onUpload={(type, label) => setModal({ type, label })}
                            />
                        ))}
                    </div>
                </div>

                <SubmittedDocumentsList submissions={context.submissions} />
            </div>

            <SubmitRequirementModal
                open={!!modal}
                documentType={modal?.type ?? ''}
                documentLabel={modal?.label ?? ''}
                onClose={() => setModal(null)}
                csrfToken={csrfToken}
                submitUrl={submitUrl}
            />
        </>
    );
}

const el = document.getElementById('documents-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const submitUrl = el.dataset.submitUrl ?? '';
    createRoot(el).render(
        <ErrorBoundary>
            <DocumentsApp context={context} csrfToken={csrfToken} submitUrl={submitUrl} />
        </ErrorBoundary>
    );
}
