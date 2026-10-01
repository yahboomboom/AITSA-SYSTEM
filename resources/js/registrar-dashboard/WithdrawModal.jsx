import { useRef, useState } from 'react';

const REASONS = [
    { value: 'no_show', label: 'No-show (no response)' },
    { value: 'withdrew', label: 'Withdrew (informed us)' },
];

export default function WithdrawModal({ students, actionUrl, csrfToken, onClose }) {
    const [reason, setReason] = useState('no_show');
    const [note, setNote] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const formRef = useRef(null);

    const submit = () => {
        setSubmitting(true);
        formRef.current.submit();
    };

    const who = students.length === 1
        ? <strong className="text-brandNavy dark:text-white">{students[0].name}</strong>
        : <strong className="text-brandNavy dark:text-white">{students.length} students</strong>;

    return (
        <div
            className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget && !submitting) onClose(); }}
        >
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-sm max-h-[90vh] overflow-y-auto">
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Mark as withdrawn?</span>
                    <button type="button" onClick={onClose} disabled={submitting} aria-label="Close" className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white disabled:opacity-40">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>
                <div className="p-6 space-y-4">
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400">
                        You're withdrawing {who}. Their program slot is freed, they can no longer log in, and the reservation fee is forfeited. You can reinstate them later if a slot is free.
                    </p>
                    <div className="space-y-2">
                        {REASONS.map((r) => (
                            <label key={r.value} className="flex items-center gap-2 text-sm text-brandNavy dark:text-slate-200">
                                <input type="radio" name="reason-choice" value={r.value} checked={reason === r.value} onChange={() => setReason(r.value)} />
                                {r.label}
                            </label>
                        ))}
                    </div>
                    <textarea
                        value={note}
                        onChange={(e) => setNote(e.target.value)}
                        maxLength={500}
                        rows={3}
                        placeholder="Note (optional) — e.g. called twice, no answer"
                        className="w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-red-400 transition-colors resize-none"
                    />
                    <form ref={formRef} action={actionUrl} method="POST" className="hidden">
                        <input type="hidden" name="_token" value={csrfToken} />
                        {students.map((s) => <input key={s.id} type="hidden" name="ids[]" value={s.id} />)}
                        <input type="hidden" name="reason" value={reason} />
                        <input type="hidden" name="note" value={note} />
                    </form>
                    <div className="flex gap-2">
                        <button type="button" onClick={onClose} disabled={submitting}
                            className="ui-btn-primary flex-1 justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors disabled:opacity-40">
                            Cancel
                        </button>
                        <button type="button" onClick={submit} disabled={submitting}
                            className="ui-btn-primary flex-1 justify-center text-white bg-red-500 hover:bg-red-600 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            {submitting ? 'Withdrawing…' : 'Withdraw'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
