export default function RegistrarCard({ registrarCleared, submission, documentsUrl }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                <span className="font-bold text-brandNavy dark:text-slate-300"><i className="fa-solid fa-ban mr-2" />On-Hold Record Status</span>
                <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Registrar</span>
            </div>
            <div id="registrarCardBody" className="p-6 space-y-4">
                {registrarCleared ? (
                    <div className="text-center space-y-2">
                        <h3 className="text-base font-bold text-brandGreen">Cleared</h3>
                        <p className="text-xs text-brandNavy/60 dark:text-slate-400">No on-hold records with this department.</p>
                    </div>
                ) : (
                    <div className="text-left max-w-xl mx-auto space-y-3">
                        <h3 className="text-base font-bold text-red-600 dark:text-red-500 text-center">Not Yet Cleared</h3>
                        <p className="text-xs text-brandNavy/70 dark:text-slate-400">Your account has an active administrative documentation hold:</p>

                        <div className="flex items-start space-x-3 p-3.5 rounded-xl bg-red-600/5 dark:bg-red-500/5 border border-red-600/10 dark:border-red-500/10">
                            <i className="fa-solid fa-circle-xmark text-red-500 mt-0.5 flex-shrink-0" />
                            <div>
                                <span className="text-xs font-bold text-brandNavy dark:text-slate-200 block">Office of the University Registrar</span>
                                <span className="text-[11px] text-brandNavy/60 dark:text-slate-500">Pending Original Copy Submission — Form 137 / Permanent Academic Records</span>
                            </div>
                        </div>

                        {submission.submissionPending ? (
                            <div id="submissionPendingBanner" className="flex items-start gap-3 p-4 rounded-xl bg-blue-600/5 border border-blue-600/15 dark:bg-blue-500/5 dark:border-blue-500/15">
                                <div className="w-8 h-8 rounded-full bg-blue-600/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i className="fa-solid fa-clock text-blue-600 dark:text-blue-400 text-xs" />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-xs font-bold text-brandNavy dark:text-slate-200">Documents Submitted — Awaiting Registrar Review</p>
                                    <p className="text-[11px] text-brandNavy/60 dark:text-slate-500 mt-0.5">
                                        Submitted on {submission.createdAt}.
                                        The Registrar's Office will review your documents within 1–3 business days.
                                    </p>
                                    {submission.originalName && (
                                        <div className="mt-2 inline-flex items-center gap-1.5 text-[10px] font-mono text-blue-600 dark:text-blue-400 bg-blue-600/5 px-2 py-1 rounded-lg">
                                            <i className="fa-solid fa-file-pdf" />{submission.originalName}
                                        </div>
                                    )}
                                </div>
                                <a href={documentsUrl} className="text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:underline flex-shrink-0 underline-offset-2">
                                    Resubmit
                                </a>
                            </div>
                        ) : (
                            <div className="flex items-center justify-between gap-4 p-4 rounded-xl bg-brandNavy/3 dark:bg-slate-800/40 border border-brandNavy/8 dark:border-slate-700/40">
                                <div>
                                    <p className="text-xs font-bold text-brandNavy dark:text-slate-200">Resolve this hold</p>
                                    <p className="text-[11px] text-brandNavy/60 dark:text-slate-500 mt-0.5">Upload a scanned copy of your Form 137 or equivalent document directly to the Registrar.</p>
                                </div>
                                <a
                                    href={documentsUrl}
                                    className="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2.5 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-bold rounded-xl transition-all shadow-sm hover:shadow-brandGreen/20 hover:-translate-y-0.5 active:translate-y-0 uppercase tracking-wider whitespace-nowrap"
                                >
                                    <i className="fa-solid fa-upload" />Submit Documents
                                </a>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
