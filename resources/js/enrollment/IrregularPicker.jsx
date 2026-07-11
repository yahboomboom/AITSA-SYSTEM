import React, { useMemo, useState } from 'react';

function overlaps(a, b) {
    if (!a.days.some((d) => b.days.includes(d))) return false;
    return a.start_time < b.end_time && b.start_time < a.end_time;
}

export default function IrregularPicker({ catalogue, submitting, error, onSubmit }) {
    // subjectId -> section object
    const [picks, setPicks] = useState({});

    const conflict = useMemo(() => {
        const chosen = Object.values(picks);
        for (let i = 0; i < chosen.length; i++) {
            for (let j = i + 1; j < chosen.length; j++) {
                if (overlaps(chosen[i], chosen[j])) return [chosen[i], chosen[j]];
            }
        }
        return null;
    }, [picks]);

    const toggle = (subject, section) => {
        setPicks((prev) => {
            const next = { ...prev };
            if (next[subject.id]?.id === section.id) delete next[subject.id];
            else next[subject.id] = { ...section, code: subject.code };
            return next;
        });
    };

    const years = useMemo(
        () => [...new Set(catalogue.map((s) => s.year_level))].sort(),
        [catalogue]
    );

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-1">Build Your Schedule</h2>
            <p className="text-xs text-slate-500 mb-4">
                Pick one section per subject. Your selection is submitted to the Department Chair for approval.
            </p>

            {years.map((year) => (
                <div key={year} className="mb-6">
                    <h3 className="text-sm font-bold text-slate-500 uppercase mb-2">Year {year}</h3>
                    {catalogue.filter((s) => s.year_level === year).map((subject) => (
                        <div key={subject.id}
                            className={`border rounded-xl p-4 mb-3 ${subject.eligible ? 'border-slate-200 dark:border-slate-700' : 'border-slate-100 dark:border-slate-800 opacity-60'}`}>
                            <div className="flex items-center justify-between flex-wrap gap-2">
                                <p className="font-semibold text-brandNavy dark:text-slate-100">
                                    <span className="font-mono">{subject.code}</span> — {subject.title}
                                    <span className="ml-2 text-xs text-slate-400">{subject.units} units · {subject.mode}</span>
                                </p>
                                {!subject.eligible && (
                                    <span className="text-xs font-semibold text-amber-600">
                                        <i className="fa-solid fa-lock mr-1" />{subject.reason}
                                    </span>
                                )}
                            </div>
                            {subject.eligible && (
                                <div className="flex flex-wrap gap-2 mt-3">
                                    {subject.sections.map((section) => {
                                        const selected = picks[subject.id]?.id === section.id;
                                        const full = section.seats_left <= 0;
                                        return (
                                            <button key={section.id} disabled={full && !selected}
                                                onClick={() => toggle(subject, section)}
                                                className={`px-3 py-2 rounded-lg border text-xs text-left
                                                    ${selected ? 'border-brandGreen bg-brandGreen/10 text-brandGreen font-semibold'
                                                        : full ? 'border-slate-200 text-slate-400 cursor-not-allowed'
                                                        : 'border-slate-300 dark:border-slate-600 hover:border-brandNavy'}`}>
                                                <span className="font-semibold">Block {section.block_label}</span>{' '}
                                                {section.days.join('/')} {section.start_time}–{section.end_time} · {section.room}
                                                <span className="block text-[10px] opacity-70">
                                                    {full ? 'Section full' : `${section.seats_left} seats left`} · {section.professor}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            ))}

            {conflict && (
                <p className="text-sm text-red-600 mb-2">
                    <i className="fa-solid fa-triangle-exclamation mr-1" />
                    Schedule conflict: {conflict[0].code} overlaps with {conflict[1].code}.
                </p>
            )}
            {error && <p className="text-sm text-red-600 mb-2">{error}</p>}

            <button
                disabled={submitting || conflict !== null || Object.keys(picks).length === 0}
                onClick={() => onSubmit(Object.values(picks).map((s) => s.id))}
                className="px-5 py-2.5 rounded-lg bg-brandNavy text-white font-semibold text-sm hover:opacity-90 disabled:opacity-50">
                {submitting ? 'Submitting…' : `Submit ${Object.keys(picks).length} Subject(s) for Approval`}
            </button>
        </div>
    );
}
