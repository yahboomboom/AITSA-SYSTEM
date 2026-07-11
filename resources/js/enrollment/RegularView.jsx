import React from 'react';

export default function RegularView({ block, submitting, error, onConfirm }) {
    if (!block) {
        return (
            <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
                <p className="text-sm text-slate-500">
                    No block schedule with open seats is available for your program and year level.
                    Please contact the Registrar's Office.
                </p>
            </div>
        );
    }

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-1">
                Your Block Schedule — Block {block.label}
            </h2>
            <p className="text-xs text-slate-500 mb-4">Review your pre-assigned schedule, then confirm your subject load.</p>
            <table className="w-full text-sm">
                <thead className="text-left text-xs uppercase text-slate-400">
                    <tr><th className="py-1">Code</th><th>Title</th><th>Units</th><th>Schedule</th><th>Room</th><th>Professor</th></tr>
                </thead>
                <tbody>
                    {block.sections.map((s) => (
                        <tr key={s.code} className="border-t border-slate-100 dark:border-slate-800">
                            <td className="py-1 font-mono">{s.code}</td>
                            <td>{s.title}</td>
                            <td>{s.units}</td>
                            <td>{s.days.join('/')} {s.start_time}–{s.end_time}</td>
                            <td>{s.room}</td>
                            <td>{s.professor}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
            {error && <p className="mt-3 text-sm text-red-600">{error}</p>}
            <button onClick={onConfirm} disabled={submitting}
                className="mt-4 px-5 py-2.5 rounded-lg bg-brandGreen text-white font-semibold text-sm hover:opacity-90 disabled:opacity-50">
                {submitting ? 'Submitting…' : 'Confirm Subject Load'}
            </button>
        </div>
    );
}
