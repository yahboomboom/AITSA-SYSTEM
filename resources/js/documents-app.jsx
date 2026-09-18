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
                    <h1 className="font-heading text-2xl font-semibold text-brandNavy dark:text-white">Documents</h1>
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                        Submit each requirement below. The Registrar's Office reviews submissions within 1–3 business days.
                    </p>
                </div>

                <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
                    <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-1">Required documents</h3>
                    <div className="ui-doc-list border-brandNavy/8 dark:border-slate-800">
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
