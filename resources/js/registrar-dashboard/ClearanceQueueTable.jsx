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
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg overflow-hidden">
            <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h3 className="text-sm font-bold text-brandNavy dark:text-white tracking-wide">Admission & Clearance Processing Queue</h3>
                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <div className="relative w-full sm:w-72">
                        <i className="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-brandNavy/40 dark:text-slate-500 text-xs" />
                        <input
                            type="text"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search student name or ID..."
                            className="w-full bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 pl-9 pr-4 py-2 rounded focus:outline-none focus:border-brandGreen dark:focus:border-emerald-500/50 transition-colors"
                        />
                    </div>
                    <select
                        value={statusFilter}
                        onChange={(event) => setStatusFilter(event.target.value)}
                        className="w-full sm:w-auto bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 px-3 py-2 rounded focus:outline-none focus:border-brandGreen transition-colors"
                    >
                        <option value="">Status</option>
                        <option value="all">View all students</option>
                        <option value="pending">Pending review</option>
                        <option value="provisional">Provisional</option>
                        <option value="cleared">Cleared</option>
                    </select>
                </div>
            </div>

            <div className="overflow-x-hidden">
                <table className="w-full table-fixed text-left border-collapse">
                    <thead>
                        <tr className="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                            <th className="w-[23%] py-3 px-4">Student Info</th>
                            <th className="w-[14%] py-3 px-4">Student ID</th>
                            <th className="w-[26%] py-3 px-4">Program / Track</th>
                            <th className="w-[18%] py-3 px-4 text-center">Admission Status</th>
                            <th className="w-[19%] py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                        {filteredRows.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="py-12 text-center text-brandNavy/40 dark:text-slate-500 font-medium">
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
        <tr className="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors">
                                    <td className="py-4 px-4 font-bold text-brandNavy dark:text-white tracking-wide break-words">{row.studentName}</td>
                                    <td className="py-4 px-4 font-mono text-brandNavy/60 dark:text-slate-400 font-medium break-words">{row.studentId}</td>
                                    <td className="py-4 px-4 text-brandNavy/70 dark:text-slate-300 font-medium tracking-wide break-words">{row.program}</td>
            <td className="py-4 px-4 text-center">
                {row.isApproved ? (
                    <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider whitespace-nowrap"><i className="fa-solid fa-circle-check mr-1.5" />Cleared</span>
                ) : row.isProvisional ? (
                    <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-cyan-600/10 text-cyan-600 border border-cyan-600/20 uppercase tracking-wider whitespace-nowrap"><i className="fa-solid fa-hourglass-half mr-1.5" />Provisional</span>
                ) : (
                    <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider whitespace-nowrap"><i className="fa-solid fa-clock mr-1.5" />Pending Review</span>
                )}
            </td>
            <td className="py-4 px-4 text-center align-top">
                <form action={row.signUrl} method="POST">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <select value={action} onChange={handleActionChange} className="w-full px-2 py-2 text-[11px] rounded border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200">
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
                        <input type="text" name="remarks" required maxLength={500} placeholder="Reason for hold" className="min-w-0 flex-1 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none" />
                        <button type="submit" className="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded whitespace-nowrap">Hold</button>
                    </form>
                )}
                {action === 'provisional' && (
                    <form action={row.grantProvisionalUrl} method="POST" className="mt-2 flex gap-2">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="text" name="reason" required maxLength={1000} placeholder="Reason for provisional" className="min-w-0 flex-1 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none" />
                        <button type="submit" className="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white text-[11px] font-black rounded whitespace-nowrap">Grant</button>
                    </form>
                )}
            </td>
        </tr>
    );
}
