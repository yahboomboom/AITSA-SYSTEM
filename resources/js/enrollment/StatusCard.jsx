import React from 'react';

const STYLES = {
    pending:  { icon: 'fa-hourglass-half', tone: 'text-brandGold',  label: 'Awaiting department chair approval' },
    enrolled: { icon: 'fa-circle-check',   tone: 'text-brandGreen', label: 'Officially enrolled' },
    rejected: { icon: 'fa-circle-xmark',   tone: 'text-red-600',    label: 'Returned with remarks' },
};

export default function StatusCard({ enrollment, onResubmit, action = null }) {
    const style = STYLES[enrollment.status];

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <p className={`font-heading text-lg font-semibold ${style.tone}`}>
                <i className={`fa-solid ${style.icon} mr-2`} />{style.label}
            </p>
            {action}
            {enrollment.status === 'rejected' && (
                <div className="mt-3">
                    <p className="text-sm text-brandNavy/70 dark:text-slate-300">
                        <span className="font-medium text-brandNavy dark:text-slate-200">Chair remarks:</span> {enrollment.remarks}
                    </p>
                    <button onClick={onResubmit} className="ui-btn-primary mt-3 bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                        Revise and resubmit
                    </button>
                </div>
            )}
            {enrollment.sections.length > 0 && (
                <div className="mt-4 overflow-x-auto">
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
                            {enrollment.sections.map((s) => (
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
            )}
        </div>
    );
}
