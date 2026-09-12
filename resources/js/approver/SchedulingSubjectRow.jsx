import React, { useState } from 'react';
import SectionEditor from '../curriculum/SectionEditor';

// Read-only counterpart to curriculum/SubjectRow.jsx — the Dept Chair
// schedules sections but doesn't own subject content (code/title/units/
// prerequisites stay Registrar-only), so this renders subject details as
// plain text with no edit/delete affordance.
export default function SchedulingSubjectRow({ subject, allSubjects, schoolYear, faculty, rooms, onChanged, onListsChanged }) {
    const [open, setOpen] = useState(false);

    const prereqCodes = (subject.prerequisite_ids ?? [])
        .map((id) => allSubjects.find((s) => s.id === id)?.code)
        .filter(Boolean);

    return (
        <div className="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-3">
            <div className="flex items-center justify-between flex-wrap gap-2">
                <p className="text-sm font-semibold text-brandNavy dark:text-slate-100">
                    <span className="font-mono">{subject.code}</span> — {subject.title}
                    <span className="ml-2 text-xs text-slate-400">
                        {subject.units} units · {subject.mode}
                        {prereqCodes.length > 0 && <> · requires {prereqCodes.join(', ')}</>}
                    </span>
                </p>
                <button onClick={() => setOpen(!open)} className="text-xs text-brandNavy dark:text-slate-300 hover:underline">
                    {open ? 'Hide' : 'Show'} sections ({subject.sections.length})
                </button>
            </div>
            {open && <SectionEditor subject={subject} schoolYear={schoolYear} faculty={faculty} rooms={rooms}
                onChanged={onChanged} onListsChanged={onListsChanged} />}
        </div>
    );
}
