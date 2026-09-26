import React, { useState } from 'react';
import api from '../lib/api';
import SectionEditModal from './SectionEditModal';

const EMPTY = { block_label: 'A', days: ['M', 'W'], start_time: '08:00', end_time: '09:30', room: 'TBA', professor: 'TBA', capacity: 40, faculty_id: null, room_id: null, delivery_mode: 'Face-to-Face' };

export default function SectionEditor({ subject, schoolYear, faculty, rooms, onChanged, onListsChanged }) {
    const [draft, setDraft] = useState(null); // null | {..section fields, id?}
    const [removingId, setRemovingId] = useState(null);
    const [error, setError] = useState(null);

    const remove = (section) => {
        setError(null);
        const warning = section.enrolled_count > 0
            ? `Block ${section.block_label} has ${section.enrolled_count} student(s) enrolled. Deleting it removes their schedule for this subject entirely. Delete anyway?`
            : `Delete Block ${section.block_label}? This cannot be undone.`;
        if (!window.confirm(warning)) return;
        setRemovingId(section.id);
        api.delete(`/admin/sections/${section.id}`)
            .then(onChanged)
            .catch((err) => setError(err.response?.data?.message ?? 'Delete failed.'))
            .finally(() => setRemovingId(null));
    };

    return (
        <div className="mt-3 bg-lightBg dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700 rounded-lg p-4">
            <div className="flex items-center justify-between mb-2">
                <h4 className="text-xs font-bold uppercase tracking-wider text-brandNavy/60 dark:text-slate-400">
                    Sections <span className="text-brandNavy/40 dark:text-slate-500 font-normal normal-case">({subject.sections.length})</span>
                </h4>
                <button onClick={() => setDraft({ ...EMPTY, subject_id: subject.id, school_year: schoolYear })}
                    className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white text-xs transition-colors">
                    <i className="fa-solid fa-plus" />Add Section
                </button>
            </div>

            {subject.sections.length === 0 && (
                <p className="text-xs text-slate-400 dark:text-slate-500 py-1">No sections scheduled yet.</p>
            )}

            {subject.sections.map((s) => (
                <div key={s.id} className="flex items-center justify-between text-xs py-1.5 border-b border-slate-200/70 dark:border-slate-800 last:border-b-0">
                    <span>
                        <span className="font-semibold">Block {s.block_label}</span> · {s.days.join('/')} {s.start_time}–{s.end_time} ·{' '}
                        {s.delivery_mode === 'Online' ? 'Online' : (s.room_label ?? s.room)} · {s.faculty_name ?? s.professor}
                        <span className={`ml-1 px-1.5 py-0.5 rounded text-[10px] font-semibold ${s.delivery_mode === 'Online' ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'}`}>
                            {s.delivery_mode === 'Online' ? 'Online' : 'F2F'}
                        </span>
                        <span className="text-slate-400"> · {s.enrolled_count}/{s.capacity} enrolled</span>
                    </span>
                    <span className="flex gap-2 flex-shrink-0 ml-2">
                        <button onClick={() => setDraft({ ...s })} className="text-brandNavy dark:text-slate-300 hover:underline">Edit</button>
                        <button onClick={() => remove(s)} disabled={removingId === s.id}
                            className="text-red-600 hover:underline disabled:opacity-50 disabled:cursor-not-allowed">
                            {removingId === s.id ? 'Deleting…' : 'Delete'}
                        </button>
                    </span>
                </div>
            ))}

            {!draft && error && <p className="text-xs text-red-600 mt-2">{error}</p>}

            {draft && (
                <SectionEditModal
                    draft={draft}
                    setDraft={setDraft}
                    rooms={rooms}
                    faculty={faculty}
                    onClose={() => setDraft(null)}
                    onSaved={onChanged}
                    onListsChanged={onListsChanged}
                />
            )}
        </div>
    );
}
