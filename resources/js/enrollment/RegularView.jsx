import React from 'react';

export default function RegularView({ block, submitting, error, onConfirm }) {
    if (!block) {
        return (
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
                <p className="text-sm text-brandNavy/60 dark:text-slate-400">
                    No block schedule with open seats is available for your program and year level.
                    Please contact the Registrar's Office.
                </p>
            </div>
        );
    }

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h2 className="font-heading text-lg font-semibold text-brandNavy dark:text-slate-100 mb-1">
                Your block schedule — Block {block.label}
            </h2>
            <p className="text-sm text-brandNavy/50 dark:text-slate-400 mb-4">Review your pre-assigned schedule, then confirm your subject load.</p>
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Code</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Title</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Units</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Schedule</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Room</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Professor</th>
                        </tr>
                    </thead>
                    <tbody>
                        {block.sections.map((s) => (
                            <tr key={s.code}>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy dark:text-slate-200">{s.code}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{s.title}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{s.units}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{s.days.join('/')} {s.start_time}–{s.end_time}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{s.room}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{s.professor}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {error && <p className="mt-3 text-sm text-red-600">{error}</p>}
            <button onClick={onConfirm} disabled={submitting}
                className="ui-btn-primary mt-4 bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50">
                {submitting ? 'Submitting…' : 'Confirm subject load'}
            </button>
        </div>
    );
}
