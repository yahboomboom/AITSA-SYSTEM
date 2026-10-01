import React, { useState } from 'react';
import api from '../lib/api';
import SectionEditModal from './SectionEditModal';

function weeklyHours(s) {
    const [sh, sm] = s.start_time.split(':').map(Number);
    const [eh, em] = s.end_time.split(':').map(Number);
    return (((eh * 60 + em) - (sh * 60 + sm)) / 60) * s.days.length;
}

export default function FacultyLoading({ faculty, rooms, onListsChanged }) {
    const [openId, setOpenId] = useState(null);
    const [schedules, setSchedules] = useState({});
    const [draft, setDraft] = useState(null);

    const [semesters, setSemesters] = useState({});
    const [loadError, setLoadError] = useState({});

    const loadSchedule = (id) => {
        setLoadError((e) => ({ ...e, [id]: null }));
        api.get(`/admin/faculty/${id}/schedule`)
            .then((res) => {
                setSchedules((s) => ({ ...s, [id]: res.data.schedule }));
                setSemesters((s) => ({ ...s, [id]: res.data.semester }));
            })
            .catch(() => setLoadError((e) => ({ ...e, [id]: 'Could not load this schedule.' })));
    };

    const toggle = (id) => {
        if (openId === id) { setOpenId(null); return; }
        setOpenId(id);
        if (!schedules[id]) loadSchedule(id);
    };

    return (
        <div className="space-y-3">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100">Faculty Loading</h2>
            {faculty.length === 0 && (
                <p className="text-sm text-slate-400 bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">No faculty accounts yet — add one from any section editor.</p>
            )}
            {faculty.map((f) => {
                const sched = schedules[f.id] ?? [];
                // Hours for the current semester only; the list spans the school year.
                const currentSem = semesters[f.id];
                const totalHours = sched.filter((s) => !currentSem || s.semester === currentSem).reduce((sum, s) => sum + weeklyHours(s), 0);
                const clashCount = sched.filter((s) => (s.conflicts_with ?? []).length > 0).length;
                const byId = Object.fromEntries(sched.map((s) => [s.id, s]));
                const isOpen = openId === f.id;
                return (
                    <div key={f.id} className="bg-white dark:bg-panelDark rounded-2xl shadow-sm overflow-hidden">
                        <button onClick={() => toggle(f.id)} className="w-full flex items-center justify-between gap-3 p-4 hover:bg-lightBg dark:hover:bg-slate-800/50 transition-colors">
                            <span className="flex items-center gap-3">
                                <span className="w-9 h-9 rounded-full bg-gradient-to-tr from-brandNavy to-brandGreen text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                    {f.name.charAt(0).toUpperCase()}
                                </span>
                                <span className="text-left">
                                    <span className="block font-semibold text-brandNavy dark:text-slate-200 text-sm">{f.name}</span>
                                    <span className="block text-xs text-slate-400 font-mono">{f.login_id}</span>
                                </span>
                            </span>
                            <span className="flex items-center gap-3 flex-shrink-0">
                                <span className="px-2.5 py-1 rounded-full bg-lightBg dark:bg-slate-800 text-xs font-semibold text-brandNavy dark:text-slate-300">
                                    {f.sections_count} section{f.sections_count === 1 ? '' : 's'}
                                </span>
                                {isOpen && clashCount > 0 && (
                                    <span className="px-2.5 py-1 rounded-full bg-red-500/10 text-red-600 dark:text-red-400 text-xs font-semibold">
                                        <i className="fa-solid fa-triangle-exclamation mr-1" />{clashCount} clash{clashCount === 1 ? '' : 'es'}
                                    </span>
                                )}
                                {isOpen && sched.length > 0 && (
                                    <span className="px-2.5 py-1 rounded-full bg-brandGold/15 text-brandGold text-xs font-semibold">
                                        {totalHours.toFixed(1)} hrs/week
                                    </span>
                                )}
                                <i className={`fa-solid fa-chevron-down text-xs text-slate-400 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
                            </span>
                        </button>
                        {isOpen && (
                            <div className="border-t border-slate-100 dark:border-slate-800 overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="text-left text-slate-400 bg-lightBg dark:bg-slate-800/50">
                                            <th className="py-2 px-4 font-semibold">Subject</th><th className="font-semibold">Sem</th><th className="font-semibold">Block</th>
                                            <th className="font-semibold">Days</th><th className="font-semibold">Time</th>
                                            <th className="font-semibold">Where</th><th className="font-semibold text-right pr-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {sched.map((s) => {
                                            const clashes = (s.conflicts_with ?? []).map((id) => byId[id]).filter(Boolean);
                                            return (
                                            <tr key={s.id} className={`border-t border-slate-50 dark:border-slate-800 ${clashes.length ? 'bg-red-50 dark:bg-red-950/30' : ''}`}>
                                                <td className="py-2 px-4">
                                                    {s.subject_code} — {s.subject_title}
                                                    {clashes.length > 0 && (
                                                        <span className="block text-red-600 dark:text-red-400 font-semibold mt-0.5">
                                                            <i className="fa-solid fa-triangle-exclamation mr-1" />Clashes with {clashes.map((c) => `${c.subject_code} ${c.block_label}`).join(', ')}
                                                        </span>
                                                    )}
                                                </td>
                                                <td>{s.semester}</td>
                                                <td>{s.block_label}</td>
                                                <td>{s.days.join('/')}</td>
                                                <td>{s.start_time}–{s.end_time}</td>
                                                <td>
                                                    {s.online ? (
                                                        <span className="px-1.5 py-0.5 rounded bg-brandNavy/10 text-brandNavy dark:text-[#8EC3DE] dark:bg-brandNavy/40 dark:text-[#8EC3DE] font-semibold">Online</span>
                                                    ) : (
                                                        s.room_label
                                                    )}
                                                </td>
                                                <td className="text-right pr-4">
                                                    <button onClick={() => setDraft({ ...s })} className="text-brandNavy dark:text-slate-300 hover:underline font-medium">Edit</button>
                                                </td>
                                            </tr>
                                            );
                                        })}
                                        {loadError[f.id] && (
                                            <tr><td colSpan={7} className="py-3 px-4 text-red-600">
                                                {loadError[f.id]} <button onClick={() => loadSchedule(f.id)} className="underline font-semibold">Retry</button>
                                            </td></tr>
                                        )}
                                        {!loadError[f.id] && sched.length === 0 && (
                                            <tr><td colSpan={7} className="py-3 px-4 text-slate-400">No sections loaded this school year.</td></tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                );
            })}

            {draft && (
                <SectionEditModal
                    draft={draft}
                    setDraft={setDraft}
                    rooms={rooms}
                    faculty={faculty}
                    onClose={() => setDraft(null)}
                    onSaved={() => loadSchedule(draft.faculty_id)}
                    onListsChanged={onListsChanged}
                />
            )}
        </div>
    );
}
