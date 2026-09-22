export default function ClearanceItemQueueTable({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                    <thead>
                        <tr className="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                            <th className="p-4">Student</th>
                            <th className="p-4">Student No.</th>
                            <th className="p-4">Status</th>
                            <th className="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                        {rows.length === 0 ? (
                            <tr>
                                <td colSpan={4} className="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">No students in your queue.</td>
                            </tr>
                        ) : (
                            rows.map((row) => (
                                <tr key={row.id}>
                                    <td className="p-4 font-bold text-brandNavy dark:text-white">{row.studentName}</td>
                                    <td className="p-4 font-mono text-brandNavy/60 dark:text-slate-400">{row.studentId}</td>
                                    <td className="p-4">
                                        {row.status === 'Approved' ? (
                                            <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGreen/10 text-brandGreen border border-brandGreen/20 rounded">Approved</span>
                                        ) : row.status === 'Hold' ? (
                                            <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-red-500/10 text-red-600 border border-red-500/20 rounded">Hold: {row.remarks}</span>
                                        ) : (
                                            <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGold/10 text-brandGold border border-brandGold/20 rounded">Pending</span>
                                        )}
                                    </td>
                                    <td className="p-4 text-right">
                                        {row.status === 'Approved' ? (
                                            <button disabled className="px-3 py-1.5 bg-lightBg dark:bg-slate-800 text-brandNavy/30 dark:text-slate-600 rounded text-[11px] font-bold border border-brandNavy/8 dark:border-slate-700 cursor-not-allowed">Approved</button>
                                        ) : (
                                            <div className="flex items-center justify-end gap-2">
                                                <form action={row.approveUrl} method="POST">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <button type="submit" className="px-3 py-1.5 bg-brandGreen hover:bg-emerald-600 text-white text-[11px] font-bold rounded">Approve</button>
                                                </form>
                                                <form action={row.holdUrl} method="POST" className="flex items-center gap-2">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <input
                                                        type="text"
                                                        name="remarks"
                                                        required
                                                        maxLength={500}
                                                        placeholder="Reason for hold"
                                                        className="w-40 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none"
                                                    />
                                                    <button type="submit" className="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold rounded">Hold</button>
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
