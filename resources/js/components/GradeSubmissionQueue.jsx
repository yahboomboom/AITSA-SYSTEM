import { useState } from 'react';

export default function GradeSubmissionQueue({ rows, csrfToken, title = 'Grade Submissions Awaiting Approval', approveLabel = 'Approve' }) {
    const [expandedId, setExpandedId] = useState(null);

    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                <i className="fa-solid fa-clipboard-check mr-2 text-brandGreen" />{title}
            </h2>

            {rows.length === 0 ? (
                <p className="text-sm text-slate-400">No grade submissions waiting.</p>
            ) : (
                rows.map((row) => {
                    const isExpanded = expandedId === row.id;
                    const items = row.items || [];

                    return (
                        <div key={row.id} className="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-4">
                            <div className="flex items-center justify-between flex-wrap gap-3">
                                <div>
                                    <p className="font-semibold text-brandNavy dark:text-slate-100">{row.subjectCode} — Block {row.blockLabel}</p>
                                    <p className="text-xs text-slate-500">{row.subjectTitle} — {row.facultyName} — {row.studentCount} student(s)</p>
                                    {row.missingGrades > 0 && (
                                        <p className="text-xs font-semibold text-amber-600 dark:text-amber-400 mt-1">
                                            <i className="fa-solid fa-triangle-exclamation mr-1" />
                                            {row.missingGrades} enrolled student{row.missingGrades > 1 ? 's' : ''} enrolled since submission, missing a grade
                                        </p>
                                    )}
                                </div>
                                <div className="flex gap-2 items-start">
                                    <button
                                        type="button"
                                        onClick={() => setExpandedId(isExpanded ? null : row.id)}
                                        className="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-brandNavy dark:text-slate-100 text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800"
                                    >
                                        {isExpanded ? 'Hide Grades ▴' : 'View Grades ▾'}
                                    </button>
                                    <form method="POST" action={row.approveUrl}>
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <button className="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">{approveLabel}</button>
                                    </form>
                                    <form method="POST" action={row.rejectUrl} className="flex gap-2">
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <input
                                            name="remarks"
                                            required
                                            maxLength={500}
                                            placeholder="Reason for rejection"
                                            className="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm"
                                        />
                                        <button className="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:opacity-90">Reject</button>
                                    </form>
                                </div>
                            </div>

                            {isExpanded && (
                                <div className="mt-4 border-t border-slate-200 dark:border-slate-700 pt-4">
                                    {items.length === 0 ? (
                                        <p className="text-sm text-slate-400">No grades recorded.</p>
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead>
                                                    <tr className="text-left text-xs uppercase text-slate-400">
                                                        <th className="py-1 pr-4">Student</th>
                                                        <th className="py-1 pr-4">Login ID</th>
                                                        <th className="py-1 pr-4">Grade</th>
                                                        <th className="py-1 pr-4">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {items.map((item, index) => (
                                                        <tr key={index} className="border-t border-slate-100 dark:border-slate-800">
                                                            <td className="py-1 pr-4 text-brandNavy dark:text-slate-100">{item.name}</td>
                                                            <td className="py-1 pr-4 text-slate-500">{item.loginId}</td>
                                                            <td className="py-1 pr-4 text-slate-500">{item.grade}</td>
                                                            <td className="py-1 pr-4 text-slate-500">{item.status}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    );
                })
            )}
        </div>
    );
}
