import React from 'react';

const STYLES = {
    pending:  { icon: 'fa-hourglass-half', tone: 'text-amber-600',  label: 'Change Request Awaiting Chair Approval' },
    approved: { icon: 'fa-circle-check',   tone: 'text-brandGreen', label: 'Change of Matriculation Approved' },
    rejected: { icon: 'fa-circle-xmark',   tone: 'text-red-600',    label: 'Change Request Returned' },
};

const ACTION_TONE = { add: 'text-brandGreen', drop: 'text-red-600', swap: 'text-amber-600' };

export default function RequestCard({ request, windowOpen, onNewRequest }) {
    const style = STYLES[request.status];

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <p className={`text-lg font-bold ${style.tone}`}>
                <i className={`fa-solid ${style.icon} mr-2`} />{style.label}
            </p>
            <ul className="mt-3 space-y-1 text-sm">
                {request.items.map((item, i) => (
                    <li key={i} className="border-t border-slate-100 dark:border-slate-800 pt-1">
                        <span className={`font-bold uppercase text-xs mr-2 ${ACTION_TONE[item.action]}`}>{item.action}</span>
                        {item.action === 'swap' && item.replaced_section && (
                            <>Block {item.replaced_section.block_label} → </>
                        )}
                        <span className="font-mono">{item.section.code}</span>{' '}
                        Block {item.section.block_label} · {item.section.days.join('/')} {item.section.start_time}–{item.section.end_time} · {item.section.room}
                    </li>
                ))}
            </ul>
            {request.status === 'rejected' && (
                <div className="mt-3">
                    <p className="text-sm text-slate-600 dark:text-slate-300">
                        <span className="font-semibold">Chair remarks:</span> {request.remarks}
                    </p>
                    {windowOpen && (
                        <button onClick={onNewRequest}
                            className="mt-3 px-4 py-2 rounded-lg bg-brandNavy text-white text-sm font-semibold hover:opacity-90">
                            File New Request
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
