import { useState } from 'react';
import { peso } from '../utils/format';

export default function ClearanceQueueTable({ rows, onReview }) {
    const [searchTerm, setSearchTerm] = useState('');

    const term = searchTerm.trim().toLowerCase();
    // Approved clearances are done — they'd just clutter the queue of
    // students still needing review. Searching can still surface them
    // (e.g. to double-check a settled account), just not by default.
    const filtered = term
        ? rows.filter((row) => (
            row.studentName.toLowerCase().includes(term) ||
            row.studentEmail.toLowerCase().includes(term) ||
            (row.referenceNo ?? '').toLowerCase().includes(term)
        ))
        : rows.filter((row) => !row.isApproved);

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Clearance evaluation queue</h2>

                <div className="relative w-full sm:w-64">
                    <span className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-brandNavy/30 dark:text-slate-500">
                        <i className="fa-solid fa-magnifying-glass text-xs" />
                    </span>
                    <input
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="Search student name or ID…"
                        className="w-full text-sm bg-lightBg dark:bg-slate-900 text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                    />
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student info</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Reference</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Outstanding bal.</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Clearance</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filtered.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="border-brandNavy/8 dark:border-slate-800 p-8 text-center text-brandNavy/40 dark:text-slate-500">
                                    {term ? 'No matching students.' : 'No students pending clearance.'}
                                </td>
                            </tr>
                        ) : (
                            filtered.map((row) => (
                                <tr key={row.id}>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <div className="font-medium text-brandNavy dark:text-white">{row.studentName}</div>
                                        <div className="text-xs text-brandNavy/40 dark:text-slate-500">{row.studentEmail}</div>
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/40 dark:text-slate-500">
                                        {row.referenceNo ?? '—'}
                                    </td>
                                    <td className={`border-brandNavy/8 dark:border-slate-800 font-medium ${row.isApproved ? 'text-brandGreen' : 'text-brandGold'}`}>
                                        {peso(row.balance)}
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        {row.isApproved ? (
                                            <span className="ui-badge-outline border-brandGreen text-brandGreen">Approved</span>
                                        ) : row.isHeld ? (
                                            <span className="ui-badge-outline border-red-600 text-red-600">Hold</span>
                                        ) : (row.isDownPaymentMet || row.isDownPaymentWaived) ? (
                                            <span className="ui-badge-outline border-cyan-600 text-cyan-600">Enrollable</span>
                                        ) : (
                                            <span className="ui-badge-outline border-brandGold text-brandGold">Pending</span>
                                        )}
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-right">
                                        {row.isApproved ? (
                                            <span className="ui-badge-outline border-brandNavy/10 text-brandNavy/30 dark:border-slate-700 dark:text-slate-600">
                                                <i className="fa-solid fa-check" />Settled
                                            </span>
                                        ) : (
                                            <button onClick={() => onReview(row)} className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                                                Review
                                            </button>
                                        )}
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
