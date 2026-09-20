import React, { useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import api from './lib/api';
import SubjectRow from './curriculum/SubjectRow';

function CurriculumApp() {
    const [programs, setPrograms] = useState([]);
    const [active, setActive] = useState(null);
    const [subjects, setSubjects] = useState([]);
    const [adding, setAdding] = useState(false);
    const [draft, setDraft] = useState(null);
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);
    const [yearFilter, setYearFilter] = useState(1);
    const [semFilter, setSemFilter] = useState(1);
    const [windowOpen, setWindowOpen] = useState(false);

    useEffect(() => {
        api.get('/admin/programs').then((res) => {
            const enrollable = res.data.programs.filter((p) => p.is_enrollable);
            setPrograms(enrollable);
            setWindowOpen(Boolean(res.data.change_matriculation_open));
            if (enrollable.length > 0) setActive(enrollable[0]);
        });
    }, []);

    const loadSubjects = useCallback(() => {
        if (!active) return;
        api.get(`/admin/programs/${active.id}/subjects`).then((res) => setSubjects(res.data.subjects));
    }, [active]);

    useEffect(loadSubjects, [loadSubjects]);

    const createSubject = () => {
        setError(null);
        setSaving(true);
        api.post('/admin/subjects', {
            ...draft, program_id: active.id, units: Number(draft.units),
            year_level: yearFilter, semester: semFilter, prerequisite_ids: [],
        }).then(() => { setAdding(false); setDraft(null); loadSubjects(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Check the fields and try again.'))
            .finally(() => setSaving(false));
    };

    const visible = subjects.filter((s) => s.year_level === yearFilter && s.semester === semFilter);

    const toggleWindow = () => {
        api.post('/admin/settings/change-matriculation', { open: !windowOpen })
            .then((res) => setWindowOpen(res.data.change_matriculation_open));
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap gap-2 items-center justify-between">
                <div className="flex flex-wrap gap-2">
                    {programs.map((p) => (
                        <button key={p.id} onClick={() => setActive(p)}
                            className={`px-4 py-2 rounded text-sm font-medium transition-colors
                                ${active?.id === p.id ? 'bg-brandNavy text-white' : 'bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                            {p.code}
                        </button>
                    ))}
                </div>
                <button onClick={toggleWindow}
                    className={`ui-btn-primary transition-colors
                        ${windowOpen ? 'bg-brandGreen text-white' : 'bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                    Change of matriculation: {windowOpen ? 'Open' : 'Closed'}
                </button>
            </div>

            {active && (
                <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
                    <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-1">{active.name}</h2>
                    <div className="flex flex-wrap gap-2 my-3 text-sm">
                        {[...Array(active.years ?? 4)].map((_, i) => (
                            <button key={i} onClick={() => setYearFilter(i + 1)}
                                className={`px-3 py-1.5 rounded transition-colors ${yearFilter === i + 1 ? 'bg-brandGreen text-white' : 'bg-lightBg dark:bg-slate-800 text-brandNavy dark:text-slate-300'}`}>
                                Year {i + 1}
                            </button>
                        ))}
                        {[1, 2].map((sem) => (
                            <button key={sem} onClick={() => setSemFilter(sem)}
                                className={`px-3 py-1.5 rounded transition-colors ${semFilter === sem ? 'bg-brandGold text-white' : 'bg-lightBg dark:bg-slate-800 text-brandNavy dark:text-slate-300'}`}>
                                Sem {sem}
                            </button>
                        ))}
                    </div>

                    {visible.map((subject) => (
                        <SubjectRow key={subject.id} subject={subject} allSubjects={subjects}
                            onChanged={loadSubjects} />
                    ))}

                    {adding ? (
                        <div className="flex flex-wrap gap-2 text-sm mt-2">
                            <input placeholder="Code" value={draft?.code ?? ''} onChange={(e) => setDraft({ ...draft, code: e.target.value })}
                                className="w-24 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors" />
                            <input placeholder="Title" value={draft?.title ?? ''} onChange={(e) => setDraft({ ...draft, title: e.target.value })}
                                className="flex-1 min-w-48 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors" />
                            <input type="number" placeholder="Units" value={draft?.units ?? 3} onChange={(e) => setDraft({ ...draft, units: e.target.value })}
                                className="w-16 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors" />
                            <select value={draft?.mode ?? 'F2F'} onChange={(e) => setDraft({ ...draft, mode: e.target.value })}
                                className="bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors">
                                <option>F2F</option><option>Online</option>
                            </select>
                            <button onClick={createSubject} disabled={saving} className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                {saving ? 'Saving…' : 'Add'}
                            </button>
                            <button onClick={() => setAdding(false)} disabled={saving} className="ui-btn-primary bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors disabled:opacity-50">Cancel</button>
                            {error && <p className="w-full text-red-600 bg-red-500/10 border border-red-500/20 rounded px-3 py-2">{error}</p>}
                        </div>
                    ) : (
                        <button onClick={() => { setAdding(true); setDraft({ code: '', title: '', units: 3, mode: 'F2F' }); }}
                            className="mt-2 text-sm text-brandGreen font-medium hover:underline">
                            + Add subject to Year {yearFilter}, Sem {semFilter}
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

const el = document.getElementById('curriculum-root');
if (el) createRoot(el).render(<ErrorBoundary><CurriculumApp /></ErrorBoundary>);
