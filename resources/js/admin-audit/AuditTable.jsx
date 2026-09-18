import { useMemo, useState } from 'react';
import ActionBadge from './ActionBadge';

const ACTIONS = ['Account Created', 'Applicant Verified', 'Applicant Declined', 'Clearance Signed', 'Payment Approved', 'Admission Approved'];

export default function AuditTable({ logs, pagination }) {
    const [search, setSearch] = useState('');
    const [action, setAction] = useState('all');

    const filtered = useMemo(() => {
        const q = search.toLowerCase();
        return logs.filter((log) => {
            const haystack = `${log.actorName} ${log.description}`.toLowerCase();
            const matchesSearch = haystack.includes(q);
            const matchesAction = action === 'all' || log.action === action;
            return matchesSearch && matchesAction;
        });
    }, [logs, search, action]);

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between flex-wrap gap-3">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                    <i className="fa-solid fa-scroll mr-2 text-brandGreen" />Action log
                </h3>
                <div className="flex gap-2 flex-wrap items-center">
                    <div className="relative">
                        <i className="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-brandNavy/30 dark:text-slate-500 text-xs" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search actor or description…"
                            className="pl-9 pr-4 py-2 text-sm rounded border border-brandNavy/10 dark:border-slate-700 bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 outline-none focus:border-brandGreen/40 transition-colors w-56"
                        />
                    </div>
                    <select
                        value={action}
                        onChange={(e) => setAction(e.target.value)}
                        className="px-3 py-2 text-sm rounded border border-brandNavy/10 dark:border-slate-700 bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors"
                    >
                        <option value="all">All actions</option>
                        {ACTIONS.map((a) => <option key={a} value={a}>{a}</option>)}
                    </select>
                    <span className="text-xs text-brandNavy/40 dark:text-slate-500">{filtered.length} shown</span>
                </div>
            </div>

            {logs.length === 0 ? (
                <div className="py-16 text-center">
                    <i className="fa-solid fa-scroll text-3xl text-brandNavy/15 dark:text-slate-700 mb-3 block" />
                    <p className="text-sm text-brandNavy/40 dark:text-slate-500">No audit entries yet.</p>
                    <p className="text-xs text-brandNavy/30 dark:text-slate-600 mt-1">Entries appear when staff perform actions.</p>
                </div>
            ) : (
                <>
                    <div className="overflow-x-auto">
                        <table className="ui-table">
                            <thead>
                                <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                                    <th className="border-brandNavy/8 dark:border-slate-800">#</th>
                                    <th className="border-brandNavy/8 dark:border-slate-800">Timestamp</th>
                                    <th className="border-brandNavy/8 dark:border-slate-800">Action</th>
                                    <th className="border-brandNavy/8 dark:border-slate-800">Performed by</th>
                                    <th className="border-brandNavy/8 dark:border-slate-800">Description</th>
                                    <th className="border-brandNavy/8 dark:border-slate-800">Target</th>
                                    <th className="border-brandNavy/8 dark:border-slate-800">IP address</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filtered.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="border-brandNavy/8 dark:border-slate-800 py-10 text-center text-brandNavy/40 dark:text-slate-500">No matching entries.</td>
                                    </tr>
                                )}
                                {filtered.map((log, i) => (
                                    <tr key={i}>
                                        <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-xs text-brandNavy/40 dark:text-slate-500">
                                            {String(log.seq).padStart(4, '0')}
                                        </td>
                                        <td className="border-brandNavy/8 dark:border-slate-800">
                                            <p className="font-medium text-brandNavy dark:text-slate-200">{log.date}</p>
                                            <p className="text-xs text-brandNavy/40 dark:text-slate-500 font-mono">{log.time}</p>
                                        </td>
                                        <td className="border-brandNavy/8 dark:border-slate-800"><ActionBadge action={log.action} /></td>
                                        <td className="border-brandNavy/8 dark:border-slate-800">
                                            <p className="font-medium text-brandNavy dark:text-slate-200">{log.actorName}</p>
                                            <p className="text-xs text-brandNavy/40 dark:text-slate-500">ID {log.actorId ?? '—'}</p>
                                        </td>
                                        <td className="border-brandNavy/8 dark:border-slate-800 max-w-xs">
                                            <p className="text-brandNavy/80 dark:text-slate-300 leading-snug">{log.description}</p>
                                        </td>
                                        <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-xs text-brandNavy/50 dark:text-slate-500">
                                            {log.targetType ? `${log.targetType} #${log.targetId}` : '—'}
                                        </td>
                                        <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-xs text-brandNavy/40 dark:text-slate-600">
                                            {log.ipAddress ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {pagination.total > logs.length && (
                        <div className="px-5 py-4 border-t border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                            <p className="text-xs text-brandNavy/40 dark:text-slate-500">
                                Showing {pagination.firstItem}–{pagination.lastItem} of {pagination.total} entries
                            </p>
                            <div className="flex gap-1">
                                {pagination.prevPageUrl ? (
                                    <a href={pagination.prevPageUrl} className="px-3 py-1.5 text-xs font-medium rounded text-brandNavy dark:text-slate-300 border border-brandNavy/15 dark:border-slate-700 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">Prev</a>
                                ) : (
                                    <span className="px-3 py-1.5 text-xs rounded text-brandNavy/25 dark:text-slate-600 border border-brandNavy/10 dark:border-slate-700 cursor-not-allowed">Prev</span>
                                )}
                                {pagination.nextPageUrl ? (
                                    <a href={pagination.nextPageUrl} className="px-3 py-1.5 text-xs font-medium rounded text-brandNavy dark:text-slate-300 border border-brandNavy/15 dark:border-slate-700 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">Next</a>
                                ) : (
                                    <span className="px-3 py-1.5 text-xs rounded text-brandNavy/25 dark:text-slate-600 border border-brandNavy/10 dark:border-slate-700 cursor-not-allowed">Next</span>
                                )}
                            </div>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
