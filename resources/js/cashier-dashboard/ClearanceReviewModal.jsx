import { peso } from '../utils/format';

export default function ClearanceReviewModal({ open, student, onClose, csrfToken, approveUrl, holdUrl }) {
    if (!open || !student) return null;

    return (
        <div
            className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg w-full max-w-md overflow-hidden">
                <div className="p-5 border-b border-brandNavy/8 dark:border-slate-800 flex items-center justify-between">
                    <span className="text-sm font-bold text-brandNavy dark:text-white">Review Clearance</span>
                    <button type="button" onClick={onClose} className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>
                <div className="p-6 space-y-5">
                    <div className="space-y-1">
                        <p className="text-[10px] font-bold uppercase tracking-widest text-brandNavy/40 dark:text-slate-500">Evaluating</p>
                        <h3 className="text-base font-bold text-brandNavy dark:text-white">{student.studentName}</h3>
                    </div>

                    <div className="bg-lightBg dark:bg-slate-800/60 rounded-lg p-4 border border-brandNavy/8 dark:border-slate-700 text-xs">
                        <div className="flex justify-between items-center font-bold">
                            <span className="text-brandNavy/50 dark:text-slate-400">Balance:</span>
                            <span className="text-brandGold text-base">{peso(student.balance)}</span>
                        </div>
                    </div>

                    <form action={approveUrl} method="POST" className="grid grid-cols-1 gap-2">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="user_id" value={student.userId} />
                        <input type="hidden" name="amount" value={peso(student.balance)} />
                        <input
                            type="text"
                            name="reference_no"
                            required
                            maxLength={100}
                            placeholder="OR / Receipt number"
                            className="w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 text-xs text-brandNavy dark:text-slate-200 outline-none"
                        />
                        <button type="submit" className="w-full py-3 bg-brandGreen hover:bg-emerald-600 text-white font-bold rounded text-xs uppercase tracking-wider transition-colors">
                            <i className="fa-solid fa-circle-check mr-2" />Approve &amp; Sign Off
                        </button>
                    </form>

                    <form action={holdUrl} method="POST" className="grid grid-cols-1 gap-2 mt-2">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="user_id" value={student.userId} />
                        <input
                            type="text"
                            name="remarks"
                            required
                            maxLength={500}
                            placeholder="Reason for hold"
                            className="w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 text-xs text-brandNavy dark:text-slate-200 outline-none"
                        />
                        <button type="submit" className="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded text-xs uppercase tracking-wider transition-colors">
                            <i className="fa-solid fa-circle-pause mr-2" />Hold with Remarks
                        </button>
                    </form>

                    <button type="button" onClick={onClose} className="w-full py-3 bg-lightBg dark:bg-slate-800 hover:bg-brandNavy/5 text-brandNavy/60 dark:text-slate-400 text-xs font-bold rounded transition-colors mt-2">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    );
}
