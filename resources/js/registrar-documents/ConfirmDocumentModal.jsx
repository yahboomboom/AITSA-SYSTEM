import { useRef, useState } from 'react';

// One modal handles both Accept and Reject so there is a single place that
// owns the "are you sure" step for a pending document — accidental clicks on
// the row buttons only ever open this card, never submit anything directly.
export default function ConfirmDocumentModal({ action, csrfToken, onClose }) {
    const [remarks, setRemarks] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const formRef = useRef(null);

    if (!action) {
        return null;
    }

    const isReject = action.type === 'reject';
    const canSubmit = !isReject || remarks.trim().length > 0;

    const submit = () => {
        setSubmitting(true);
        formRef.current.submit();
    };

    return (
        <div
            className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget && !submitting) onClose(); }}
        >
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-sm overflow-hidden">
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                        {isReject ? 'Reject document?' : 'Accept document?'}
                    </span>
                    <button type="button" onClick={onClose} disabled={submitting} aria-label="Close" className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white disabled:opacity-40">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>
                <div className="p-6 space-y-4">
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400">
                        {isReject ? (
                            <>You're rejecting <strong className="text-brandNavy dark:text-white">{action.studentName}</strong>'s <strong className="text-brandNavy dark:text-white">{action.doc.typeLabel}</strong>. The student will see the reason below and will need to resubmit.</>
                        ) : (
                            <>You're accepting <strong className="text-brandNavy dark:text-white">{action.studentName}</strong>'s <strong className="text-brandNavy dark:text-white">{action.doc.typeLabel}</strong>. This cannot be undone.</>
                        )}
                    </p>
                    {isReject && (
                        <textarea
                            autoFocus
                            value={remarks}
                            onChange={(e) => setRemarks(e.target.value)}
                            maxLength={500}
                            rows={3}
                            placeholder="Reason for rejection (shown to the student)"
                            className="w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-red-400 transition-colors resize-none"
                        />
                    )}
                    <form ref={formRef} action={isReject ? action.doc.rejectUrl : action.doc.acceptUrl} method="POST" className="hidden">
                        <input type="hidden" name="_token" value={csrfToken} />
                        {isReject && <input type="hidden" name="remarks" value={remarks} />}
                    </form>
                    <div className="flex gap-2">
                        <button
                            type="button" onClick={onClose} disabled={submitting}
                            className="ui-btn-primary flex-1 justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors disabled:opacity-40"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            disabled={!canSubmit || submitting}
                            onClick={submit}
                            className={`ui-btn-primary flex-1 justify-center text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed ${isReject ? 'bg-red-500 hover:bg-red-600' : 'bg-brandGreen hover:bg-brandGreen/90'}`}
                        >
                            {submitting ? (isReject ? 'Rejecting…' : 'Accepting…') : (isReject ? 'Reject' : 'Accept')}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
