export default function ClearanceQueueTable({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Admission &amp; clearance processing queue</h3>
                <div className="relative w-full sm:w-72">
                    <i className="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-brandNavy/40 dark:text-slate-500 text-xs" />
                    <input
                        type="text"
                        placeholder="Search student name or ID…"
                        className="w-full bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 pl-9 pr-4 py-2 rounded focus:outline-none focus:border-brandGreen dark:focus:border-emerald-500/50 transition-colors"
                    />
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-400">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student info</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Student ID</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Program / track</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Admission status</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="border-brandNavy/8 dark:border-slate-800 py-12 text-center text-brandNavy/40 dark:text-slate-500">
                                    <div className="flex flex-col items-center justify-center space-y-2">
                                        <i className="fa-solid fa-box-open text-2xl text-brandNavy/20 dark:text-slate-600" />
                                        <span>No students pending admission review at this time.</span>
                                    </div>
                                </td>
                            </tr>
                        ) : (
                            rows.map((row) => (
                                <tr key={row.id}>
                                    <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandNavy dark:text-white">{row.studentName}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/60 dark:text-slate-400">{row.studentId}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/70 dark:text-slate-300">{row.program}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                                        {row.isApproved ? (
                                            <span className="ui-badge-outline border-brandGreen text-brandGreen">
                                                <i className="fa-solid fa-circle-check" />Cleared
                                            </span>
                                        ) : (
                                            <span className="ui-badge-outline border-brandGold text-brandGold">
                                                <i className="fa-solid fa-clock" />Pending review
                                            </span>
                                        )}
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-right">
                                        {row.isApproved ? (
                                            <span className="ui-badge-outline border-brandNavy/10 text-brandNavy/40 dark:border-slate-700 dark:text-slate-500">
                                                <i className="fa-solid fa-check-double" />Signed off
                                            </span>
                                        ) : (
                                            <div className="flex items-center justify-end gap-2">
                                                <form action={row.signUrl} method="POST" className="inline-block">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <button type="submit" className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                                                        Sign clearance
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
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
