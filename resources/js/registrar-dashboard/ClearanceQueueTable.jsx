import { useMemo, useState } from 'react';

export default function ClearanceQueueTable({ rows, csrfToken }) {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
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
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Admission &amp; clearance processing queue</h3>
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
                            filteredRows.map((row) => <ClearanceRow key={row.id} row={row} csrfToken={csrfToken} />)
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function ClearanceRow({ row, csrfToken }) {
    const [action, setAction] = useState('');

    const handleActionChange = (event) => {
        const nextAction = event.target.value;
        setAction(nextAction);
        if (nextAction === 'view') {
            window.location.href = `${window.location.pathname}?documents_search=${encodeURIComponent(row.studentId)}&documents_view=all#student-submitted-documents`;
            return;
        }
        if (nextAction === 'sign') {
            event.currentTarget.form?.requestSubmit();
        }
    };

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
                <form action={row.signUrl} method="POST">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <select value={action} onChange={handleActionChange} className="w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-xs text-brandNavy dark:text-slate-200 outline-none focus:border-brandNavy dark:focus:border-slate-500">
                        <option value="">Select action</option>
                        <option value="view">View submitted documents</option>
                        <option value="sign" disabled={row.isApproved}>Sign and approve clearance</option>
                        <option value="hold" disabled={row.isApproved}>Place on hold</option>
                        {!row.isProvisional && !row.isApproved && <option value="provisional">Grant provisional</option>}
                    </select>
                </form>
                {action === 'hold' && (
                    <form action={row.holdUrl} method="POST" className="mt-2 flex gap-2">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="text" name="remarks" required maxLength={500} placeholder="Reason for hold" className="min-w-0 flex-1 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-xs text-brandNavy dark:text-slate-200 outline-none focus:border-brandNavy dark:focus:border-slate-500" />
                        <button type="submit" className="ui-btn-primary bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors">
                            Hold
                        </button>
                    </form>
                )}
                {action === 'provisional' && (
                    <form action={row.grantProvisionalUrl} method="POST" className="mt-2 flex gap-2">
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
