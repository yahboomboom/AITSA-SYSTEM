import React, { useCallback, useMemo, useState } from 'react';
import SectionPickerModal from './SectionPickerModal';

function overlaps(a, b) {
    if (!a.days.some((d) => b.days.includes(d))) return false;
    return a.start_time < b.end_time && b.start_time < a.end_time;
}

export default function IrregularPicker({ catalogue, maxUnits, submitting, error, onSubmit }) {
    // subjectId -> section object
    const [picks, setPicks] = useState({});
    // subject whose section pop-up is open
    const [activeSubject, setActiveSubject] = useState(null);

    const conflict = useMemo(() => {
        const chosen = Object.values(picks);
        for (let i = 0; i < chosen.length; i++) {
            for (let j = i + 1; j < chosen.length; j++) {
                if (overlaps(chosen[i], chosen[j])) return [chosen[i], chosen[j]];
            }
        }
        return null;
    }, [picks]);

    const units = useMemo(
        () => Object.keys(picks).reduce((sum, id) => sum + (catalogue.find((s) => String(s.id) === id)?.units ?? 0), 0),
        [picks, catalogue]
    );
    const overCap = units > maxUnits;

    // The already-picked section (of another subject) that this section
    // would clash with, or null. Used to lock clashing sections up front.
    const clashFor = (subjectId, section) => {
        for (const [pickedSubjectId, picked] of Object.entries(picks)) {
            if (pickedSubjectId === String(subjectId)) continue;
            if (overlaps(picked, section)) return picked;
        }
        return null;
    };

    const pick = (subject, section) => {
        setPicks((prev) => ({ ...prev, [subject.id]: { ...section, code: subject.code } }));
        setActiveSubject(null);
    };

    const remove = (subject) => {
        setPicks((prev) => {
            const next = { ...prev };
            delete next[subject.id];
            return next;
        });
        setActiveSubject(null);
    };

    const closeModal = useCallback(() => setActiveSubject(null), []);

    // Only subjects the student can actually take: missing prerequisites and
    // already-passed subjects are left out entirely.
    const available = useMemo(() => catalogue.filter((s) => s.eligible), [catalogue]);

    const years = useMemo(
        () => [...new Set(available.map((s) => s.year_level))].sort(),
        [available]
    );

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h2 className="font-heading text-lg font-semibold text-brandNavy dark:text-slate-100 mb-1">Build your schedule</h2>
            <p className="text-sm text-brandNavy/50 dark:text-slate-400 mb-4">
                Click a subject to choose its section. You can take subjects from any year once you've passed their prerequisites.
                Your selection is submitted to the Department Chair for approval.
            </p>
            <p className={`text-sm font-semibold mb-4 ${overCap ? 'text-red-600' : 'text-brandNavy dark:text-slate-200'}`}>
                <i className="fa-solid fa-scale-balanced mr-1" />
                {units} / {maxUnits} units
                {overCap && <span className="font-normal"> — remove a subject; the maximum is {maxUnits} units per term.</span>}
            </p>

            {available.length === 0 && (
                <p className="text-sm text-brandNavy/60 dark:text-slate-400 border border-dashed border-brandNavy/15 dark:border-slate-700 rounded p-4 mb-4">
                    <i className="fa-solid fa-circle-info mr-1" />
                    No subjects are open to you this semester. Please contact the Registrar or your Department Chair.
                </p>
            )}

            {years.map((year) => (
                <div key={year} className="mb-6">
                    <h3 className="text-sm font-medium text-brandNavy/60 dark:text-slate-400 mb-2">Year {year}</h3>
                    {available.filter((s) => s.year_level === year).map((subject) => {
                        const picked = picks[subject.id];
                        const allBlocked = !picked && subject.sections.length > 0
                            && subject.sections.every((section) => section.seats_left <= 0 || clashFor(subject.id, section));

                        return (
                            <button key={subject.id} type="button" onClick={() => setActiveSubject(subject)}
                                className={`w-full text-left border rounded-lg p-4 mb-3 flex items-center justify-between flex-wrap gap-3 transition-colors
                                    ${picked ? 'border-brandGreen/50 bg-brandGreen/5' : 'border-brandNavy/10 dark:border-slate-700 hover:border-brandNavy/30 dark:hover:border-slate-500'}`}>
                                <span className="font-medium text-brandNavy dark:text-slate-100">
                                    <span className="font-mono">{subject.code}</span> — {subject.title}
                                    <span className="ml-2 text-xs text-brandNavy/40 dark:text-slate-500">{subject.units} units · {subject.mode}</span>
                                    <span className="block text-xs font-normal mt-0.5">
                                        {subject.prerequisites?.length ? (
                                            <span className="text-brandGreen">
                                                <i className="fa-solid fa-circle-check mr-1" />
                                                Prerequisite: {subject.prerequisites.join(', ')} — passed
                                            </span>
                                        ) : (
                                            <span className="text-brandNavy/40 dark:text-slate-500">No prerequisite</span>
                                        )}
                                    </span>
                                </span>
                                <span className="text-xs font-semibold whitespace-nowrap">
                                    {picked ? (
                                        <span className="text-brandGreen">
                                            <i className="fa-solid fa-circle-check mr-1" />
                                            Block {picked.block_label} · {picked.days.join('/')} {picked.start_time}–{picked.end_time}
                                            <span className="ml-2 underline font-normal text-brandNavy/50 dark:text-slate-400">Change</span>
                                        </span>
                                    ) : allBlocked ? (
                                        <span className="text-red-600">
                                            <i className="fa-solid fa-triangle-exclamation mr-1" />All sections conflict with your schedule
                                        </span>
                                    ) : (
                                        <span className="text-brandNavy/60 dark:text-slate-300">
                                            Choose section <i className="fa-solid fa-chevron-right ml-1 text-[10px]" />
                                        </span>
                                    )}
                                </span>
                            </button>
                        );
                    })}
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
                disabled={submitting || conflict !== null || overCap || Object.keys(picks).length === 0}
                onClick={() => onSubmit(Object.values(picks).map((s) => s.id))}
                className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors disabled:opacity-50">
                {submitting ? 'Submitting…' : `Submit ${Object.keys(picks).length} subject(s) for approval`}
            </button>

            {activeSubject && (
                <SectionPickerModal
                    subject={activeSubject}
                    picked={picks[activeSubject.id]}
                    clashFor={clashFor}
                    onPick={pick}
                    onRemove={remove}
                    onClose={closeModal}
                />
            )}
        </div>
    );
}
