import { useState } from 'react';
import ApplicantQueueTable from './ApplicantQueueTable';
import ClearanceQueueTable from './ClearanceQueueTable';

const TAB_BASE = 'flex-1 sm:flex-none px-5 py-3 text-sm font-semibold border-b-2 transition-colors flex items-center justify-center gap-2';
const TAB_ACTIVE = 'border-brandGreen text-brandNavy dark:text-white';
const TAB_INACTIVE = 'border-transparent text-brandNavy/50 dark:text-slate-500 hover:text-brandNavy dark:hover:text-slate-300';

export default function AdmissionsPipelineCard({ applicants, clearances, csrfToken, documentsPageUrl }) {
    const [tab, setTab] = useState(applicants.length > 0 ? 'applicants' : 'clearance');

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-6 pt-4">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Admissions pipeline</h3>
                <p className="text-xs text-brandNavy/50 dark:text-slate-400 mt-0.5">From a new application to an enrolled student's clearance sign-off.</p>
            </div>
            <div className="px-6 mt-3 border-b border-brandNavy/10 dark:border-slate-800 flex">
                <button type="button" onClick={() => setTab('applicants')} className={`${TAB_BASE} ${tab === 'applicants' ? TAB_ACTIVE : TAB_INACTIVE}`}>
                    Step 1 &middot; New Applicants
                    {applicants.length > 0 && <span className="ui-badge-outline border-amber-500 text-amber-600">{applicants.length}</span>}
                </button>
                <button type="button" onClick={() => setTab('clearance')} className={`${TAB_BASE} ${tab === 'clearance' ? TAB_ACTIVE : TAB_INACTIVE}`}>
                    Step 2 &middot; Clearance Processing
                </button>
            </div>
            {tab === 'applicants' ? (
                <ApplicantQueueTable applicants={applicants} csrfToken={csrfToken} />
            ) : (
                <ClearanceQueueTable rows={clearances} csrfToken={csrfToken} documentsPageUrl={documentsPageUrl} />
            )}
        </div>
    );
}
