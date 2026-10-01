import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import SlotsTab from './registrar-slots/SlotsTab';
import TermTab from './registrar-slots/TermTab';

const EMPTY_CONTEXT = { curricula: [], errors: {}, term: null };
const TAB_KEY = 'registrar-slots-tab';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            curricula: Array.isArray(parsed.curricula) ? parsed.curricula : [],
            errors: parsed.errors ?? {},
            term: parsed.term ?? null,
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function readTab() {
    try { return localStorage.getItem(TAB_KEY); } catch { return null; }
}

function SlotsPage({ context, csrfToken, startTermUrl, maxUnits, maxUnitsUrl, schoolYear }) {
    // A failed term start lands back here with an error: show that tab.
    const [tab, setTab] = useState(context.errors.semester ? 'term' : (readTab() ?? 'slots'));
    const choose = (next) => {
        setTab(next);
        try { localStorage.setItem(TAB_KEY, next); } catch { /* storage unavailable */ }
    };
    const tabClass = (name) => `px-4 py-2 rounded-md text-sm font-semibold transition-colors ${tab === name
        ? 'bg-white dark:bg-panelDark text-brandNavy dark:text-white shadow-sm border border-brandNavy/10 dark:border-slate-700'
        : 'text-brandNavy/60 dark:text-slate-400 hover:text-brandNavy dark:hover:text-white'}`;

    return (
        <div className="space-y-5">
            <div className="flex gap-2" role="tablist">
                <button type="button" role="tab" aria-selected={tab === 'slots'} onClick={() => choose('slots')} className={tabClass('slots')}>
                    <i className="fa-solid fa-chair mr-2" />Slots
                </button>
                <button type="button" role="tab" aria-selected={tab === 'term'} onClick={() => choose('term')} className={tabClass('term')}>
                    <i className="fa-solid fa-calendar-days mr-2" />Term &amp; rules
                </button>
            </div>
            {tab === 'slots' ? (
                <SlotsTab curricula={context.curricula} schoolYear={schoolYear} csrfToken={csrfToken} />
            ) : context.term ? (
                <TermTab term={context.term} startTermUrl={startTermUrl} maxUnits={maxUnits} maxUnitsUrl={maxUnitsUrl}
                    csrfToken={csrfToken} error={context.errors.semester} />
            ) : null}
        </div>
    );
}

const el = document.getElementById('registrar-slots-root');
if (el) {
    const context = parseContext(el.dataset.context);

    createRoot(el).render(
        <ErrorBoundary>
            <SlotsPage
                context={context}
                csrfToken={el.dataset.csrfToken ?? ''}
                startTermUrl={el.dataset.startTermUrl ?? ''}
                maxUnits={Number(el.dataset.maxUnits) || 26}
                maxUnitsUrl={el.dataset.maxUnitsUrl ?? ''}
                schoolYear={context.term?.current?.schoolYear ?? ''}
            />
        </ErrorBoundary>
    );
}
