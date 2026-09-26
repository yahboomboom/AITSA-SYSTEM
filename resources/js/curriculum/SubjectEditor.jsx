import React, { useState } from 'react';
import api from '../lib/api';

const fieldClass = 'w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm';
const labelClass = 'block text-sm font-bold text-brandNavy dark:text-slate-200 mb-0.5';
const helpClass = 'text-xs text-brandNavy/50 dark:text-slate-500 mb-1.5';

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
                <div className="p-5 space-y-4">
                    <div>
                        <label className={labelClass}>Subject code</label>
                        <p className={helpClass}>A short code for this subject.</p>
                        <input value={draft.code} onChange={(e) => setDraft({ ...draft, code: e.target.value })}
                            placeholder="e.g. BOM101" className={fieldClass} />
                    </div>
                    <div>
                        <label className={labelClass}>Subject name</label>
                        <p className={helpClass}>The full name students will see.</p>
                        <input value={draft.title} onChange={(e) => setDraft({ ...draft, title: e.target.value })}
                            placeholder="e.g. Introduction to Business" className={fieldClass} />
                    </div>
                    <div>
                        <label className={labelClass}>Units</label>
                        <p className={helpClass}>How many units this subject is worth.</p>
                        <input type="number" value={draft.units} onChange={(e) => setDraft({ ...draft, units: e.target.value })}
                            placeholder="e.g. 3" className={`max-w-[8rem] ${fieldClass}`} />
                    </div>
                    <div>
                        <label className={labelClass}>Where will classes be held?</label>
                        <p className={helpClass}>Choose how this class meets.</p>
                        <select value={draft.mode} onChange={(e) => setDraft({ ...draft, mode: e.target.value })} className={fieldClass}>
                            <option value="F2F">In the classroom (Face-to-Face)</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                    <div>
                        <label className={labelClass}>What must students take first?</label>
                        <p className={helpClass}>These subjects must be passed before this one. Leave blank if none.</p>
                        <div className="flex flex-wrap gap-1.5 mb-2">
                            {(draft.prerequisite_ids ?? []).length === 0 && (
                                <span className="text-xs text-brandNavy/40 dark:text-slate-500">No requirements yet</span>
                            )}
                            {(draft.prerequisite_ids ?? []).map((id) => {
                                const prereq = allSubjects.find((s) => s.id === id);
                                if (!prereq) return null;
                                return (
                                    <span key={id} className="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded bg-lightBg dark:bg-slate-800 text-xs text-brandNavy dark:text-slate-200">
                                        {prereq.code}
                                        <button type="button"
                                            onClick={() => setDraft({ ...draft, prerequisite_ids: draft.prerequisite_ids.filter((pid) => pid !== id) })}
                                            className="text-brandNavy/40 dark:text-slate-500 hover:text-red-600">
                                            <i className="fa-solid fa-xmark text-[10px]" />
                                        </button>
                                    </span>
                                );
                            })}
                        </div>
                        <select value="" onChange={(e) => {
                            const id = Number(e.target.value);
                            if (!id) return;
                            setDraft({ ...draft, prerequisite_ids: [...(draft.prerequisite_ids ?? []), id] });
                        }} className={fieldClass}>
                            <option value="">Pick a subject to add…</option>
                            {allSubjects.filter((s) => s.id !== draft.id && !(draft.prerequisite_ids ?? []).includes(s.id)).map((s) => (
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
