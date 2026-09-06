import { useMemo, useState } from 'react';

export default function AccountsTable({ rows, reviewUrl }) {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        const term = search.toLowerCase();
        if (!term) return rows;
        return rows.filter((r) =>
            r.studentName.toLowerCase().includes(term) || r.referenceNo.toLowerCase().includes(term)
        );
    }, [rows, search]);

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
            <div className="p-5 border-b border-brandNavy/8 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <h2 className="text-sm font-bold text-brandNavy dark:text-white">Account Registry</h2>

                <div className="relative w-full sm:w-64">
                    <span className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-brandNavy/30 dark:text-slate-500">
                        <i className="fa-solid fa-magnifying-glass text-xs" />
                    </span>
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search student..."
                        className="w-full text-xs bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                    />
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr className="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                            <th className="p-4">Profile ID</th>
                            <th className="p-4">Student</th>
                            <th className="p-4">Reference</th>
                            <th className="p-4">Clearance</th>
                            <th className="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                        {filtered.length === 0 && (
                            <tr>
                                <td colSpan={5} className="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">
                                    {rows.length === 0 ? 'No account records found.' : 'No matching records.'}
                                </td>
                            </tr>
                        )}
                        {filtered.map((a) => (
                            <tr key={a.id} className="hover:bg-lightBg/40 dark:hover:bg-slate-800/20 transition-colors">
                                <td className="p-4 font-mono text-brandNavy/40 dark:text-slate-500">{a.profileId}</td>
                                <td className="p-4">
                                    <div className="font-bold text-brandNavy dark:text-white">{a.studentName}</div>
                                    <div className="text-[10px] text-brandNavy/40 dark:text-slate-500">{a.studentEmail}</div>
                                </td>
                                <td className="p-4 font-mono text-brandNavy/40 dark:text-slate-500">{a.referenceNo}</td>
                                <td className="p-4">
                                    {a.cashierStatus === 'Approved' ? (
                                        <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGreen/10 text-brandGreen border border-brandGreen/20 rounded">Approved</span>
                                    ) : (
                                        <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGold/10 text-brandGold border border-brandGold/20 rounded">Pending</span>
                                    )}
                                </td>
                                <td className="p-4 text-right">
                                    {a.cashierStatus !== 'Approved' ? (
                                        <a href={reviewUrl} className="inline-block px-3 py-1.5 bg-brandNavy hover:bg-brandGreen text-white rounded transition-colors font-bold text-[11px]">
                                            <i className="fa-solid fa-arrow-right mr-1" />Review in Cashier Hub
                                        </a>
                                    ) : (
                                        <button disabled className="px-3 py-1.5 bg-lightBg dark:bg-slate-800 text-brandNavy/30 dark:text-slate-600 rounded cursor-not-allowed font-medium border border-brandNavy/8 dark:border-slate-700 text-[11px]">
                                            <i className="fa-solid fa-check mr-1" />Settled
                                        </button>
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
