import React, { useEffect } from 'react';

// Pop-up card listing one subject's sections. Picking a section closes it;
// sections that clash with another pick, or are full, are shown locked.
export default function SectionPickerModal({ subject, picked, clashFor, onPick, onRemove, onClose }) {
    useEffect(() => {
        const onKey = (e) => { if (e.key === 'Escape') onClose(); };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [onClose]);

    return (
        <div
            className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div role="dialog" aria-modal="true" aria-labelledby="section-picker-title"
                className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-start justify-between gap-3">
                    <div>
                        <p id="section-picker-title" className="font-heading text-base font-semibold text-brandNavy dark:text-white">
                            <span className="font-mono">{subject.code}</span> — {subject.title}
                        </p>
                        <p className="text-xs text-brandNavy/50 dark:text-slate-400 mt-1">
                            Year {subject.year_level} · {subject.units} units · {subject.mode}
                        </p>
                        <p className="text-xs mt-1">
                            {subject.prerequisites?.length ? (
                                <span className="text-brandGreen">
                                    <i className="fa-solid fa-circle-check mr-1" />Prerequisite: {subject.prerequisites.join(', ')} — passed
                                </span>
                            ) : (
                                <span className="text-brandNavy/40 dark:text-slate-500">No prerequisite</span>
                            )}
                        </p>
                    </div>
                    <button type="button" onClick={onClose} aria-label="Close"
                        className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>

                <div className="p-5 space-y-2 overflow-y-auto">
                    <p className="text-xs font-semibold text-brandNavy/60 dark:text-slate-400 mb-1">Choose a section</p>
                    {subject.sections.length === 0 && (
                        <p className="text-sm text-brandNavy/50 dark:text-slate-400">No sections are scheduled for this subject yet.</p>
                    )}
                    {subject.sections.map((section) => {
                        const selected = picked?.id === section.id;
                        const full = section.seats_left <= 0;
                        const clash = selected ? null : clashFor(subject.id, section);
                        const locked = !selected && (full || clash);
                        const online = section.delivery_mode === 'Online';

                        return (
                            <button key={section.id} type="button" disabled={locked}
                                onClick={() => onPick(subject, section)}
                                className={`w-full text-left rounded-lg border p-3 transition-colors flex items-center justify-between gap-3
                                    ${selected ? 'border-brandGreen bg-brandGreen/10'
                                        : clash ? 'border-dashed border-red-300 dark:border-red-500/40 bg-red-500/5 cursor-not-allowed'
                                        : full ? 'border-brandNavy/10 dark:border-slate-800 opacity-60 cursor-not-allowed'
                                        : 'border-brandNavy/15 dark:border-slate-700 hover:border-brandGreen hover:bg-brandGreen/5'}`}>
                                <span className={locked ? 'text-brandNavy/40 dark:text-slate-500' : 'text-brandNavy dark:text-slate-100'}>
                                    <span className="block text-sm font-semibold">
                                        Block {section.block_label} · {section.days.join('/')} {section.start_time}–{section.end_time}
                                    </span>
                                    <span className="block text-xs opacity-70 mt-0.5">
                                        {online ? 'Online class' : `${section.room} · Face-to-Face`} · {section.professor}
                                    </span>
                                    {clash && (
                                        <span className="block text-xs font-semibold text-red-600 mt-1">
                                            <i className="fa-solid fa-ban mr-1" />
                                            Conflicts with {clash.code} ({clash.days.join('/')} {clash.start_time}–{clash.end_time})
                                        </span>
                                    )}
                                </span>
                                <span className="text-xs font-semibold whitespace-nowrap">
                                    {selected ? (
                                        <span className="text-brandGreen"><i className="fa-solid fa-circle-check mr-1" />Selected</span>
                                    ) : full ? (
                                        <span className="text-brandNavy/40 dark:text-slate-500">Section full</span>
                                    ) : (
                                        <span className={clash ? 'text-brandNavy/30 dark:text-slate-600' : 'text-brandNavy/50 dark:text-slate-400'}>
                                            {section.seats_left} seats left
                                        </span>
                                    )}
                                </span>
                            </button>
                        );
                    })}
                </div>

                <div className="p-4 border-t border-brandNavy/10 dark:border-slate-800 flex gap-2">
                    {picked && (
                        <button type="button" onClick={() => onRemove(subject)}
                            className="ui-btn-primary flex-1 justify-center bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors">
                            Remove selection
                        </button>
                    )}
                    <button type="button" onClick={onClose}
                        className="ui-btn-primary flex-1 justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    );
}
