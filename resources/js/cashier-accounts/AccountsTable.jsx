import { Fragment, useMemo, useState } from 'react';
import { peso } from '../utils/format';

const DEFAULT_VISIBLE = 10;

const FEE_LABELS = {
    reservation: 'Reservation fee',
    tuition: 'Tuition',
};

function feeLabel(type) {
    if (!type) return 'Payment';
    return FEE_LABELS[type] ?? type.charAt(0).toUpperCase() + type.slice(1);
}

function statusBadge(status) {
    if (status === 'Settled') return 'border-brandGreen text-brandGreen';
    if (status === 'Pending') return 'border-brandGold text-brandGold';
    return 'border-red-500 text-red-500';
}

export default function AccountsTable({ rows }) {
    const [openIds, setOpenIds] = useState({});
    const toggleOpen = (id) => setOpenIds((prev) => ({ ...prev, [id]: !prev[id] }));
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
                        placeholder="Search by student no. or name..."
                        className="w-full text-xs bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                    />
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr className="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                            <th className="p-4">Student No.</th>
                            <th className="p-4">Student</th>
                            <th className="p-4">Last Payment</th>
                            <th className="p-4">Clearance</th>
                            <th className="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                        {visible.length === 0 && (
                            <tr>
                                <td colSpan={5} className="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">
                                    {rows.length === 0 ? 'No accounts pending review.' : 'No matching students.'}
                                </td>
                            </tr>
                        )}
                        {visible.map((a) => {
                            const payments = a.payments ?? [];
                            const isOpen = !!openIds[a.id];
                            const totalSettled = payments
                                .filter((p) => p.status === 'Settled')
                                .reduce((sum, p) => sum + Number(p.amount ?? 0), 0);

                            return (
                                <Fragment key={a.id}>
                                    <tr>
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
                                            <button
                                                type="button"
                                                onClick={() => toggleOpen(a.id)}
                                                aria-expanded={isOpen}
                                                className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors"
                                            >
                                                <i className="fa-solid fa-clock-rotate-left" />Review transaction history
                                                <i className={`fa-solid fa-chevron-down text-[10px] ml-1 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
                                            </button>
                                        </td>
                                    </tr>

                                    {isOpen && (
                                        <tr>
                                            <td colSpan={5} className="border-brandNavy/8 dark:border-slate-800 bg-lightBg/60 dark:bg-slate-900/40 p-0">
                                                <div className="p-4">
                                                    {payments.length === 0 ? (
                                                        <p className="text-sm text-brandNavy/40 dark:text-slate-500 italic">
                                                            This student has no payments on record yet.
                                                        </p>
                                                    ) : (
                                                        <>
                                                            <div className="overflow-x-auto">
                                                                <table className="w-full text-sm">
                                                                    <thead>
                                                                        <tr className="text-left text-xs text-brandNavy/50 dark:text-slate-500">
                                                                            <th className="py-1.5 pr-4 font-medium">Paid for</th>
                                                                            <th className="py-1.5 pr-4 font-medium">Reference</th>
                                                                            <th className="py-1.5 pr-4 font-medium">Amount</th>
                                                                            <th className="py-1.5 pr-4 font-medium">Status</th>
                                                                            <th className="py-1.5 pr-4 font-medium">Method</th>
                                                                            <th className="py-1.5 font-medium text-right">Date</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        {payments.map((p) => (
                                                                            <tr key={p.id} className="border-t border-brandNavy/8 dark:border-slate-800">
                                                                                <td className="py-2 pr-4 font-medium text-brandNavy dark:text-slate-200">{feeLabel(p.feeType)}</td>
                                                                                <td className="py-2 pr-4 font-mono text-brandNavy/70 dark:text-slate-300">{p.referenceNo}</td>
                                                                                <td className="py-2 pr-4 text-brandNavy dark:text-slate-200">{peso(p.amount)}</td>
                                                                                <td className="py-2 pr-4">
                                                                                    <span className={`ui-badge-outline ${statusBadge(p.status)}`}>{p.status}</span>
                                                                                </td>
                                                                                <td className="py-2 pr-4 text-brandNavy/60 dark:text-slate-400">{p.gateway ?? '—'}</td>
                                                                                <td className="py-2 text-right text-brandNavy/60 dark:text-slate-400">{p.date ?? '—'}</td>
                                                                            </tr>
                                                                        ))}
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            <div className="mt-3 pt-3 border-t border-brandNavy/8 dark:border-slate-800 text-sm text-brandNavy/60 dark:text-slate-400">
                                                                Total settled: <span className="font-semibold text-brandNavy dark:text-white">{peso(totalSettled)}</span>
                                                            </div>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </Fragment>
                            );
                        })}
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