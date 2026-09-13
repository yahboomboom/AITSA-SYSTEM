import { useState } from 'react';
import { peso } from '../utils/format';

export default function ClearanceQueueTable({ rows, onReview }) {
    const [searchTerm, setSearchTerm] = useState('');

    const term = searchTerm.trim().toLowerCase();
    const filtered = term
        ? rows.filter((row) => (
            row.studentName.toLowerCase().includes(term) ||
            row.studentEmail.toLowerCase().includes(term) ||
            (row.referenceNo ?? '').toLowerCase().includes(term)
        ))
        : rows;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
            <div className="p-5 border-b border-brandNavy/8 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <h2 className="text-sm font-bold text-brandNavy dark:text-white">Clearance Evaluation Queue</h2>

                <div className="relative w-full sm:w-64">
                    <span className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-brandNavy/30 dark:text-slate-500">
                        <i className="fa-solid fa-magnifying-glass text-xs" />
                    </span>
                    <input
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="Search student name or ID..."
                        className="w-full text-xs bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                    />
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr className="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                            <th className="p-4">Student Info</th>
                            <th className="p-4">Reference</th>
                            <th className="p-4">Outstanding Bal</th>
                            <th className="p-4">Clearance</th>
                            <th className="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                        {rows.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">
                                    No students pending clearance.
                                </td>
                            </tr>
                        ) : (
                            filtered.map((row) => (
                                <tr key={row.id} className="hover:bg-lightBg/40 dark:hover:bg-slate-800/20 transition-colors">
                                    <td className="p-4">
                                        <div className="font-bold text-brandNavy dark:text-white">{row.studentName}</div>
                                        <div className="text-[10px] text-brandNavy/40 dark:text-slate-500">{row.studentEmail}</div>
                                    </td>
                                    <td className="p-4 font-mono text-brandNavy/40 dark:text-slate-500">
                                        {row.referenceNo ?? '—'}
                                    </td>
                                    <td className="p-4 font-bold text-brandGold">
                                        {peso(row.balance)}
                                    </td>
                                    <td className="p-4">
                                        <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGold/10 text-brandGold border border-brandGold/20 rounded">Pending</span>
                                    </td>
                                    <td className="p-4 text-right">
                                        <button
                                            onClick={() => onReview(row)}
                                            className="px-3 py-1.5 bg-brandNavy hover:bg-brandGreen text-white rounded transition-colors font-bold text-[11px]"
                                        >
                                            Review
                                        </button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}