import React, { useState } from 'react';
import api from '../lib/api';

const EMPTY = { block_label: 'A', days: ['M', 'W'], start_time: '08:00', end_time: '09:30', room: 'TBA', professor: 'TBA', capacity: 40, faculty_id: null, room_id: null, delivery_mode: 'Face-to-Face' };
const DAY_OPTIONS = ['M', 'T', 'W', 'Th', 'F', 'Sat', 'Sun'];

const fieldClass = 'px-2.5 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-xs';
const labelClass = 'block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5';

export default function SectionEditor({ subject, schoolYear, faculty, rooms, onChanged, onListsChanged }) {
    const [draft, setDraft] = useState(null); // null | {..section fields, id?}
    const [error, setError] = useState(null);
    const [newFaculty, setNewFaculty] = useState(null); // null | {name, login_id}
    const [newRoom, setNewRoom] = useState(null); // null | {name, type}
    const [saving, setSaving] = useState(false);
    const [removingId, setRemovingId] = useState(null);
    const [removingRoom, setRemovingRoom] = useState(false);
    const [removingFaculty, setRemovingFaculty] = useState(false);

    const closeModal = () => { if (!saving) { setDraft(null); setError(null); setNewFaculty(null); setNewRoom(null); } };

    const save = () => {
        setError(null);
        setSaving(true);
        const payload = {
            ...draft, subject_id: subject.id, school_year: schoolYear, capacity: Number(draft.capacity),
            faculty_id: draft.faculty_id ? Number(draft.faculty_id) : null,
            room_id: draft.room_id ? Number(draft.room_id) : null,
        };
        const req = draft.id ? api.put(`/admin/sections/${draft.id}`, payload) : api.post('/admin/sections', payload);
        req.then(() => { setDraft(null); onChanged(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Check the section fields and try again.'))
            .finally(() => setSaving(false));
    };

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

    const addFaculty = () => {
        setError(null);
        setSaving(true);
        api.post('/admin/faculty', newFaculty)
            .then((res) => {
                setNewFaculty(null);
                setDraft((d) => ({ ...d, faculty_id: res.data.faculty.id }));
                onListsChanged();
            })
            .catch((err) => setError(err.response?.data?.message ?? 'Could not add faculty.'))
            .finally(() => setSaving(false));
    };

    const addRoom = () => {
        setError(null);
        setSaving(true);
        api.post('/admin/rooms', newRoom)
            .then((res) => {
                setNewRoom(null);
                setDraft((d) => ({ ...d, room_id: res.data.room.id }));
                onListsChanged();
            })
            .catch((err) => setError(err.response?.data?.message ?? 'Could not add room.'))
            .finally(() => setSaving(false));
    };

    const deleteRoom = () => {
        if (!draft.room_id) return;
        const room = rooms.find((r) => r.id === Number(draft.room_id));
        if (!window.confirm(`Delete room "${room?.name ?? ''}"? This cannot be undone.`)) return;
        setError(null);
        setRemovingRoom(true);
        api.delete(`/admin/rooms/${draft.room_id}`)
            .then(() => {
                setDraft((d) => ({ ...d, room_id: null }));
                onListsChanged();
            })
            .catch((err) => setError(err.response?.data?.message ?? 'Could not delete room.'))
            .finally(() => setRemovingRoom(false));
    };

    const deleteFaculty = () => {
        if (!draft.faculty_id) return;
        const member = faculty.find((f) => f.id === Number(draft.faculty_id));
        if (!window.confirm(`Delete faculty "${member?.name ?? ''}"? This cannot be undone.`)) return;
        setError(null);
        setRemovingFaculty(true);
        api.delete(`/admin/faculty/${draft.faculty_id}`)
            .then(() => {
                setDraft((d) => ({ ...d, faculty_id: null }));
                onListsChanged();
            })
            .catch((err) => setError(err.response?.data?.message ?? 'Could not delete faculty.'))
            .finally(() => setRemovingFaculty(false));
    };

    const toggleDay = (day) => setDraft((d) => ({
        ...d,
        days: d.days.includes(day) ? d.days.filter((x) => x !== day) : [...d.days, day],
    }));

    return (
        <div className="mt-3 bg-lightBg dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700 rounded-lg p-4">
            <div className="flex items-center justify-between mb-2">
                <h4 className="text-xs font-bold uppercase tracking-wider text-brandNavy/60 dark:text-slate-400">
                    Sections <span className="text-brandNavy/40 dark:text-slate-500 font-normal normal-case">({subject.sections.length})</span>
                </h4>
                <button onClick={() => setDraft({ ...EMPTY })} className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white text-xs transition-colors">
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
                <div
                    className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
                    onClick={(e) => { if (e.target === e.currentTarget) closeModal(); }}
                >
                    <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
                        <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white dark:bg-panelDark">
                            <span className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                                {draft.id ? `Edit Block ${draft.block_label}` : 'Add Section'}
                            </span>
                            <button type="button" onClick={closeModal} className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white">
                                <i className="fa-solid fa-xmark" />
                            </button>
                        </div>
                        <div className="p-5 space-y-3">
                            <div className="flex flex-wrap gap-2">
                                <div>
                                    <label className={labelClass}>Block</label>
                                    <input value={draft.block_label} onChange={(e) => setDraft({ ...draft, block_label: e.target.value })}
                                        placeholder="Block" className={`w-16 ${fieldClass}`} />
                                </div>
                                <div>
                                    <label className={labelClass}>Start time</label>
                                    <input type="time" value={draft.start_time} onChange={(e) => setDraft({ ...draft, start_time: e.target.value })}
                                        className={fieldClass} />
                                </div>
                                <div>
                                    <label className={labelClass}>End time</label>
                                    <input type="time" value={draft.end_time} onChange={(e) => setDraft({ ...draft, end_time: e.target.value })}
                                        className={fieldClass} />
                                </div>
                                <div>
                                    <label className={labelClass}>Capacity</label>
                                    <input type="number" value={draft.capacity} onChange={(e) => setDraft({ ...draft, capacity: e.target.value })}
                                        placeholder="Cap" className={`w-16 ${fieldClass}`} />
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-2 items-end">
                                {/* Delivery mode: switches whether this specific block meets face-to-face or online.
                                    Independent from the subject's own default "mode" so one subject can offer both. */}
                                <div>
                                    <label className={labelClass}>Delivery mode</label>
                                    <select value={draft.delivery_mode ?? 'Face-to-Face'}
                                        onChange={(e) => setDraft({ ...draft, delivery_mode: e.target.value, room_id: e.target.value === 'Online' ? null : draft.room_id })}
                                        className={fieldClass}>
                                        <option value="Face-to-Face">Face-to-Face</option>
                                        <option value="Online">Online</option>
                                    </select>
                                </div>
                                {draft.delivery_mode !== 'Online' && (
                                    <>
                                        <div>
                                            <label className={labelClass}>Room</label>
                                            <select value={draft.room_id ?? ''} onChange={(e) => setDraft({ ...draft, room_id: e.target.value || null })}
                                                className={fieldClass}>
                                                <option value="">— room unassigned —</option>
                                                {rooms.map((r) => <option key={r.id} value={r.id}>{r.name}{r.type === 'virtual' ? ' (online)' : ''}</option>)}
                                            </select>
                                        </div>
                                        <button type="button" onClick={deleteRoom} disabled={!draft.room_id || removingRoom}
                                            title="Delete selected room"
                                            className="text-red-600 hover:text-red-700 pb-1.5 disabled:opacity-30 disabled:cursor-not-allowed">
                                            <i className="fa-solid fa-trash" />
                                        </button>
                                        {!newRoom && (
                                            <button onClick={() => setNewRoom({ name: '', type: 'physical' })}
                                                className="text-xs text-brandGreen font-semibold hover:underline pb-1.5">+ Add room</button>
                                        )}
                                    </>
                                )}
                            </div>
                            <div className="flex flex-wrap gap-2 items-end">
                                <div>
                                    <label className={labelClass}>Faculty</label>
                                    <select value={draft.faculty_id ?? ''} onChange={(e) => setDraft({ ...draft, faculty_id: e.target.value || null })}
                                        className={fieldClass}>
                                        <option value="">— professor unassigned —</option>
                                        {faculty.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
                                    </select>
                                </div>
                                <button type="button" onClick={deleteFaculty} disabled={!draft.faculty_id || removingFaculty}
                                    title="Delete selected faculty"
                                    className="text-red-600 hover:text-red-700 pb-1.5 disabled:opacity-30 disabled:cursor-not-allowed">
                                    <i className="fa-solid fa-trash" />
                                </button>
                                {!newFaculty && (
                                    <button onClick={() => setNewFaculty({ name: '', login_id: '' })}
                                        className="text-xs text-brandGreen font-semibold hover:underline pb-1.5">+ Add faculty</button>
                                )}
                            </div>
                            {newRoom && (
                                <div className="bg-lightBg dark:bg-slate-800/50 rounded p-2.5">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider">New room</span>
                                        <button type="button" onClick={() => setNewRoom(null)} disabled={saving}
                                            className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white disabled:opacity-50">
                                            <i className="fa-solid fa-xmark" />
                                        </button>
                                    </div>
                                    <div className="flex flex-wrap gap-2 items-center">
                                        <input value={newRoom.name} onChange={(e) => setNewRoom({ ...newRoom, name: e.target.value })}
                                            placeholder="Room name" className={`w-32 ${fieldClass}`} />
                                        <select value={newRoom.type} onChange={(e) => setNewRoom({ ...newRoom, type: e.target.value })}
                                            className={fieldClass}>
                                            <option value="physical">Physical</option><option value="virtual">Virtual (online)</option>
                                        </select>
                                        <button onClick={addRoom} disabled={saving} className="ui-btn-primary bg-brandGreen text-white text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                                            {saving ? 'Saving…' : 'Save Room'}
                                        </button>
                                        <button type="button" onClick={() => setNewRoom(null)} disabled={saving}
                                            className="text-xs text-brandNavy/50 dark:text-slate-400 hover:underline disabled:opacity-50">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            )}
                            {newFaculty && (
                                <div className="bg-lightBg dark:bg-slate-800/50 rounded p-2.5">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider">New faculty</span>
                                        <button type="button" onClick={() => setNewFaculty(null)} disabled={saving}
                                            className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white disabled:opacity-50">
                                            <i className="fa-solid fa-xmark" />
                                        </button>
                                    </div>
                                    <div className="flex flex-wrap gap-2 items-center">
                                        <input value={newFaculty.name} onChange={(e) => setNewFaculty({ ...newFaculty, name: e.target.value })}
                                            placeholder="Professor name" className={`w-40 ${fieldClass}`} />
                                        <input value={newFaculty.login_id} onChange={(e) => setNewFaculty({ ...newFaculty, login_id: e.target.value })}
                                            placeholder="Login ID" className={`w-28 ${fieldClass}`} />
                                        <button onClick={addFaculty} disabled={saving} className="ui-btn-primary bg-brandGreen text-white text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                                            {saving ? 'Saving…' : 'Save Faculty'}
                                        </button>
                                        <button type="button" onClick={() => setNewFaculty(null)} disabled={saving}
                                            className="text-xs text-brandNavy/50 dark:text-slate-400 hover:underline disabled:opacity-50">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            )}
                            <div>
                                <label className={labelClass}>Meeting days</label>
                                <div className="flex flex-wrap gap-1">
                                    {DAY_OPTIONS.map((day) => (
                                        <button key={day} onClick={() => toggleDay(day)}
                                            className={`px-2 py-1 rounded text-xs ${draft.days.includes(day) ? 'bg-brandNavy text-white' : 'bg-slate-200 dark:bg-slate-700'}`}>
                                            {day}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            {error && <p className="text-xs text-red-600 bg-red-50 dark:bg-red-950/40 rounded px-2.5 py-1.5">{error}</p>}
                        </div>
                        <div className="p-5 border-t border-brandNavy/10 dark:border-slate-800 flex gap-2 sticky bottom-0 bg-white dark:bg-panelDark">
                            <button onClick={save} disabled={saving} className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                {saving ? 'Saving…' : 'Save Section'}
                            </button>
                            <button onClick={closeModal} disabled={saving} className="ui-btn-primary bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors disabled:opacity-50">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
