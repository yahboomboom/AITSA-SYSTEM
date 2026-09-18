import { useMemo, useState } from 'react';
import { peso } from '../utils/format';

export default function TransactionsTable({ rows }) {
    const [search, setSearch] = useState('');
    const [view, setView] = useState('latest'); // 'latest' | 'full'

    const latestPerStudent = useMemo(() => {
        const byUser = new Map();
        for (const t of rows) {
            const key = t.userId ?? t.studentName;
            const existing = byUser.get(key);
            if (!existing || new Date(t.createdAt) > new Date(existing.createdAt)) {
                byUser.set(key, t);
            }
        }
        return Array.from(byUser.values()).sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    }, [rows]);

    const baseRows = view === 'latest' ? latestPerStudent : rows;

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();
        if (!term) return baseRows;
        return baseRows.filter((r) =>
            r.studentName.toLowerCase().includes(term) ||
            (r.studentNo ?? '').toLowerCase().includes(term) ||
            r.referenceNo.toLowerCase().includes(term)
        );
    }, [baseRows, search]);

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                    <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Settled audit records</h2>
                    <div className="flex items-center bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded p-0.5 text-xs font-medium">
                        <button
                            onClick={() => setView('latest')}
                            className={`px-3 py-1 rounded transition-colors ${view === 'latest' ? 'bg-brandNavy text-white' : 'text-brandNavy/50 dark:text-slate-400'}`}
                        >
                            Latest per student
                        </button>
                        <button
                            onClick={() => setView('full')}
                            className={`px-3 py-1 rounded transition-colors ${view === 'full' ? 'bg-brandNavy text-white' : 'text-brandNavy/50 dark:text-slate-400'}`}
                        >
                            Full history
                        </button>
                    </div>
                </div>

                <div className="relative w-full sm:w-64">
                    <span className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-brandNavy/30 dark:text-slate-500">
                        <i className="fa-solid fa-magnifying-glass text-xs" />
                    </span>
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by student no., name, or ref…"
                        className="w-full text-sm bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                    />
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Transaction ID</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Student no.</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Student name</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Reference</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Amount</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Status</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Processor</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filtered.length === 0 && (
                            <tr>
                                <td colSpan={8} className="border-brandNavy/8 dark:border-slate-800 p-8 text-center text-brandNavy/40 dark:text-slate-500">
                                    {rows.length === 0 ? 'No transaction records yet.' : 'No matching records.'}
                                </td>
                            </tr>
                        )}
                        {filtered.map((t) => (
                            <tr key={t.id}>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/40 dark:text-slate-500">#{String(t.id).padStart(5, '0')}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/60 dark:text-slate-400">{t.studentNo}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandNavy dark:text-white">{t.studentName}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/40 dark:text-slate-500">{t.referenceNo}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandGreen">{peso(t.amount)}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    <span className="ui-badge-outline border-brandGreen text-brandGreen">{t.status}</span>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-400">{t.processorName}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-right text-brandNavy/40 dark:text-slate-500 font-mono">{t.timestamp}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}