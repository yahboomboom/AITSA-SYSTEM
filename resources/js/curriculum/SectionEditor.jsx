import React, { useState } from 'react';
import api from '../lib/api';

const EMPTY = { block_label: 'A', days: ['M', 'W'], start_time: '08:00', end_time: '09:30', room: 'TBA', professor: 'TBA', capacity: 40, faculty_id: null, room_id: null };
const DAY_OPTIONS = ['M', 'T', 'W', 'Th', 'F', 'Sat', 'Sun'];

export default function SectionEditor({ subject, schoolYear, faculty, rooms, onChanged, onListsChanged }) {
    const [draft, setDraft] = useState(null); // null | {..section fields, id?}
    const [error, setError] = useState(null);
    const [newFaculty, setNewFaculty] = useState(null); // null | {name, login_id}
    const [newRoom, setNewRoom] = useState(null); // null | {name, type}

    const save = () => {
        setError(null);
        const payload = {
            ...draft, subject_id: subject.id, school_year: schoolYear, capacity: Number(draft.capacity),
            faculty_id: draft.faculty_id ? Number(draft.faculty_id) : null,
            room_id: draft.room_id ? Number(draft.room_id) : null,
        };
        const req = draft.id ? api.put(`/admin/sections/${draft.id}`, payload) : api.post('/admin/sections', payload);
        req.then(() => { setDraft(null); onChanged(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Check the section fields and try again.'));
    };

    const remove = (id) => {
        setError(null);
        api.delete(`/admin/sections/${id}`)
            .then(onChanged)
            .catch((err) => setError(err.response?.data?.message ?? 'Delete failed.'));
    };

    const addFaculty = () => {
        setError(null);
        api.post('/admin/faculty', newFaculty)
            .then((res) => {
                setNewFaculty(null);
                setDraft((d) => ({ ...d, faculty_id: res.data.faculty.id }));
                onListsChanged();
            })
            .catch((err) => setError(err.response?.data?.message ?? 'Could not add faculty.'));
    };

    const addRoom = () => {
        setError(null);
        api.post('/admin/rooms', newRoom)
            .then((res) => {
                setNewRoom(null);
                setDraft((d) => ({ ...d, room_id: res.data.room.id }));
                onListsChanged();
            })
            .catch((err) => setError(err.response?.data?.message ?? 'Could not add room.'));
    };

    const toggleDay = (day) => setDraft((d) => ({
        ...d,
        days: d.days.includes(day) ? d.days.filter((x) => x !== day) : [...d.days, day],
    }));

    return (
        <div className="mt-3 pl-4 border-l-2 border-slate-100 dark:border-slate-800">
            {subject.sections.map((s) => (
                <div key={s.id} className="flex items-center justify-between text-xs py-1.5 border-b border-slate-50 dark:border-slate-800">
                    <span>
                        <span className="font-semibold">Block {s.block_label}</span> · {s.days.join('/')} {s.start_time}–{s.end_time} · {s.room_label ?? s.room} · {s.faculty_name ?? s.professor}
                        <span className="text-slate-400"> · {s.enrolled_count}/{s.capacity} enrolled</span>
                    </span>
                    <span className="flex gap-2">
                        <button onClick={() => setDraft({ ...s })} className="text-brandNavy dark:text-slate-300 hover:underline">Edit</button>
                        <button onClick={() => remove(s.id)} className="text-red-600 hover:underline">Delete</button>
                    </span>
                </div>
            ))}

            {draft ? (
                <div className="mt-2 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 space-y-2 text-xs">
                    <div className="flex flex-wrap gap-2">
                        <input value={draft.block_label} onChange={(e) => setDraft({ ...draft, block_label: e.target.value })}
                            placeholder="Block" className="w-16 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input type="time" value={draft.start_time} onChange={(e) => setDraft({ ...draft, start_time: e.target.value })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input type="time" value={draft.end_time} onChange={(e) => setDraft({ ...draft, end_time: e.target.value })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        <input type="number" value={draft.capacity} onChange={(e) => setDraft({ ...draft, capacity: e.target.value })}
                            placeholder="Cap" className="w-16 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                    </div>
                    <div className="flex flex-wrap gap-2 items-center">
                        <select value={draft.room_id ?? ''} onChange={(e) => setDraft({ ...draft, room_id: e.target.value || null })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                            <option value="">— room unassigned —</option>
                            {rooms.map((r) => <option key={r.id} value={r.id}>{r.name}{r.type === 'virtual' ? ' (online)' : ''}</option>)}
                        </select>
                        <button onClick={() => setNewRoom(newRoom ? null : { name: '', type: 'physical' })}
                            className="text-brandGreen font-semibold hover:underline">+ Add room</button>
                        <select value={draft.faculty_id ?? ''} onChange={(e) => setDraft({ ...draft, faculty_id: e.target.value || null })}
                            className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                            <option value="">— professor unassigned —</option>
                            {faculty.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
                        </select>
                        <button onClick={() => setNewFaculty(newFaculty ? null : { name: '', login_id: '' })}
                            className="text-brandGreen font-semibold hover:underline">+ Add faculty</button>
                    </div>
                    {newRoom && (
                        <div className="flex flex-wrap gap-2 items-center">
                            <input value={newRoom.name} onChange={(e) => setNewRoom({ ...newRoom, name: e.target.value })}
                                placeholder="Room name" className="w-32 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <select value={newRoom.type} onChange={(e) => setNewRoom({ ...newRoom, type: e.target.value })}
                                className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                                <option value="physical">Physical</option><option value="virtual">Virtual (online)</option>
                            </select>
                            <button onClick={addRoom} className="px-3 py-1.5 rounded bg-brandGreen text-white font-semibold">Save Room</button>
                        </div>
                    )}
                    {newFaculty && (
                        <div className="flex flex-wrap gap-2 items-center">
                            <input value={newFaculty.name} onChange={(e) => setNewFaculty({ ...newFaculty, name: e.target.value })}
                                placeholder="Professor name" className="w-40 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <input value={newFaculty.login_id} onChange={(e) => setNewFaculty({ ...newFaculty, login_id: e.target.value })}
                                placeholder="Login ID" className="w-28 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <button onClick={addFaculty} className="px-3 py-1.5 rounded bg-brandGreen text-white font-semibold">Save Faculty</button>
                        </div>
                    )}
                    <div className="flex gap-1">
                        {DAY_OPTIONS.map((day) => (
                            <button key={day} onClick={() => toggleDay(day)}
                                className={`px-2 py-1 rounded ${draft.days.includes(day) ? 'bg-brandNavy text-white' : 'bg-slate-200 dark:bg-slate-700'}`}>
                                {day}
                            </button>
                        ))}
                    </div>
                    {error && <p className="text-red-600">{error}</p>}
                    <div className="flex gap-2">
                        <button onClick={save} className="px-3 py-1.5 rounded bg-brandGreen text-white font-semibold">Save Section</button>
                        <button onClick={() => setDraft(null)} className="px-3 py-1.5 rounded bg-slate-200 dark:bg-slate-700">Cancel</button>
                    </div>
                </div>
            ) : (
                <div>
                    {error && <p className="text-xs text-red-600 mt-1">{error}</p>}
                    <button onClick={() => setDraft({ ...EMPTY })} className="mt-2 text-xs text-brandGreen font-semibold hover:underline">
                        + Add Section
                    </button>
                </div>
            )}
        </div>
    );
}
