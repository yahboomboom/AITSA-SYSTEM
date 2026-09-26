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

    const loadSchedule = (id) => {
        api.get(`/admin/faculty/${id}/schedule`).then((res) => setSchedules((s) => ({ ...s, [id]: res.data.schedule })));
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
                const totalHours = sched.reduce((sum, s) => sum + weeklyHours(s), 0);
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
                                            <th className="py-2 px-4 font-semibold">Subject</th><th className="font-semibold">Block</th>
                                            <th className="font-semibold">Days</th><th className="font-semibold">Time</th>
                                            <th className="font-semibold">Where</th><th className="font-semibold text-right pr-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {sched.map((s) => (
                                            <tr key={s.id} className="border-t border-slate-50 dark:border-slate-800">
                                                <td className="py-2 px-4">{s.subject_code} — {s.subject_title}</td>
                                                <td>{s.block_label}</td>
                                                <td>{s.days.join('/')}</td>
                                                <td>{s.start_time}–{s.end_time}</td>
                                                <td>
                                                    {s.online ? (
                                                        <span className="px-1.5 py-0.5 rounded bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300 font-semibold">Online</span>
                                                    ) : (
                                                        s.room_label
                                                    )}
                                                </td>
                                                <td className="text-right pr-4">
                                                    <button onClick={() => setDraft({ ...s })} className="text-brandNavy dark:text-slate-300 hover:underline font-medium">Edit</button>
                                                </td>
                                            </tr>
                                        ))}
                                        {sched.length === 0 && (
                                            <tr><td colSpan={6} className="py-3 px-4 text-slate-400">No sections loaded this school year.</td></tr>
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
