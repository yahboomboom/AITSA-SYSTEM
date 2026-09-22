import React, { useState } from 'react';
import api from '../lib/api';

export default function SubjectRow({ subject, allSubjects, onChanged, onEdit }) {
    const [error, setError] = useState(null);
    const [removing, setRemoving] = useState(false);

    const remove = () => {
        setError(null);
        const sectionCount = subject.sections.length;
        const warning = sectionCount > 0
            ? `Delete ${subject.code} — ${subject.title}? This also deletes its ${sectionCount} section(s) and cannot be undone.`
            : `Delete ${subject.code} — ${subject.title}? This cannot be undone.`;
        if (!window.confirm(warning)) return;
        setRemoving(true);
        api.delete(`/admin/subjects/${subject.id}`)
            .then(onChanged)
            .catch((err) => { setError(err.response?.data?.message ?? 'Delete failed.'); setRemoving(false); });
    };

    const prereqCodes = (subject.prerequisite_ids ?? [])
        .map((id) => allSubjects.find((s) => s.id === id)?.code)
        .filter(Boolean);

    return (
        <div className="border border-brandNavy/10 dark:border-slate-700 rounded-lg p-4 mb-3">
            <div className="flex items-center justify-between flex-wrap gap-2">
                <p className="text-sm font-medium text-brandNavy dark:text-slate-100">
                    <span className="font-mono">{subject.code}</span> — {subject.title}
                    <span className="ml-2 text-xs text-brandNavy/40 dark:text-slate-500">
                        {subject.units} units · {subject.mode}
                        {prereqCodes.length > 0 && <> · requires {prereqCodes.join(', ')}</>}
                    </span>
                </p>
                <span className="flex items-center gap-3 text-xs">
                    <span className="text-brandNavy/40 dark:text-slate-500">{subject.sections.length} section(s) — scheduled by the Dept Chair</span>
                    <button onClick={() => onEdit(subject)} className="text-brandNavy dark:text-slate-300 hover:underline font-medium">Edit</button>
                    <button onClick={remove} disabled={removing} className="text-red-600 hover:underline font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        {removing ? 'Deleting…' : 'Delete'}
                    </button>
                </span>
            </div>
            {error && <p className="w-full text-xs text-red-600 bg-red-500/10 border border-red-500/20 rounded px-3 py-2 mt-2">{error}</p>}
        </div>
    );
}
