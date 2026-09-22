import React, { useMemo, useState } from 'react';

function overlaps(a, b) {
    if (!a.days.some((d) => b.days.includes(d))) return false;
    return a.start_time < b.end_time && b.start_time < a.end_time;
}

export default function ChangeBuilder({ current, catalogue, submitting, error, onSubmit, onCancel }) {
    const [drops, setDrops] = useState({});  // current section id -> true
    const [swaps, setSwaps] = useState({});  // replaced section id -> target section (with code)
    const [adds, setAdds] = useState({});    // subject id -> target section (with code)

    const items = useMemo(() => [
        ...Object.keys(drops).map((id) => ({ action: 'drop', section_id: Number(id) })),
        ...Object.entries(swaps).map(([replacedId, s]) => ({ action: 'swap', section_id: s.id, replaced_section_id: Number(replacedId) })),
        ...Object.values(adds).map((s) => ({ action: 'add', section_id: s.id })),
    ], [drops, swaps, adds]);

    const resulting = useMemo(() => {
        const kept = current.filter((s) => !drops[s.id] && !swaps[s.id]);
        return [...kept, ...Object.values(swaps), ...Object.values(adds)];
    }, [current, drops, swaps, adds]);

    const conflict = useMemo(() => {
        for (let i = 0; i < resulting.length; i++) {
            for (let j = i + 1; j < resulting.length; j++) {
                if (overlaps(resulting[i], resulting[j])) return [resulting[i], resulting[j]];
            }
        }
        return null;
    }, [resulting]);

    const toggleDrop = (section) => {
        setDrops((prev) => {
            const next = { ...prev };
            if (next[section.id]) delete next[section.id];
            else { next[section.id] = true; }
            return next;
        });
        setSwaps((prev) => { const next = { ...prev }; delete next[section.id]; return next; });
    };

    const toggleSwap = (currentSection, target, code) => {
        setSwaps((prev) => {
            const next = { ...prev };
            if (next[currentSection.id]?.id === target.id) delete next[currentSection.id];
            else next[currentSection.id] = { ...target, code };
            return next;
        });
        setDrops((prev) => { const next = { ...prev }; delete next[currentSection.id]; return next; });
    };

    const toggleAdd = (subject, section) => {
        setAdds((prev) => {
            const next = { ...prev };
            if (next[subject.id]?.id === section.id) delete next[subject.id];
            else next[subject.id] = { ...section, code: subject.code };
            return next;
        });
    };

    const currentSubjectIds = new Set(current.map((s) => s.subject_id));
    const addable = catalogue.filter((subj) => subj.eligible && !currentSubjectIds.has(subj.id));

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h2 className="font-heading text-lg font-semibold text-brandNavy dark:text-slate-100 mb-1">Change of matriculation</h2>
            <p className="text-sm text-brandNavy/50 dark:text-slate-400 mb-4">
                Drop or swap your current sections, or add new subjects. Your request is submitted to the Department Chair for approval.
            </p>

            <h3 className="text-sm font-medium text-brandNavy/60 dark:text-slate-400 mb-2">Current subjects</h3>
            {current.map((section) => {
                const subject = catalogue.find((s) => s.id === section.subject_id);
                const alternatives = (subject?.sections ?? []).filter((s) => s.id !== section.id);
                return (
                    <div key={section.id}
                        className={`border rounded p-4 mb-3 ${drops[section.id] ? 'border-red-300 bg-red-50/50 dark:bg-red-900/10' : 'border-brandNavy/10 dark:border-slate-700'}`}>
                        <div className="flex items-center justify-between flex-wrap gap-2">
                            <p className="font-medium text-brandNavy dark:text-slate-100">
                                <span className="font-mono">{section.code}</span> — {section.title}
                                <span className="ml-2 text-xs text-brandNavy/40 dark:text-slate-500">
                                    Block {section.block_label} · {section.days.join('/')} {section.start_time}–{section.end_time} · {section.room}
                                </span>
                            </p>
                            <button onClick={() => toggleDrop(section)}
                                className={`ui-badge-outline ${drops[section.id] ? 'border-red-500 text-red-600' : 'border-brandNavy/20 dark:border-slate-600 text-red-600 hover:border-red-400'}`}>
                                {drops[section.id] ? 'Undo drop' : 'Drop'}
                            </button>
                        </div>
                        {alternatives.length > 0 && !drops[section.id] && (
                            <div className="flex flex-wrap items-center gap-2 mt-3">
                                <span className="text-xs text-brandNavy/40 dark:text-slate-500">Swap to:</span>
                                {alternatives.map((alt) => {
                                    const selected = swaps[section.id]?.id === alt.id;
                                    const full = alt.seats_left <= 0;
                                    return (
                                        <button key={alt.id} disabled={full && !selected}
                                            onClick={() => toggleSwap(section, alt, section.code)}
                                            className={`px-3 py-2 rounded border text-xs text-left
                                                ${selected ? 'border-brandGold bg-brandGold/10 text-brandGold font-medium'
                                                    : full ? 'border-brandNavy/10 text-brandNavy/40 cursor-not-allowed'
                                                    : 'border-brandNavy/20 dark:border-slate-600 hover:border-brandNavy dark:hover:border-slate-400'}`}>
                                            <span className="font-semibold">Block {alt.block_label}</span>{' '}
                                            {alt.days.join('/')} {alt.start_time}–{alt.end_time} · {alt.room}
                                            <span className="block text-[10px] opacity-70">
                                                {full ? 'Section full' : `${alt.seats_left} seats left`} · {alt.professor}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                );
            })}

            {addable.length > 0 && (
                <>
                    <h3 className="text-sm font-medium text-brandNavy/60 dark:text-slate-400 mb-2 mt-6">Add a subject</h3>
                    {addable.map((subject) => (
                        <div key={subject.id} className="border border-brandNavy/10 dark:border-slate-700 rounded p-4 mb-3">
                            <p className="font-medium text-brandNavy dark:text-slate-100">
                                <span className="font-mono">{subject.code}</span> — {subject.title}
                                <span className="ml-2 text-xs text-brandNavy/40 dark:text-slate-500">{subject.units} units · {subject.mode}</span>
                            </p>
                            <div className="flex flex-wrap gap-2 mt-3">
                                {subject.sections.map((section) => {
                                    const selected = adds[subject.id]?.id === section.id;
                                    const full = section.seats_left <= 0;
                                    return (
                                        <button key={section.id} disabled={full && !selected}
                                            onClick={() => toggleAdd(subject, section)}
                                            className={`px-3 py-2 rounded border text-xs text-left
                                                ${selected ? 'border-brandGreen bg-brandGreen/10 text-brandGreen font-medium'
                                                    : full ? 'border-brandNavy/10 text-brandNavy/40 cursor-not-allowed'
                                                    : 'border-brandNavy/20 dark:border-slate-600 hover:border-brandNavy dark:hover:border-slate-400'}`}>
                                            <span className="font-semibold">Block {section.block_label}</span>{' '}
                                            {section.days.join('/')} {section.start_time}–{section.end_time} · {section.room}
                                            <span className="block text-[10px] opacity-70">
                                                {full ? 'Section full' : `${section.seats_left} seats left`} · {section.professor}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </>
            )}

            {conflict && (
                <p className="text-sm text-red-600 mb-2 mt-2">
                    <i className="fa-solid fa-triangle-exclamation mr-1" />
                    Schedule conflict: {conflict[0].code} overlaps with {conflict[1].code}.
                </p>
            )}
            {resulting.length === 0 && (
                <p className="text-sm text-red-600 mb-2 mt-2">You must keep at least one subject.</p>
            )}
            {error && <p className="text-sm text-red-600 mb-2 mt-2">{error}</p>}

            <div className="flex gap-2 mt-4">
                <button
                    disabled={submitting || items.length === 0 || conflict !== null || resulting.length === 0}
                    onClick={() => onSubmit(items)}
                    className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors disabled:opacity-50">
                    {submitting ? 'Submitting…' : `Submit ${items.length} change(s) for approval`}
                </button>
                <button onClick={onCancel}
                    className="ui-btn-primary bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy dark:text-slate-300 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    );
}
