import React, { useState } from 'react';
import api from '../lib/api';
import SectionEditor from './SectionEditor';

export default function SubjectRow({ subject, allSubjects, schoolYear, onChanged }) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState(null);
    const [error, setError] = useState(null);

    const startEdit = () => {
        setDraft({
            code: subject.code, title: subject.title, units: subject.units,
            year_level: subject.year_level, semester: subject.semester, mode: subject.mode,
            prerequisite_ids: subject.prerequisite_ids ?? [],
        });
        setEditing(true);
    };

    const save = () => {
        setError(null);
        api.put(`/admin/subjects/${subject.id}`, { ...draft, units: Number(draft.units) })
            .then(() => { setEditing(false); onChanged(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Check the fields and try again.'));
    };

    const remove = () => {
        setError(null);
        api.delete(`/admin/subjects/${subject.id}`)
            .then(onChanged)
            .catch((err) => setError(err.response?.data?.message ?? 'Delete failed.'));
    };

    const prereqCodes = (subject.prerequisite_ids ?? [])
        .map((id) => allSubjects.find((s) => s.id === id)?.code)
        .filter(Boolean);

    return (
        <div className="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-3">
            <div className="flex items-center justify-between flex-wrap gap-2">
                {editing ? (
                    <div className="flex flex-wrap gap-2 text-xs w-full">
                        <input value={draft.code} onChange={(e) => setDraft({ ...draft, code: e.target.value })}
                            className="w-24 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input value={draft.title} onChange={(e) => setDraft({ ...draft, title: e.target.value })}
                            className="flex-1 min-w-48 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input type="number" value={draft.units} onChange={(e) => setDraft({ ...draft, units: e.target.value })}
                            className="w-16 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <select value={draft.mode} onChange={(e) => setDraft({ ...draft, mode: e.target.value })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                            <option>F2F</option><option>Online</option>
                        </select>
                        <select multiple value={draft.prerequisite_ids.map(String)}
                            onChange={(e) => setDraft({ ...draft, prerequisite_ids: [...e.target.selectedOptions].map((o) => Number(o.value)) })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800 min-w-40" size={3}>
                            {allSubjects.filter((s) => s.id !== subject.id).map((s) => (
                                <option key={s.id} value={s.id}>{s.code} (prereq)</option>
                            ))}
                        </select>
                        <button onClick={save} className="px-3 py-1.5 rounded bg-brandGreen text-white font-semibold">Save</button>
                        <button onClick={() => setEditing(false)} className="px-3 py-1.5 rounded bg-slate-200 dark:bg-slate-700">Cancel</button>
                        {error && <p className="w-full text-red-600">{error}</p>}
                    </div>
                ) : (
                    <>
                        <p className="text-sm font-semibold text-brandNavy dark:text-slate-100">
                            <span className="font-mono">{subject.code}</span> — {subject.title}
                            <span className="ml-2 text-xs text-slate-400">
                                {subject.units} units · {subject.mode}
                                {prereqCodes.length > 0 && <> · requires {prereqCodes.join(', ')}</>}
                            </span>
                        </p>
                        <span className="flex gap-3 text-xs">
                            <button onClick={() => setOpen(!open)} className="text-brandNavy dark:text-slate-300 hover:underline">
                                {open ? 'Hide' : 'Show'} sections ({subject.sections.length})
                            </button>
                            <button onClick={startEdit} className="text-brandNavy dark:text-slate-300 hover:underline">Edit</button>
                            <button onClick={remove} className="text-red-600 hover:underline">Delete</button>
                        </span>
                        {error && <p className="w-full text-xs text-red-600">{error}</p>}
                    </>
                )}
            </div>
            {open && <SectionEditor subject={subject} schoolYear={schoolYear} onChanged={onChanged} />}
        </div>
    );
}
