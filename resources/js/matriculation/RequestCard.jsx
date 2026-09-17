import React from 'react';

const STYLES = {
    pending:  { icon: 'fa-hourglass-half', tone: 'text-brandGold',  label: 'Change request awaiting chair approval' },
    approved: { icon: 'fa-circle-check',   tone: 'text-brandGreen', label: 'Change of matriculation approved' },
    rejected: { icon: 'fa-circle-xmark',   tone: 'text-red-600',    label: 'Change request returned' },
};

const ACTION_TONE = { add: 'border-brandGreen text-brandGreen', drop: 'border-red-500 text-red-600', swap: 'border-brandGold text-brandGold' };

export default function RequestCard({ request, windowOpen, onNewRequest }) {
    const style = STYLES[request.status];

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <p className={`font-heading text-lg font-semibold ${style.tone}`}>
                <i className={`fa-solid ${style.icon} mr-2`} />{style.label}
            </p>
            <ul className="mt-3 divide-y divide-brandNavy/8 dark:divide-slate-800 text-sm">
                {request.items.map((item, i) => (
                    <li key={i} className="py-2 flex items-center gap-2">
                        <span className={`ui-badge-outline ${ACTION_TONE[item.action]}`}>{item.action}</span>
                        <span className="text-brandNavy/80 dark:text-slate-300">
                            {item.action === 'swap' && item.replaced_section && (
                                <>Block {item.replaced_section.block_label} → </>
                            )}
                            <span className="font-mono">{item.section.code}</span>{' '}
                            Block {item.section.block_label} · {item.section.days.join('/')} {item.section.start_time}–{item.section.end_time} · {item.section.room}
                        </span>
                    </li>
                ))}
            </ul>
            {request.status === 'rejected' && (
                <div className="mt-3">
                    <p className="text-sm text-brandNavy/70 dark:text-slate-300">
                        <span className="font-medium text-brandNavy dark:text-slate-200">Chair remarks:</span> {request.remarks}
                    </p>
                    {windowOpen && (
                        <button onClick={onNewRequest} className="ui-btn-primary mt-3 bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                            File new request
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
