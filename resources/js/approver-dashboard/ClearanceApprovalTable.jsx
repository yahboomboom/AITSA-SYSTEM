export default function ClearanceApprovalTable({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="border-b border-brandNavy/8 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest">
                            <th className="py-3.5 px-5">Student Info</th>
                            <th className="py-3.5 px-5">Student ID</th>
                            <th className="py-3.5 px-5">Program</th>
                            <th className="py-3.5 px-5 text-center">Status</th>
                            <th className="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                        {rows.map((row) => (
                            <tr key={row.id} className="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors">
                                <td className="py-4 px-5 font-bold text-brandNavy dark:text-white">{row.studentName}</td>
                                <td className="py-4 px-5 font-mono text-brandNavy/50 dark:text-slate-400">{row.studentId}</td>
                                <td className="py-4 px-5 text-brandNavy/70 dark:text-slate-300">{row.program}</td>
                                <td className="py-4 px-5 text-center">
                                    {row.state === 'locked' ? (
                                        <span className="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">
                                            Awaiting Registrar
                                        </span>
                                    ) : row.state === 'approved' ? (
                                        <span className="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">
                                            Fully Approved
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 uppercase tracking-wider">
                                            Ready for Chair
                                        </span>
                                    )}
                                </td>
                                <td className="py-4 px-5 text-right">
                                    {row.state === 'locked' ? (
                                        <button disabled title="Registrar must sign off first" className="px-3.5 py-1.5 bg-lightBg dark:bg-slate-800 text-brandNavy/30 dark:text-slate-600 text-[11px] font-bold rounded cursor-not-allowed border border-brandNavy/8 dark:border-slate-700">
                                            <i className="fa-solid fa-lock mr-1.5" />Locked
                                        </button>
                                    ) : row.state === 'approved' ? (
                                        <button disabled className="px-3.5 py-1.5 bg-lightBg dark:bg-slate-800 text-brandNavy/30 dark:text-slate-500 text-[11px] font-bold rounded cursor-not-allowed border border-brandNavy/8 dark:border-slate-700">
                                            <i className="fa-solid fa-check-double mr-1.5" />Approved
                                        </button>
                                    ) : (
                                        <div className="flex items-center justify-end gap-2">
                                            <form action={row.signUrl} method="POST" className="inline-block">
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <button type="submit" className="px-3.5 py-1.5 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-black rounded transition-colors">
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
                                                    className="w-36 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none"
                                                />
                                                <button type="submit" className="px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded transition-colors">
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
