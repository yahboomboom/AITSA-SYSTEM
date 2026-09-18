import { useMemo, useState } from 'react';
import { peso } from '../utils/format';

const DEFAULT_VISIBLE = 10;

export default function AccountsTable({ rows, reviewUrl }) {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();
        if (!term) return rows;
        return rows.filter((r) =>
            r.studentName.toLowerCase().includes(term) ||
            (r.studentNo ?? '').toLowerCase().includes(term) ||
            (r.studentEmail ?? '').toLowerCase().includes(term) ||
            (r.referenceNo ?? '').toLowerCase().includes(term)
        );
    }, [rows, search]);

    // Show only the first 10 accounts until the cashier searches for someone specific —
    // keeps the registry short by default while staying fully searchable by name or student no.
    const visible = search.trim() ? filtered : filtered.slice(0, DEFAULT_VISIBLE);

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Account registry</h2>

                <div className="relative w-full sm:w-64">
                    <span className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-brandNavy/30 dark:text-slate-500">
                        <i className="fa-solid fa-magnifying-glass text-xs" />
                    </span>
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by student no. or name…"
                        className="w-full text-sm bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                    />
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student No.</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Student</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Last Payment</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Clearance</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {visible.length === 0 && (
                            <tr>
                                <td colSpan={5} className="border-brandNavy/8 dark:border-slate-800 p-8 text-center text-brandNavy/40 dark:text-slate-500">
                                    {rows.length === 0 ? 'No accounts pending review.' : 'No matching students.'}
                                </td>
                            </tr>
                        )}
                        {visible.map((a) => (
                            <tr key={a.id}>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/40 dark:text-slate-500">{a.studentNo}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    <div className="font-medium text-brandNavy dark:text-white">{a.studentName}</div>
                                    <div className="text-xs text-brandNavy/40 dark:text-slate-500">{a.studentEmail}</div>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    {a.referenceNo ? (
                                        <>
                                            <div className="font-mono text-brandNavy/70 dark:text-slate-300">{a.referenceNo}</div>
                                            <div className="text-xs text-brandNavy/40 dark:text-slate-500">
                                                {peso(a.lastPaymentAmount)} · {a.lastPaymentDate}
                                            </div>
                                        </>
                                    ) : (
                                        <span className="text-brandNavy/30 dark:text-slate-600 italic">No payment yet</span>
                                    )}
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    {a.cashierStatus === 'Approved' ? (
                                        <span className="ui-badge-outline border-brandGreen text-brandGreen">Approved</span>
                                    ) : (
                                        <span className="ui-badge-outline border-brandGold text-brandGold">Pending</span>
                                    )}
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-right">
                                    {a.cashierStatus !== 'Approved' ? (
                                        <a href={reviewUrl} className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                                            <i className="fa-solid fa-arrow-right" />Review in Cashier Hub
                                        </a>
                                    ) : (
                                        <span className="ui-badge-outline border-brandNavy/10 text-brandNavy/30 dark:border-slate-700 dark:text-slate-600">
                                            <i className="fa-solid fa-check" />Settled
                                        </span>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {!search.trim() && filtered.length > DEFAULT_VISIBLE && (
                    <div className="px-4 py-3 text-center text-[11px] text-brandNavy/40 dark:text-slate-500 border-t border-brandNavy/8 dark:border-slate-800">
                        Showing {DEFAULT_VISIBLE} of {filtered.length} — search by student no. or name to find someone else.
                    </div>
                )}
            </div>
        </div>
    );
}