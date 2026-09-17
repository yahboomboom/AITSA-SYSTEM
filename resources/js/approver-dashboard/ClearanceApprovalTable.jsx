export default function ClearanceApprovalTable({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student info</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Student ID</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Program</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Status</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.id}>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandNavy dark:text-white">{row.studentName}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/50 dark:text-slate-400">{row.studentId}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/70 dark:text-slate-300">{row.program}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                                    {row.state === 'locked' ? (
                                        <span className="ui-badge-outline border-brandGold text-brandGold">Awaiting registrar</span>
                                    ) : row.state === 'approved' ? (
                                        <span className="ui-badge-outline border-brandGreen text-brandGreen">Fully approved</span>
                                    ) : (
                                        <span className="ui-badge-outline border-blue-500 text-blue-600 dark:text-blue-400">Ready for chair</span>
                                    )}
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-right">
                                    {row.state === 'locked' ? (
                                        <span title="Registrar must sign off first" className="ui-badge-outline border-brandNavy/10 text-brandNavy/30 dark:border-slate-700 dark:text-slate-600">
                                            <i className="fa-solid fa-lock" />Locked
                                        </span>
                                    ) : row.state === 'approved' ? (
                                        <span className="ui-badge-outline border-brandNavy/10 text-brandNavy/30 dark:border-slate-700 dark:text-slate-500">
                                            <i className="fa-solid fa-check-double" />Approved
                                        </span>
                                    ) : (
                                        <div className="flex items-center justify-end gap-2">
                                            <form action={row.signUrl} method="POST" className="inline-block">
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <button type="submit" className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                                                    Approve
                                                </button>
                                            </form>
                                            <form action={row.holdUrl} method="POST" className="flex items-center gap-2">
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <input
                                                    type="text"
                                                    name="remarks"
                                                    required
                                                    maxLength={500}
                                                    placeholder="Reason for hold"
                                                    className="w-36 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-xs text-brandNavy dark:text-slate-200 outline-none focus:border-brandNavy dark:focus:border-slate-500"
                                                />
                                                <button type="submit" className="ui-btn-primary bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors">
                                                    Hold
                                                </button>
                                            </form>
                                        </div>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
