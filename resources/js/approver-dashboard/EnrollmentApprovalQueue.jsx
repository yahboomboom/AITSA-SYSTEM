export default function EnrollmentApprovalQueue({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                <i className="fa-solid fa-user-graduate mr-2 text-brandGreen" />Pending Irregular Enrollments
            </h2>

            {rows.length === 0 ? (
                <p className="text-sm text-slate-400">No enrollments awaiting approval.</p>
            ) : (
                rows.map((row) => (
                    <div key={row.id} className="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-4">
                        <div className="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <p className="font-semibold text-brandNavy dark:text-slate-100">{row.studentName} ({row.studentId})</p>
                                <p className="text-xs text-slate-500">{row.major} — {row.yearLevel} — submitted {row.submittedAgo}</p>
                            </div>
                            <div className="flex gap-2">
                                <form method="POST" action={row.approveUrl}>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <button className="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">Approve</button>
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
                        <table className="w-full mt-3 text-sm">
                            <thead className="text-left text-xs uppercase text-slate-400">
                                <tr><th className="py-1">Code</th><th>Title</th><th>Schedule</th><th>Room</th></tr>
                            </thead>
                            <tbody>
                                {row.sections.map((section, i) => (
                                    <tr key={i} className="border-t border-slate-100 dark:border-slate-800">
                                        <td className="py-1 font-mono">{section.code}</td>
                                        <td>{section.title}</td>
                                        <td>{section.scheduleLabel}</td>
                                        <td>{section.room}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ))
            )}
        </div>
    );
}
