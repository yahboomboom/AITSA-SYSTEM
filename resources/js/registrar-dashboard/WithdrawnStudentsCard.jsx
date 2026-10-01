import { useRef, useState } from 'react';

function ReinstateModal({ student, csrfToken, onClose }) {
    const [submitting, setSubmitting] = useState(false);
    const formRef = useRef(null);

    return (
        <div className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget && !submitting) onClose(); }}>
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-sm max-h-[90vh] overflow-y-auto">
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Reinstate student?</span>
                    <button type="button" onClick={onClose} disabled={submitting} aria-label="Close" className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white disabled:opacity-40">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>
                <div className="p-6 space-y-4">
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400">
                        <strong className="text-brandNavy dark:text-white">{student.name}</strong> gets their slot back and can log in again, using their original reservation. This only works if the program still has a free slot.
                    </p>
                    <form ref={formRef} action={student.reinstateUrl} method="POST" className="hidden">
                        <input type="hidden" name="_token" value={csrfToken} />
                    </form>
                    <div className="flex gap-2">
                        <button type="button" onClick={onClose} disabled={submitting}
                            className="ui-btn-primary flex-1 justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors disabled:opacity-40">
                            Cancel
                        </button>
                        <button type="button" disabled={submitting} onClick={() => { setSubmitting(true); formRef.current.submit(); }}
                            className="ui-btn-primary flex-1 justify-center text-white bg-brandGreen hover:bg-brandGreen/90 transition-colors disabled:opacity-40">
                            {submitting ? 'Reinstating…' : 'Reinstate'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function WithdrawnStudentsCard({ rows, csrfToken }) {
    const [target, setTarget] = useState(null);

    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                <i className="fa-solid fa-user-xmark mr-2 text-slate-400" />Withdrawn Admissions
            </h2>
            {rows.length === 0 ? (
                <p className="text-sm text-slate-400">No withdrawn admissions.</p>
            ) : (
                rows.map((row) => (
                    <div key={row.id} className="flex items-center justify-between flex-wrap gap-3 border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-3">
                        <div>
                            <p className="font-semibold text-brandNavy dark:text-slate-100">{row.name}</p>
                            <p className="text-xs text-slate-500">
                                {row.loginId} · {row.program} · {row.reasonLabel} on {row.withdrawnOnFormatted} by {row.byName}
                            </p>
                            {row.note && <p className="text-xs text-slate-400 mt-1">“{row.note}”</p>}
                        </div>
                        <button type="button" onClick={() => setTarget(row)}
                            className="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">
                            Reinstate
                        </button>
                    </div>
                ))
            )}
            {target && <ReinstateModal student={target} csrfToken={csrfToken} onClose={() => setTarget(null)} />}
        </div>
    );
}
