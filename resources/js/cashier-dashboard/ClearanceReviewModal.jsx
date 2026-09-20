import { peso } from '../utils/format';

// Locks every button in the modal (both forms + Cancel) the moment either
// action is submitted, so a slow request can't be double-clicked into two
// ledger rows / audit entries for the same payment.
// NOTE: relies on the .space-y-5 class below to find the modal wrapper —
// keep that class on this exact div if the layout changes.
function lockReviewModal(form, busyLabel) {
    const modal = form.closest('.space-y-5');
    modal?.querySelectorAll('button').forEach((btn) => { btn.disabled = true; });
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.replaceChildren();
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-spinner fa-spin mr-2';
        submitBtn.append(icon, document.createTextNode(busyLabel));
    }
}

export default function ClearanceReviewModal({ open, student, onClose, csrfToken, approveUrl, holdUrl, waiveDownPaymentUrl }) {
    if (!open || !student) return null;

    return (
        <div
            className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-md overflow-hidden">
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Review clearance</span>
                    <button type="button" onClick={onClose} className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>
                <div className="p-6 space-y-5">
                    <div className="space-y-1">
                        <p className="text-xs text-brandNavy/40 dark:text-slate-500">Evaluating</p>
                        <h3 className="font-heading text-base font-semibold text-brandNavy dark:text-white">{student.studentName}</h3>
                    </div>

                    <div className="bg-lightBg dark:bg-slate-800/60 rounded p-4 border border-brandNavy/8 dark:border-slate-700 text-sm">
                        <div className="flex justify-between items-center">
                            <span className="text-brandNavy/50 dark:text-slate-400">Balance:</span>
                            <span className="text-brandGold font-medium text-base">{peso(student.balance)}</span>
                        </div>
                    </div>

                    <form
                        action={approveUrl}
                        method="POST"
                        className="grid grid-cols-1 gap-2"
                        onSubmit={(e) => lockReviewModal(e.currentTarget, 'Processing…')}
                    >
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="user_id" value={student.userId} />
                        <input type="hidden" name="amount" value={peso(student.balance)} />
                        <input
                            type="text"
                            name="reference_no"
                            required
                            maxLength={100}
                            placeholder="OR / Receipt number"
                            className="w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 text-sm text-brandNavy dark:text-slate-200 outline-none focus:border-brandNavy dark:focus:border-slate-500"
                        />
                        <button type="submit" className="ui-btn-primary w-full justify-center bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50">
                            <i className="fa-solid fa-circle-check" />Approve and sign off
                        </button>
                    </form>

                    <form
                        action={holdUrl}
                        method="POST"
                        className="grid grid-cols-1 gap-2 mt-2"
                        onSubmit={(e) => lockReviewModal(e.currentTarget, 'Processing…')}
                    >
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="user_id" value={student.userId} />
                        <input
                            type="text"
                            name="remarks"
                            required
                            maxLength={500}
                            placeholder="Reason for hold"
                            className="w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 text-sm text-brandNavy dark:text-slate-200 outline-none focus:border-brandNavy dark:focus:border-slate-500"
                        />
                        <button type="submit" className="ui-btn-primary w-full justify-center bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors disabled:opacity-50">
                            <i className="fa-solid fa-circle-pause" />Hold with remarks
                        </button>
                    </form>

                    {!student.isDownPaymentMet && !student.isDownPaymentWaived && !student.isHeld && (
                        <form
                            action={waiveDownPaymentUrl}
                            method="POST"
                            className="grid grid-cols-1 gap-2 mt-2"
                            onSubmit={(e) => lockReviewModal(e.currentTarget, 'Processing…')}
                        >
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="user_id" value={student.userId} />
                            <input
                                type="text"
                                name="reason"
                                required
                                maxLength={1000}
                                placeholder="Reason (e.g. financial hardship)"
                                className="w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 text-sm text-brandNavy dark:text-slate-200 outline-none focus:border-brandNavy dark:focus:border-slate-500"
                            />
                            <button type="submit" className="ui-btn-primary w-full justify-center bg-cyan-600 hover:bg-cyan-700 text-white transition-colors disabled:opacity-50">
                                <i className="fa-solid fa-hand-holding-heart" />Waive down payment
                            </button>
                        </form>
                    )}

                    <button type="button" onClick={onClose} className="ui-btn-primary w-full justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors mt-2">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    );
}
