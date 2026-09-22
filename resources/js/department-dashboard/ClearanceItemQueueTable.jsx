const STATUS_STYLES = {
    Approved: 'border-brandGreen text-brandGreen',
    Hold: 'border-red-500 text-red-600',
};

export default function ClearanceItemQueueTable({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Student No.</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Status</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 ? (
                            <tr>
                                <td colSpan={4} className="border-brandNavy/8 dark:border-slate-800 p-8 text-center text-brandNavy/40 dark:text-slate-500">No students in your queue.</td>
                            </tr>
                        ) : (
                            rows.map((row) => (
                                <tr key={row.id}>
                                    <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandNavy dark:text-white">{row.studentName}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/60 dark:text-slate-400">{row.studentId}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        {row.status === 'Approved' || row.status === 'Hold' ? (
                                            <span className={`ui-badge-outline ${STATUS_STYLES[row.status]}`}>
                                                {row.status === 'Hold' ? `Hold: ${row.remarks}` : row.status}
                                            </span>
                                        ) : (
                                            <span className="ui-badge-outline border-brandGold text-brandGold">Pending</span>
                                        )}
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-right">
                                        {row.status === 'Approved' ? (
                                            <span className="ui-badge-outline border-brandNavy/10 text-brandNavy/30 dark:border-slate-700 dark:text-slate-600">Approved</span>
                                        ) : (
                                            <div className="flex items-center justify-end gap-2">
                                                <form action={row.approveUrl} method="POST">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors">Approve</button>
                                                </form>
                                                <form action={row.holdUrl} method="POST" className="flex items-center gap-2">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <input
                                                        type="text"
                                                        name="remarks"
                                                        required
                                                        maxLength={500}
                                                        placeholder="Reason for hold"
                                                        className="w-40 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-xs text-brandNavy dark:text-slate-200 outline-none focus:border-brandNavy dark:focus:border-slate-500"
                                                    />
                                                    <button type="submit" className="ui-btn-primary bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors">Hold</button>
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
