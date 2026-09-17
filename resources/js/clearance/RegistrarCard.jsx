export default function RegistrarCard({ registrarCleared, submission, documentsUrl }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-4">Registrar</h3>

            {registrarCleared ? (
                <div className="flex items-center gap-3">
                    <span className="w-8 h-8 rounded-full border-2 border-brandGreen text-brandGreen flex items-center justify-center flex-shrink-0">
                        <svg viewBox="0 0 16 16" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M3 8.5 6.5 12 13 4" />
                        </svg>
                    </span>
                    <p className="text-sm text-brandNavy/70 dark:text-slate-400">No on-hold records with this department.</p>
                </div>
            ) : (
                <div className="space-y-4">
                    <div className="flex items-start gap-3 py-2.5 border-y border-brandNavy/8 dark:border-slate-800">
                        <span className="w-6 h-6 rounded-full border-2 border-red-500 text-red-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg viewBox="0 0 16 16" className="w-3 h-3" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M4 4l8 8M12 4l-8 8" />
                            </svg>
                        </span>
                        <div>
                            <p className="text-sm font-medium text-brandNavy dark:text-slate-200">Office of the University Registrar</p>
                            <p className="text-xs text-brandNavy/60 dark:text-slate-500">Pending original copy submission — Form 137 / permanent academic records</p>
                        </div>
                    </div>

                    {submission.submissionPending ? (
                        <div className="flex items-start gap-3">
                            <div>
                                <p className="text-sm font-medium text-brandNavy dark:text-slate-200">Documents submitted — awaiting registrar review</p>
                                <p className="text-xs text-brandNavy/60 dark:text-slate-500 mt-0.5">
                                    Submitted on {submission.createdAt}. The Registrar's Office will review your documents within 1–3 business days.
                                </p>
                                {submission.originalName && (
                                    <p className="text-xs text-brandNavy/50 dark:text-slate-500 mt-1">{submission.originalName}</p>
                                )}
                            </div>
                            <a href={documentsUrl} className="text-xs font-medium text-brandNavy dark:text-brandGold hover:underline flex-shrink-0">
                                Resubmit
                            </a>
                        </div>
                    ) : (
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <p className="text-sm font-medium text-brandNavy dark:text-slate-200">Resolve this hold</p>
                                <p className="text-xs text-brandNavy/60 dark:text-slate-500 mt-0.5">Upload a scanned copy of your Form 137 or equivalent document directly to the Registrar.</p>
                            </div>
                            <a href={documentsUrl} className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors flex-shrink-0 self-start">
                                Submit documents
                            </a>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
