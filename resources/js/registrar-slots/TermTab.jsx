import { useRef, useState } from 'react';
import MaxUnitsForm from './MaxUnitsForm';

const semLabel = (s) => (Number(s) === 1 ? '1st Semester' : '2nd Semester');

function StartTermModal({ term, actionUrl, csrfToken, onClose }) {
    const [schoolYear, setSchoolYear] = useState(term.next.schoolYear);
    const [semester, setSemester] = useState(String(term.next.semester));
    const [custom, setCustom] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const formRef = useRef(null);
    const newYear = schoolYear !== term.current.schoolYear;

    return (
        <div className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget && !submitting) onClose(); }}>
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-sm max-h-[90vh] overflow-y-auto">
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Start {semLabel(semester)}, {schoolYear}?</span>
                    <button type="button" onClick={onClose} disabled={submitting} aria-label="Close" className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>
                <div className="p-5 space-y-4 text-sm text-brandNavy/70 dark:text-slate-300">
                    <ul className="space-y-1.5">
                        <li><i className="fa-solid fa-rotate text-brandGreen w-5" />New clearance for every College student</li>
                        {newYear && <li><i className="fa-solid fa-arrow-up text-brandGreen w-5" />Fully enrolled students move up a year</li>}
                        <li><i className="fa-solid fa-triangle-exclamation text-brandGold w-5" />Can't be undone</li>
                    </ul>

                    {custom ? (
                        <div className="flex gap-2">
                            <input value={schoolYear} onChange={(e) => setSchoolYear(e.target.value)} placeholder="2027-2028"
                                className="w-28 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded px-2 py-1.5 text-sm" />
                            <select value={semester} onChange={(e) => setSemester(e.target.value)}
                                className="bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded px-2 py-1.5 text-sm">
                                <option value="1">1st Semester</option>
                                <option value="2">2nd Semester</option>
                            </select>
                        </div>
                    ) : (
                        <button type="button" onClick={() => setCustom(true)} className="text-xs text-brandNavy/50 dark:text-slate-400 underline underline-offset-2">
                            Different term?
                        </button>
                    )}

                    <form ref={formRef} action={actionUrl} method="POST" className="hidden">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="school_year" value={schoolYear} />
                        <input type="hidden" name="semester" value={semester} />
                    </form>
                    <div className="flex gap-2">
                        <button type="button" onClick={onClose} disabled={submitting}
                            className="ui-btn-primary flex-1 justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800">
                            Cancel
                        </button>
                        <button type="button" disabled={submitting || !schoolYear} onClick={() => { setSubmitting(true); formRef.current.submit(); }}
                            className="ui-btn-primary flex-1 justify-center bg-brandGreen hover:bg-[#247039] text-white disabled:opacity-50">
                            {submitting ? 'Starting…' : 'Start'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function TermTab({ term, startTermUrl, maxUnits, maxUnitsUrl, csrfToken, error }) {
    const [confirming, setConfirming] = useState(false);

    return (
        <div className="space-y-6">
            <div className="rounded-lg border border-brandNavy/10 dark:border-slate-700 bg-white dark:bg-panelDark shadow-sm p-5">
                <p className="text-xs text-brandNavy/50 dark:text-slate-400">Current term</p>
                <p className="text-xl font-bold text-brandNavy dark:text-white mt-0.5">{term.current.schoolYear} · {semLabel(term.current.semester)}</p>
                <button type="button" onClick={() => setConfirming(true)}
                    className="ui-btn-primary mt-4 bg-brandGreen hover:bg-[#247039] text-white">
                    Start {semLabel(term.next.semester)} ({term.next.schoolYear}) <i className="fa-solid fa-arrow-right" />
                </button>
                {error && <p className="text-sm text-red-600 font-medium mt-3">{error}</p>}
            </div>

            {maxUnitsUrl && <MaxUnitsForm maxUnits={maxUnits} actionUrl={maxUnitsUrl} csrfToken={csrfToken} />}

            {confirming && (
                <StartTermModal term={term} actionUrl={startTermUrl} csrfToken={csrfToken} onClose={() => setConfirming(false)} />
            )}
        </div>
    );
}
