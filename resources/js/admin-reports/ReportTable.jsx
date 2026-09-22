import { useMemo, useState } from 'react';
import StatusBadge from './StatusBadge';

function exportCSV(rows) {
    const headers = ['#', 'Student Name', 'Student No.', 'Email', 'Chair', 'Cashier', 'Registrar', 'Overall'];
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
    a.download = 'AITSA_Report_AY2025–2026_1stSem.csv';
    a.click();
    URL.revokeObjectURL(url);
}

export default function ReportTable({ rows }) {
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('all');

    const filtered = useMemo(() => {
        const q = search.toLowerCase();
        return rows.filter((r) => {
            const matchesName = r.studentName.toLowerCase().includes(q);
            const matchesStatus = status === 'all' || (status === 'cleared') === r.isCleared;
            return matchesName && matchesStatus;
        });
    }, [rows, search, status]);

    return (
        <>
            <div className="flex flex-col sm:flex-row gap-3 no-print">
                <div className="relative flex-1 max-w-xs">
                    <i className="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-brandNavy/30 dark:text-slate-500 text-xs" />
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search student name..."
                        className="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-500 outline-none focus:border-brandGreen/40 transition-colors"
                    />
                </div>
                <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value)}
                    className="px-4 py-2 text-xs rounded-xl border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors"
                >
                    <option value="all">All Students</option>
                    <option value="cleared">Fully Cleared</option>
                    <option value="pending">Pending Clearance</option>
                </select>
                <div className="flex items-center gap-2 sm:ml-auto">
                    <button onClick={() => exportCSV(filtered)} className="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-brandGreen hover:bg-brandNavy text-white transition-all shadow-sm hover:-translate-y-0.5 active:translate-y-0">
                        <i className="fa-solid fa-file-csv" />Export CSV
                    </button>
                    <button onClick={() => window.print()} className="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-brandNavy hover:bg-brandGreen text-white transition-all shadow-sm hover:-translate-y-0.5 active:translate-y-0">
                        <i className="fa-solid fa-print" />Print Report
                    </button>
                </div>
            </div>

            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        <i className="fa-solid fa-table-list mr-2 text-brandGreen" />Enrollment & Clearance Status
                    </span>
                    <span className="text-[10px] font-bold text-brandNavy/40 dark:text-slate-500">
                        {filtered.length} record{filtered.length !== 1 ? 's' : ''}
                    </span>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead>
                            <tr className="text-[9px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest border-b border-brandNavy/8 dark:border-slate-800">
                                <th className="py-3 px-4">#</th>
                                <th className="py-3 px-4">Student Name</th>
                                <th className="py-3 px-4">Student No.</th>
                                <th className="py-3 px-4">Program</th>
                                <th className="py-3 px-4 text-center">Chair</th>
                                <th className="py-3 px-4 text-center">Cashier</th>
                                <th className="py-3 px-4 text-center">Registrar</th>
                                <th className="py-3 px-4 text-center">Overall</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                            {filtered.length === 0 && (
                                <tr>
                                    <td colSpan={8} className="py-10 text-center text-sm text-brandNavy/40 dark:text-slate-500">
                                        {rows.length === 0 ? 'No student records found.' : 'No matching records.'}
                                    </td>
                                </tr>
                            )}
                            {filtered.map((r, i) => (
                                <tr key={r.id} className="hover:bg-brandNavy/[0.02] dark:hover:bg-slate-800/20 transition-colors">
                                    <td className="py-2.5 px-4 text-[11px] font-mono text-brandNavy/40 dark:text-slate-500">{String(i + 1).padStart(3, '0')}</td>
                                    <td className="py-2.5 px-4">
                                        <p className="text-[11px] font-bold text-brandNavy dark:text-slate-200">{r.studentName}</p>
                                        <p className="text-[9px] text-brandNavy/40 dark:text-slate-500">{r.studentEmail}</p>
                                    </td>
                                    <td className="py-2.5 px-4 text-[11px] font-mono text-brandNavy/60 dark:text-slate-400">{r.studentNo}</td>
                                    <td className="py-2.5 px-4 text-[11px] text-brandNavy/70 dark:text-slate-400">{r.program}</td>
                                    <td className="py-2.5 px-4 text-center"><StatusBadge status={r.chairStatus} /></td>
                                    <td className="py-2.5 px-4 text-center"><StatusBadge status={r.cashierStatus} /></td>
                                    <td className="py-2.5 px-4 text-center"><StatusBadge status={r.registrarStatus} /></td>
                                    <td className="py-2.5 px-4 text-center">
                                        {r.isCleared ? (
                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-black rounded-full bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">
                                                <i className="fa-solid fa-circle-check text-[8px]" />Cleared
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-black rounded-full bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">
                                                <i className="fa-solid fa-clock text-[8px]" />Pending
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
