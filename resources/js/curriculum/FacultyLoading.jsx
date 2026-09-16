import React, { useState } from 'react';
import api from '../lib/api';

function weeklyHours(s) {
    const [sh, sm] = s.start_time.split(':').map(Number);
    const [eh, em] = s.end_time.split(':').map(Number);
    return (((eh * 60 + em) - (sh * 60 + sm)) / 60) * s.days.length;
}

export default function FacultyLoading({ faculty }) {
    const [openId, setOpenId] = useState(null);
    const [schedules, setSchedules] = useState({});

    const toggle = (id) => {
        if (openId === id) { setOpenId(null); return; }
        setOpenId(id);
        if (!schedules[id]) {
            api.get(`/admin/faculty/${id}/schedule`).then((res) => setSchedules((s) => ({ ...s, [id]: res.data.schedule })));
        }
    };

    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-3">Faculty Loading</h2>
            {faculty.length === 0 && (
                <p className="text-sm text-slate-400">No faculty accounts yet — add one from any section editor.</p>
            )}
            {faculty.map((f) => {
                const sched = schedules[f.id] ?? [];
                const totalHours = sched.reduce((sum, s) => sum + weeklyHours(s), 0);
                return (
                    <div key={f.id} className="border-b border-slate-100 dark:border-slate-800 py-2">
                        <button onClick={() => toggle(f.id)} className="w-full flex justify-between items-center text-sm">
                            <span className="font-semibold text-brandNavy dark:text-slate-200">
                                {f.name} <span className="text-xs text-slate-400 font-mono">({f.login_id})</span>
                            </span>
                            <span className="text-xs text-slate-400">
                                {f.sections_count} section(s){openId === f.id && sched.length > 0 ? ` · ${totalHours.toFixed(1)} hrs/week` : ''}
                            </span>
                        </button>
                        {openId === f.id && (
                            <table className="w-full text-xs mt-2">
                                <thead>
                                    <tr className="text-left text-slate-400">
                                        <th className="py-1 font-semibold">Subject</th><th className="font-semibold">Block</th>
                                        <th className="font-semibold">Days</th><th className="font-semibold">Time</th><th className="font-semibold">Room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sched.map((s) => (
                                        <tr key={s.id} className="border-t border-slate-50 dark:border-slate-800">
                                            <td className="py-1.5">{s.subject_code} — {s.subject_title}</td>
                                            <td>{s.block_label}</td>
                                            <td>{s.days.join('/')}</td>
                                            <td>{s.start_time}–{s.end_time}</td>
                                            <td>
                                                {s.room_label}
                                                {s.online && <span className="ml-1 px-1.5 py-0.5 rounded bg-brandGold/15 text-brandGold font-bold text-[10px] uppercase">Online</span>}
                                            </td>
                                        </tr>
                                    ))}
                                    {sched.length === 0 && (
                                        <tr><td colSpan={5} className="py-2 text-slate-400">No sections loaded this school year.</td></tr>
                                    )}
                                </tbody>
                            </table>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
