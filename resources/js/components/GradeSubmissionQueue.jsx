export default function GradeSubmissionQueue({ rows, csrfToken, title = 'Grade Submissions Awaiting Approval', approveLabel = 'Approve' }) {
    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                <i className="fa-solid fa-clipboard-check mr-2 text-brandGreen" />{title}
            </h2>

            {rows.length === 0 ? (
                <p className="text-sm text-slate-400">No grade submissions waiting.</p>
            ) : (
                rows.map((row) => (
                    <div key={row.id} className="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-4 flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <p className="font-semibold text-brandNavy dark:text-slate-100">{row.subjectCode} — Block {row.blockLabel}</p>
                            <p className="text-xs text-slate-500">{row.subjectTitle} — {row.facultyName} — {row.studentCount} student(s)</p>
                        </div>
                        <div className="flex gap-2">
                            <form method="POST" action={row.approveUrl}>
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button className="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">{approveLabel}</button>
                            </form>
                            <form method="POST" action={row.rejectUrl} className="flex gap-2">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <input
                                    name="remarks"
                                    required
                                    maxLength={500}
                                    placeholder="Reason for rejection"
                                    className="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm"
                                />
                                <button className="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:opacity-90">Reject</button>
                            </form>
                        </div>
                    </div>
                ))
            )}
        </div>
    );
}
