export default function StudentRegistryTable({ rows }) {
    if (rows.length === 0) {
        return (
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
                <div className="py-16 text-center text-brandNavy/40 dark:text-slate-500">
                    <i className="fa-solid fa-users text-3xl mb-3 block opacity-40" />
                    <p className="text-sm font-medium">No student accounts found.</p>
                    <p className="text-xs mt-1">Student accounts are created by the Admin.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 hidden sm:table-cell">Student ID</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 hidden md:table-cell">Program</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 hidden md:table-cell">Year</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((s) => (
                            <tr key={s.id}>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-full bg-brandNavy/10 dark:bg-slate-700 flex items-center justify-center text-xs font-semibold text-brandNavy dark:text-slate-300 flex-shrink-0">
                                            {s.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <p className="font-medium text-brandNavy dark:text-slate-200 leading-tight">{s.name}</p>
                                            <p className="text-xs text-brandNavy/40 dark:text-slate-500">{s.email}</p>
                                        </div>
                                    </div>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 hidden sm:table-cell">
                                    <span className="font-mono text-xs text-brandNavy/70 dark:text-slate-400">{s.loginId}</span>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 hidden md:table-cell text-brandNavy/60 dark:text-slate-400">{s.major ?? '—'}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 hidden md:table-cell">
                                    <div className="flex items-center gap-2">
                                        <span className="text-brandNavy/60 dark:text-slate-400">{s.yearLevel ?? '—'}</span>
                                        {s.isIrregular ? (
                                            <span className="ui-badge-outline border-brandGold text-brandGold">Irregular</span>
                                        ) : (
                                            <span className="ui-badge-outline border-blue-500 text-blue-600">Regular</span>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
