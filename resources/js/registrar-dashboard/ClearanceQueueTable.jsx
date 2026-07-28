export default function ClearanceQueueTable({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg overflow-hidden">
            <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h3 className="text-sm font-bold text-brandNavy dark:text-white tracking-wide">Admission & Clearance Processing Queue</h3>
                <div className="relative w-full sm:w-72">
                    <i className="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-brandNavy/40 dark:text-slate-500 text-xs" />
                    <input
                        type="text"
                        placeholder="Search student name or ID..."
                        className="w-full bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 pl-9 pr-4 py-2 rounded focus:outline-none focus:border-brandGreen dark:focus:border-emerald-500/50 transition-colors"
                    />
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                            <th className="py-4 px-6">Student Info</th>
                            <th className="py-4 px-6">Student ID</th>
                            <th className="py-4 px-6">Program / Track</th>
                            <th className="py-4 px-6 text-center">Admission Status</th>
                            <th className="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                        {rows.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="py-12 text-center text-brandNavy/40 dark:text-slate-500 font-medium">
                                    <div className="flex flex-col items-center justify-center space-y-2">
                                        <i className="fa-solid fa-box-open text-2xl text-brandNavy/20 dark:text-slate-600" />
                                        <span>No students pending admission review at this time.</span>
                                    </div>
                                </td>
                            </tr>
                        ) : (
                            rows.map((row) => (
                                <tr key={row.id} className="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors">
                                    <td className="py-5 px-6 font-bold text-brandNavy dark:text-white tracking-wide">{row.studentName}</td>
                                    <td className="py-5 px-6 font-mono text-brandNavy/60 dark:text-slate-400 font-medium">{row.studentId}</td>
                                    <td className="py-5 px-6 text-brandNavy/70 dark:text-slate-300 font-medium tracking-wide">{row.program}</td>
                                    <td className="py-5 px-6 text-center">
                                        {row.isApproved ? (
                                            <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">
                                                <i className="fa-solid fa-circle-check mr-1.5" />Cleared
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">
                                                <i className="fa-solid fa-clock mr-1.5" />Pending Review
                                            </span>
                                        )}
                                    </td>
                                    <td className="py-5 px-6 text-right">
                                        {row.isApproved ? (
                                            <button disabled className="px-4 py-2 bg-lightBg dark:bg-slate-800 text-brandNavy/40 dark:text-slate-500 text-[11px] font-bold rounded cursor-not-allowed border border-brandNavy/10 dark:border-slate-700">
                                                <i className="fa-solid fa-check-double mr-1.5" />Signed Off
                                            </button>
                                        ) : (
                                            <div className="flex items-center justify-end gap-2">
                                                <form action={row.signUrl} method="POST" className="inline-block">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <button type="submit" className="px-4 py-2 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        Sign Clearance
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
                                                    <button type="submit" className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded transition-colors tracking-wide">
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
