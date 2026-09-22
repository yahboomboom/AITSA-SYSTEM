import { useMemo, useState } from 'react';

export default function ClearanceQueueTable({ rows, csrfToken, documentsPageUrl }) {
    const [search, setSearch] = useState('');
    // Defaults to the pending queue — every clearance for the current term is
    // already loaded (see registrar.dashboard), so there's no reason to make
    // the registrar search or pick a filter just to see what's waiting on them.
    const [statusFilter, setStatusFilter] = useState('pending');
    const query = search.trim().toLowerCase();
    const filteredRows = useMemo(() => {
        if (!query && !statusFilter) return [];

        return rows.filter((row) => {
            const matchesQuery = !query || `${row.studentName} ${row.studentId}`.toLowerCase().includes(query);
            const matchesStatus = !statusFilter
                || statusFilter === 'all'
                || (statusFilter === 'cleared' && row.isApproved)
                || (statusFilter === 'provisional' && row.isProvisional)
                || (statusFilter === 'pending' && !row.isApproved && !row.isProvisional);

            return matchesQuery && matchesStatus;
        });
    }, [rows, query, statusFilter]);

    return (
        <>
            <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-4">
                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <div className="relative w-full sm:w-72">
                        <i className="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-brandNavy/40 dark:text-slate-500 text-xs" />
                        <input
                            type="text"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search student name or ID…"
                            className="w-full bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 pl-9 pr-4 py-2 rounded focus:outline-none focus:border-brandGreen dark:focus:border-emerald-500/50 transition-colors"
                        />
                    </div>
                    <select
                        value={statusFilter}
                        onChange={(event) => setStatusFilter(event.target.value)}
                        className="w-full sm:w-auto bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-sm text-brandNavy dark:text-slate-200 px-3 py-2 rounded focus:outline-none focus:border-brandGreen transition-colors"
                    >
                        <option value="">Status</option>
                        <option value="all">View all students</option>
                        <option value="pending">Pending review</option>
                        <option value="provisional">Provisional</option>
                        <option value="cleared">Cleared</option>
                    </select>
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-400">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student info</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Student ID</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Program / track</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Admission status</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredRows.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="border-brandNavy/8 dark:border-slate-800 py-12 text-center text-brandNavy/40 dark:text-slate-500">
                                    <div className="flex flex-col items-center justify-center space-y-2">
                                        <i className="fa-solid fa-box-open text-2xl text-brandNavy/20 dark:text-slate-600" />
                                        <span>{query || statusFilter ? 'No matching students.' : 'Search or choose a status to view admission and clearance records.'}</span>
                                    </div>
                                </td>
                            </tr>
                        ) : (
                            filteredRows.map((row) => <ClearanceRow key={row.id} row={row} csrfToken={csrfToken} documentsPageUrl={documentsPageUrl} />)
                        )}
                    </tbody>
                </table>
            </div>
        </>
    );
}

function ClearanceRow({ row, csrfToken, documentsPageUrl }) {
    const [openAction, setOpenAction] = useState(null); // 'hold' | 'provisional' | null

    return (
        <tr>
            <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandNavy dark:text-white">{row.studentName}</td>
            <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/60 dark:text-slate-400">{row.studentId}</td>
            <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/70 dark:text-slate-300">{row.program}</td>
            <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                {row.isApproved ? (
                    <span className="ui-badge-outline border-brandGreen text-brandGreen">
                        <i className="fa-solid fa-circle-check" />Cleared
                    </span>
                ) : row.isProvisional ? (
                    <span className="ui-badge-outline border-cyan-600 text-cyan-600">
                        <i className="fa-solid fa-hourglass-half" />Provisional
                    </span>
                ) : (
                    <span className="ui-badge-outline border-brandGold text-brandGold">
                        <i className="fa-solid fa-clock" />Pending review
                    </span>
                )}
            </td>
            <td className="border-brandNavy/8 dark:border-slate-800 text-right align-top">
                <div className="flex flex-wrap items-center justify-end gap-1.5">
                    <a
                        href={`${documentsPageUrl}?search=${encodeURIComponent(row.studentId)}`}
                        className="ui-btn-primary bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors"
                    >
                        <i className="fa-solid fa-eye" />View
                    </a>
                    {!row.isApproved && (
                        <>
                            <form action={row.signUrl} method="POST" className="inline">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                                    <i className="fa-solid fa-pen-nib" />Sign
                                </button>
                            </form>
                            <button
                                type="button"
                                onClick={() => setOpenAction((current) => (current === 'hold' ? null : 'hold'))}
                                className="ui-btn-primary bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors"
                            >
                                Hold
                            </button>
                            {!row.isProvisional && (
                                <button
                                    type="button"
                                    onClick={() => setOpenAction((current) => (current === 'provisional' ? null : 'provisional'))}
                                    className="ui-btn-primary bg-cyan-600 hover:bg-cyan-700 text-white transition-colors"
                                >
                                    Provisional
                                </button>
                            )}
                        </>
                    )}
                </div>
                {openAction === 'hold' && (
                    <form action={row.holdUrl} method="POST" className="mt-2 flex gap-2 justify-end">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="text" name="remarks" required maxLength={500} placeholder="Reason for hold" className="min-w-0 flex-1 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-xs text-brandNavy dark:text-slate-200 outline-none focus:border-brandNavy dark:focus:border-slate-500" />
                        <button type="submit" className="ui-btn-primary bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors">
                            Confirm hold
                        </button>
                    </form>
                )}
                {openAction === 'provisional' && (
                    <form action={row.grantProvisionalUrl} method="POST" className="mt-2 flex gap-2 justify-end">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="text" name="reason" required maxLength={1000} placeholder="Reason for provisional" className="min-w-0 flex-1 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-xs text-brandNavy dark:text-slate-200 outline-none focus:border-brandNavy dark:focus:border-slate-500" />
                        <button type="submit" className="ui-btn-primary bg-cyan-600 hover:bg-cyan-700 text-white transition-colors">
                            Grant
                        </button>
                    </form>
                )}
            </td>
        </tr>
    );
}
