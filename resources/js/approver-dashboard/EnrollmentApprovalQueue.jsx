export default function EnrollmentApprovalQueue({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6 mt-8">
            <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-slate-100 mb-4">
                <i className="fa-solid fa-user-graduate mr-2 text-brandGreen" />Pending irregular enrollments
            </h2>

            {rows.length === 0 ? (
                <p className="text-sm text-brandNavy/50 dark:text-slate-400">No enrollments awaiting approval.</p>
            ) : (
                rows.map((row) => (
                    <div key={row.id} className="border border-brandNavy/10 dark:border-slate-700 rounded p-4 mb-4">
                        <div className="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <p className="font-medium text-brandNavy dark:text-slate-100">{row.studentName} ({row.studentId})</p>
                                <p className="text-xs text-brandNavy/50 dark:text-slate-500">{row.major} — {row.yearLevel} — submitted {row.submittedAgo}</p>
                            </div>
                            <div className="flex gap-2">
                                <form method="POST" action={row.approveUrl}>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <button className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors">Approve</button>
                                </form>
                                <form method="POST" action={row.rejectUrl} className="flex gap-2">
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input
                                        name="remarks"
                                        required
                                        maxLength={500}
                                        placeholder="Reason for rejection"
                                        className="px-3 py-1.5 rounded border border-brandNavy/10 dark:border-slate-600 dark:bg-slate-800 text-sm outline-none focus:border-brandNavy dark:focus:border-slate-500"
                                    />
                                    <button className="ui-btn-primary bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors">Reject</button>
                                </form>
                            </div>
                        </div>
                        <div className="overflow-x-auto mt-3">
                            <table className="ui-table">
                                <thead>
                                    <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                                        <th className="border-brandNavy/8 dark:border-slate-800">Code</th>
                                        <th className="border-brandNavy/8 dark:border-slate-800">Title</th>
                                        <th className="border-brandNavy/8 dark:border-slate-800">Schedule</th>
                                        <th className="border-brandNavy/8 dark:border-slate-800">Room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {row.sections.map((section, i) => (
                                        <tr key={i}>
                                            <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy dark:text-slate-200">{section.code}</td>
                                            <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{section.title}</td>
                                            <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{section.scheduleLabel}</td>
                                            <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{section.room}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ))
            )}
        </div>
    );
}
