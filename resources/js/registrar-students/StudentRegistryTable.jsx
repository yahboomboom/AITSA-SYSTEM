export default function StudentRegistryTable({ rows }) {
    if (rows.length === 0) {
        return (
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg overflow-hidden">
                <div className="py-16 text-center text-brandNavy/40 dark:text-slate-500">
                    <i className="fa-solid fa-users text-3xl mb-3 block opacity-40" />
                    <p className="text-sm font-semibold">No student accounts found.</p>
                    <p className="text-xs mt-1">Student accounts are created by the Admin.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg overflow-hidden">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b border-brandNavy/8 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40">
                        <th className="text-left px-5 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider">Student</th>
                        <th className="text-left px-5 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider hidden sm:table-cell">Student ID</th>
                        <th className="text-left px-5 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider hidden md:table-cell">Program</th>
                        <th className="text-left px-5 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider hidden md:table-cell">Year</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                    {rows.map((s) => (
                        <tr key={s.id} className="hover:bg-lightBg dark:hover:bg-slate-800/30 transition-colors">
                            <td className="px-5 py-3.5">
                                <div className="flex items-center gap-3">
                                    <div className="w-8 h-8 rounded-full bg-brandNavy/10 dark:bg-slate-700 flex items-center justify-center text-xs font-black text-brandNavy dark:text-slate-300 flex-shrink-0">
                                        {s.name.charAt(0).toUpperCase()}
                                    </div>
                                    <div>
                                        <p className="font-bold text-brandNavy dark:text-slate-200 text-sm leading-tight">{s.name}</p>
                                        <p className="text-xs text-brandNavy/40 dark:text-slate-500">{s.email}</p>
                                    </div>
                                </div>
                            </td>
                            <td className="px-5 py-3.5 hidden sm:table-cell">
                                <span className="font-mono text-xs text-brandNavy/70 dark:text-slate-400">{s.loginId}</span>
                            </td>
                            <td className="px-5 py-3.5 hidden md:table-cell">
                                <span className="text-xs text-brandNavy/60 dark:text-slate-400">{s.major ?? '—'}</span>
                            </td>
                            <td className="px-5 py-3.5 hidden md:table-cell">
                                <div className="flex items-center gap-2">
                                    <span className="text-xs text-brandNavy/60 dark:text-slate-400">{s.yearLevel ?? '—'}</span>
                                    {s.isIrregular ? (
                                        <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-500/10 text-amber-600">Irregular</span>
                                    ) : (
                                        <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-500/10 text-blue-600">Regular</span>
                                    )}
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
