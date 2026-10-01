import { useState } from 'react';

// Registrar-only correction of a College student's year level. The server
// enforces every rule (program length, mid-enrollment block, required
// reason) and answers with a message this modal shows as-is.
export default function EditYearLevelModal({ student, csrfToken, onClose, onSaved }) {
    const [yearLevel, setYearLevel] = useState(student.yearLevel ?? student.yearEdit.options[0]);
    const [reason, setReason] = useState('');
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const canSubmit = reason.trim() !== '' && yearLevel !== student.yearLevel;

    const submit = () => {
        setSubmitting(true);
        setError('');
        fetch(student.yearEdit.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ year_level: yearLevel, reason: reason.trim() }),
        })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message ?? 'Could not change the year level. Please try again.');
                onSaved(data.yearLevel, data.message);
            })
            .catch((e) => {
                setError(e.message);
                setSubmitting(false);
            });
    };

    return (
        <div
            className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget && !submitting) onClose(); }}
        >
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-sm max-h-[90vh] overflow-y-auto text-left">
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Change year level</span>
                    <button type="button" onClick={onClose} disabled={submitting} aria-label="Close" className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white disabled:opacity-40">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>
                <div className="p-6 space-y-4">
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400">
                        <strong className="text-brandNavy dark:text-white">{student.name}</strong> ({student.major}) is currently{' '}
                        <strong className="text-brandNavy dark:text-white">{student.yearLevel ?? 'not set'}</strong>.
                        This changes which subjects they're offered, and is recorded in the audit trail.
                    </p>
                    <label className="block">
                        <span className="text-xs font-semibold text-brandNavy/80 dark:text-slate-300">New year level</span>
                        <select
                            value={yearLevel}
                            onChange={(e) => setYearLevel(e.target.value)}
                            className="mt-1 w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 text-sm text-brandNavy dark:text-slate-200 px-3 py-2 rounded outline-none focus:border-brandGreen/40"
                        >
                            {student.yearEdit.options.map((option) => <option key={option} value={option}>{option}</option>)}
                        </select>
                    </label>
                    <label className="block">
                        <span className="text-xs font-semibold text-brandNavy/80 dark:text-slate-300">Reason (required)</span>
                        <textarea
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            maxLength={255}
                            rows={3}
                            placeholder="e.g. Paper records migration, credited transferee units"
                            className="mt-1 w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-brandGreen/40 resize-none"
                        />
                    </label>
                    {error && (
                        <p className="text-sm text-red-600 bg-red-500/10 border border-red-500/20 rounded px-3 py-2">{error}</p>
                    )}
                    <div className="flex gap-2">
                        <button type="button" onClick={onClose} disabled={submitting}
                            className="ui-btn-primary flex-1 justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors disabled:opacity-40">
                            Cancel
                        </button>
                        <button type="button" onClick={submit} disabled={!canSubmit || submitting}
                            className="ui-btn-primary flex-1 justify-center text-white bg-brandGreen hover:bg-brandGreen/90 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            {submitting ? 'Saving…' : 'Save'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
