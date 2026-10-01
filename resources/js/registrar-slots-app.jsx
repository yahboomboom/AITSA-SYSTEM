import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import NewSemesterForm from './registrar-slots/NewSemesterForm';
import SlotsTable from './registrar-slots/SlotsTable';
import MaxUnitsForm from './registrar-slots/MaxUnitsForm';

const EMPTY_CONTEXT = { curricula: [], errors: {}, old: {} };

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            curricula: Array.isArray(parsed.curricula) ? parsed.curricula : [],
            errors: parsed.errors ?? {},
            old: parsed.old ?? {},
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

const el = document.getElementById('registrar-slots-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const startTermUrl = el.dataset.startTermUrl ?? '';
    const maxUnits = Number(el.dataset.maxUnits) || 26;
    const maxUnitsUrl = el.dataset.maxUnitsUrl ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <div className="space-y-6">
                <NewSemesterForm
                    old={context.old}
                    errors={context.errors}
                    csrfToken={csrfToken}
                    actionUrl={startTermUrl}
                />
                {maxUnitsUrl && <MaxUnitsForm maxUnits={maxUnits} actionUrl={maxUnitsUrl} csrfToken={csrfToken} />}
                <SlotsTable curricula={context.curricula} csrfToken={csrfToken} />
            </div>
        </ErrorBoundary>
    );
}
