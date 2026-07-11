import React from 'react';

const STYLES = {
    pending:  { icon: 'fa-hourglass-half', tone: 'text-amber-600',  label: 'Awaiting Department Chair Approval' },
    enrolled: { icon: 'fa-circle-check',   tone: 'text-brandGreen', label: 'Officially Enrolled' },
    rejected: { icon: 'fa-circle-xmark',   tone: 'text-red-600',    label: 'Returned with Remarks' },
};

export default function StatusCard({ enrollment, onResubmit }) {
    const style = STYLES[enrollment.status];

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <p className={`text-lg font-bold ${style.tone}`}>
                <i className={`fa-solid ${style.icon} mr-2`} />{style.label}
            </p>
            {enrollment.status === 'rejected' && (
                <div className="mt-3">
                    <p className="text-sm text-slate-600 dark:text-slate-300">
                        <span className="font-semibold">Chair remarks:</span> {enrollment.remarks}
                    </p>
                    <button onClick={onResubmit}
                        className="mt-3 px-4 py-2 rounded-lg bg-brandNavy text-white text-sm font-semibold hover:opacity-90">
                        Revise & Resubmit
                    </button>
                </div>
            )}
            {enrollment.sections.length > 0 && (
                <table className="w-full mt-4 text-sm">
                    <thead className="text-left text-xs uppercase text-slate-400">
                        <tr><th className="py-1">Code</th><th>Title</th><th>Units</th><th>Schedule</th><th>Room</th><th>Professor</th></tr>
                    </thead>
                    <tbody>
                        {enrollment.sections.map((s) => (
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
            )}
        </div>
    );
}
