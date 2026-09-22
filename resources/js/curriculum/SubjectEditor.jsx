import React, { useState } from 'react';
import api from '../lib/api';

const fieldClass = 'px-2.5 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-xs';
const labelClass = 'block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5';

export default function SubjectEditor({ draft, setDraft, allSubjects, programId, yearLevel, semester, onClose, onSaved }) {
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);

    const close = () => { if (!saving) onClose(); };

    const save = () => {
        setError(null);
        setSaving(true);
        const payload = {
            code: draft.code, title: draft.title, units: Number(draft.units), mode: draft.mode,
            prerequisite_ids: draft.prerequisite_ids ?? [],
        };
        const req = draft.id
            ? api.put(`/admin/subjects/${draft.id}`, payload)
            : api.post('/admin/subjects', { ...payload, program_id: programId, year_level: yearLevel, semester });
        req.then(() => { onSaved(); onClose(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Check the fields and try again.'))
            .finally(() => setSaving(false));
    };

    return (
        <div
            className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget) close(); }}
        >
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white dark:bg-panelDark">
                    <span className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                        {draft.id ? `Edit ${draft.code}` : 'Add Subject'}
                    </span>
                    <button type="button" onClick={close} className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>
                <div className="p-5 space-y-3">
                    <div className="flex flex-wrap gap-2">
                        <div>
                            <label className={labelClass}>Code</label>
                            <input value={draft.code} onChange={(e) => setDraft({ ...draft, code: e.target.value })}
                                placeholder="Code" className={`w-24 ${fieldClass}`} />
                        </div>
                        <div className="flex-1 min-w-48">
                            <label className={labelClass}>Title</label>
                            <input value={draft.title} onChange={(e) => setDraft({ ...draft, title: e.target.value })}
                                placeholder="Title" className={`w-full ${fieldClass}`} />
                        </div>
                        <div>
                            <label className={labelClass}>Units</label>
                            <input type="number" value={draft.units} onChange={(e) => setDraft({ ...draft, units: e.target.value })}
                                placeholder="Units" className={`w-16 ${fieldClass}`} />
                        </div>
                        <div>
                            <label className={labelClass}>Mode</label>
                            <select value={draft.mode} onChange={(e) => setDraft({ ...draft, mode: e.target.value })} className={fieldClass}>
                                <option>F2F</option><option>Online</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label className={labelClass}>Prerequisites</label>
                        <select multiple value={(draft.prerequisite_ids ?? []).map(String)}
                            onChange={(e) => setDraft({ ...draft, prerequisite_ids: [...e.target.selectedOptions].map((o) => Number(o.value)) })}
                            className={`${fieldClass} w-full`} size={4}>
                            {allSubjects.filter((s) => s.id !== draft.id).map((s) => (
                                <option key={s.id} value={s.id}>{s.code} — {s.title}</option>
                            ))}
                        </select>
                    </div>
                    {error && <p className="text-xs text-red-600 bg-red-50 dark:bg-red-950/40 rounded px-2.5 py-1.5">{error}</p>}
                </div>
                <div className="p-5 border-t border-brandNavy/10 dark:border-slate-800 flex gap-2 sticky bottom-0 bg-white dark:bg-panelDark">
                    <button onClick={save} disabled={saving} className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {saving ? 'Saving…' : 'Save Subject'}
                    </button>
                    <button onClick={close} disabled={saving} className="ui-btn-primary bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors disabled:opacity-50">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    );
}
