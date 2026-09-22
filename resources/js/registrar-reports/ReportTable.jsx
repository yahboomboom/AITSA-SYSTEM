import { useMemo, useState } from 'react';
import StatusBadge from './StatusBadge';

function exportCSV(rows, schoolYear) {
    const headers = ['#', 'Student Name', 'Student No.', 'Email', 'Dept Chair', 'Cashier', 'Registrar', 'Overall'];
    const csvRows = [headers.join(',')];
    rows.forEach((r, i) => {
        csvRows.push([
            i + 1,
            `"${r.studentName}"`,
            r.studentNo,
            `"${r.studentEmail}"`,
            r.chairStatus,
            r.cashierStatus,
            r.registrarStatus,
            r.isCleared ? 'Cleared' : 'Pending',
        ].join(','));
    });
    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `AITSA_Registrar_Report_AY${schoolYear}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

export default function ReportTable({ rows, schoolYear }) {
    const [search, setSearch] = useState('');
    // Defaults to whoever's still awaiting signature — the full clearance
    // list for the current term is already loaded, so there's no reason to
    // make the registrar search or pick a filter just to see who's pending.
    const [status, setStatus] = useState('pending');

    const filtered = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q && status === 'all') return [];

        return rows.filter((r) => {
            const matchesStudent = !q || `${r.studentName} ${r.studentNo} ${r.studentEmail}`.toLowerCase().includes(q);
            const matchesStatus = status === 'all' || (status === 'signed') === r.registrarSigned;
            return matchesStudent && matchesStatus;
        });
    }, [rows, search, status]);

    return (
        <>
            <div className="flex flex-col sm:flex-row sm:items-center gap-3 no-print">
                <div className="relative flex-1 max-w-xs">
                    <i className="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-brandNavy/30 dark:text-slate-500 text-xs" />
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search student name or ID…"
                        className="w-full pl-9 pr-4 py-2 text-sm rounded border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-500 outline-none focus:border-brandGreen/40 transition-colors"
                    />
                </div>
                <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value)}
                    className="px-4 py-2 text-sm rounded border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors"
                >
                    <option value="all">All students</option>
                    <option value="signed">Registrar signed</option>
                    <option value="pending">Awaiting signature</option>
                </select>
                <div className="flex items-center gap-2 sm:ml-auto">
                    <button onClick={() => exportCSV(filtered, schoolYear)} className="ui-btn-primary bg-brandGreen hover:bg-brandNavy text-white transition-colors">
                        <i className="fa-solid fa-file-csv" />Export CSV
                    </button>
                    <button onClick={() => window.print()} className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                        <i className="fa-solid fa-print" />Print report
                    </button>
                </div>
            </div>

            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
                <div className="px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span className="font-heading text-sm font-semibold text-brandNavy dark:text-slate-300">
                        <i className="fa-solid fa-table-list mr-2 text-brandGreen" />Clearance routing status per student
                    </span>
                    <span className="text-xs text-brandNavy/40 dark:text-slate-500">
                        {filtered.length} record{filtered.length !== 1 ? 's' : ''}
                    </span>
                </div>
                <div className="overflow-x-auto">
                    <table className="ui-table">
                        <thead>
                            <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                                <th className="border-brandNavy/8 dark:border-slate-800">#</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Student name</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Student No.</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Program</th>
                                <th className="border-brandNavy/8 dark:border-slate-800 text-center">Dept chair</th>
                                <th className="border-brandNavy/8 dark:border-slate-800 text-center">Cashier</th>
                                <th className="border-brandNavy/8 dark:border-slate-800 text-center">Registrar</th>
                                <th className="border-brandNavy/8 dark:border-slate-800 text-center">Overall</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.length === 0 && (
                                <tr>
                                    <td colSpan={8} className="border-brandNavy/8 dark:border-slate-800 py-10 text-center text-brandNavy/40 dark:text-slate-500">
                                        {!search.trim() && status === 'pending' ? 'No one is awaiting signature.' : 'No matching records.'}
                                    </td>
                                </tr>
                            )}
                            {filtered.map((r, i) => (
                                <tr key={r.id}>
                                    <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/40 dark:text-slate-500">{String(i + 1).padStart(3, '0')}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <p className="font-medium text-brandNavy dark:text-slate-200">{r.studentName}</p>
                                        <p className="text-xs text-brandNavy/40 dark:text-slate-500">{r.studentEmail}</p>
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/60 dark:text-slate-400">{r.studentNo}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/70 dark:text-slate-400">{r.program}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-center"><StatusBadge status={r.chairStatus} /></td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-center"><StatusBadge status={r.cashierStatus} /></td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-center"><StatusBadge status={r.registrarStatus} /></td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                                        {r.isCleared ? (
                                            <span className="ui-badge-outline border-brandGreen text-brandGreen">
                                                <i className="fa-solid fa-circle-check" />Cleared
                                            </span>
                                        ) : (
                                            <span className="ui-badge-outline border-brandGold text-brandGold">
                                                <i className="fa-solid fa-clock" />Pending
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
