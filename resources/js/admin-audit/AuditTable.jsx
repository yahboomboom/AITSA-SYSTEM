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
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
            <div className="bg-lightBg dark:bg-slate-800/40 px-5 py-3 border-b border-brandNavy/8 dark:border-slate-800 flex items-center justify-between flex-wrap gap-3">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-scroll mr-2 text-brandGreen" />Action Log
                </span>
                <div className="flex gap-2 flex-wrap">
                    <div className="relative">
                        <i className="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-brandNavy/30 dark:text-slate-500 text-xs" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search actor or description…"
                            className="pl-8 pr-4 py-2 text-xs rounded border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-500 outline-none focus:border-brandGreen/40 transition-colors w-56"
                        />
                    </div>
                    <select
                        value={action}
                        onChange={(e) => setAction(e.target.value)}
                        className="px-3 py-2 text-xs rounded border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors"
                    >
                        <option value="all">All Actions</option>
                        {ACTIONS.map((a) => <option key={a} value={a}>{a}</option>)}
                    </select>
                </div>
                <span className="text-[10px] font-bold text-brandNavy/40 dark:text-slate-500">{filtered.length} shown</span>
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
                        <table className="w-full text-left">
                            <thead>
                                <tr className="text-[9px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest border-b border-brandNavy/8 dark:border-slate-800">
                                    <th className="py-3 px-4">#</th>
                                    <th className="py-3 px-4">Timestamp</th>
                                    <th className="py-3 px-4">Action</th>
                                    <th className="py-3 px-4">Performed By</th>
                                    <th className="py-3 px-4">Description</th>
                                    <th className="py-3 px-4">Target</th>
                                    <th className="py-3 px-4">IP Address</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                                {filtered.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="py-10 text-center text-sm text-brandNavy/40 dark:text-slate-500">No matching entries.</td>
                                    </tr>
                                )}
                                {filtered.map((log, i) => (
                                    <tr key={i} className="hover:bg-lightBg/50 dark:hover:bg-slate-800/20 transition-colors">
                                        <td className="py-3 px-4 text-[10px] font-mono text-brandNavy/30 dark:text-slate-600">
                                            {String(log.seq).padStart(4, '0')}
                                        </td>
                                        <td className="py-3 px-4">
                                            <p className="text-[11px] font-bold text-brandNavy dark:text-slate-200">{log.date}</p>
                                            <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 font-mono">{log.time}</p>
                                        </td>
                                        <td className="py-3 px-4"><ActionBadge action={log.action} /></td>
                                        <td className="py-3 px-4">
                                            <p className="text-[11px] font-bold text-brandNavy dark:text-slate-200">{log.actorName}</p>
                                            <p className="text-[9px] text-brandNavy/40 dark:text-slate-500">ID {log.actorId ?? '—'}</p>
                                        </td>
                                        <td className="py-3 px-4 max-w-xs">
                                            <p className="text-[11px] text-brandNavy/80 dark:text-slate-300 leading-snug">{log.description}</p>
                                        </td>
                                        <td className="py-3 px-4 text-[10px] font-mono text-brandNavy/50 dark:text-slate-500">
                                            {log.targetType ? `${log.targetType} #${log.targetId}` : '—'}
                                        </td>
                                        <td className="py-3 px-4 text-[10px] font-mono text-brandNavy/40 dark:text-slate-600">
                                            {log.ipAddress ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {pagination.total > logs.length && (
                        <div className="px-5 py-4 border-t border-brandNavy/8 dark:border-slate-800 flex items-center justify-between">
                            <p className="text-[10px] text-brandNavy/40 dark:text-slate-500">
                                Showing {pagination.firstItem}–{pagination.lastItem} of {pagination.total} entries
                            </p>
                            <div className="flex gap-1">
                                {pagination.prevPageUrl ? (
                                    <a href={pagination.prevPageUrl} className="px-3 py-1.5 text-[10px] font-bold rounded text-brandNavy dark:text-slate-300 border border-brandNavy/15 dark:border-slate-700 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">Prev</a>
                                ) : (
                                    <span className="px-3 py-1.5 text-[10px] rounded text-brandNavy/25 dark:text-slate-600 border border-brandNavy/10 dark:border-slate-700 cursor-not-allowed">Prev</span>
                                )}
                                {pagination.nextPageUrl ? (
                                    <a href={pagination.nextPageUrl} className="px-3 py-1.5 text-[10px] font-bold rounded text-brandNavy dark:text-slate-300 border border-brandNavy/15 dark:border-slate-700 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">Next</a>
                                ) : (
                                    <span className="px-3 py-1.5 text-[10px] rounded text-brandNavy/25 dark:text-slate-600 border border-brandNavy/10 dark:border-slate-700 cursor-not-allowed">Next</span>
                                )}
                            </div>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
