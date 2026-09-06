import { useMemo, useState } from 'react';
import { peso } from '../utils/format';

export default function TransactionsTable({ rows }) {
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
                <h2 className="text-sm font-bold text-brandNavy dark:text-white">Settled Audit Records</h2>

                <div className="relative w-full sm:w-64">
                    <span className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-brandNavy/30 dark:text-slate-500">
                        <i className="fa-solid fa-magnifying-glass text-xs" />
                    </span>
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search ref or student..."
                        className="w-full text-xs bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                    />
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr className="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                            <th className="p-4">Transaction ID</th>
                            <th className="p-4">Student Name</th>
                            <th className="p-4">Reference</th>
                            <th className="p-4">Amount</th>
                            <th className="p-4">Status</th>
                            <th className="p-4">Processor</th>
                            <th className="p-4 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                        {filtered.length === 0 && (
                            <tr>
                                <td colSpan={7} className="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">
                                    {rows.length === 0 ? 'No transaction records yet.' : 'No matching records.'}
                                </td>
                            </tr>
                        )}
                        {filtered.map((t) => (
                            <tr key={t.id} className="hover:bg-lightBg/40 dark:hover:bg-slate-800/20 transition-colors">
                                <td className="p-4 font-mono text-brandNavy/40 dark:text-slate-500">#{String(t.id).padStart(5, '0')}</td>
                                <td className="p-4 font-bold text-brandNavy dark:text-white">{t.studentName}</td>
                                <td className="p-4 font-mono text-brandNavy/40 dark:text-slate-500">{t.referenceNo}</td>
                                <td className="p-4 font-bold text-brandGreen">{peso(t.amount)}</td>
                                <td className="p-4">
                                    <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGreen/10 text-brandGreen border border-brandGreen/20 rounded">
                                        {t.status}
                                    </span>
                                </td>
                                <td className="p-4 text-brandNavy/50 dark:text-slate-400">{t.processorName}</td>
                                <td className="p-4 text-right text-brandNavy/40 dark:text-slate-500 font-mono">{t.timestamp}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
