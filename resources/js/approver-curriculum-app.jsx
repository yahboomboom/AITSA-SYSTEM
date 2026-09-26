import React, { useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import api from './lib/api';
import SubjectRow from './curriculum/SubjectRow';
import SubjectEditor from './curriculum/SubjectEditor';

const EMPTY = { code: '', title: '', units: 3, mode: 'F2F', prerequisite_ids: [] };

function CurriculumApp() {
    const [programs, setPrograms] = useState([]);
    const [active, setActive] = useState(null);
    const [subjects, setSubjects] = useState([]);
    const [draft, setDraft] = useState(null);
    const [yearFilter, setYearFilter] = useState(1);
    const [yearPicked, setYearPicked] = useState(false);
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

    const visible = subjects.filter((s) => s.year_level === yearFilter && s.semester === semFilter);

    const toggleWindow = () => {
        api.post('/admin/settings/change-matriculation', { open: !windowOpen })
            .then((res) => setWindowOpen(res.data.change_matriculation_open));
    };

    const editSubject = (subject) => setDraft({
        id: subject.id, code: subject.code, title: subject.title, units: subject.units,
        mode: subject.mode, prerequisite_ids: subject.prerequisite_ids ?? [],
    });

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
                    <div className="flex flex-wrap items-center justify-between gap-2 mb-1">
                        <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">{active.name}</h2>
                        <button onClick={() => setDraft({ ...EMPTY })}
                            className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white text-xs transition-colors shadow-sm">
                            <i className="fa-solid fa-plus" /> Add Subject to Year {yearFilter}, Sem {semFilter}
                        </button>
                    </div>
                    <div className="flex flex-wrap gap-2 my-3 text-sm items-center">
                        <select
                            value={yearFilter}
                            onFocus={() => setYearPicked(true)}
                            onChange={(e) => setYearFilter(Number(e.target.value))}
                            className="px-3 py-1.5 rounded border border-brandNavy/10 dark:border-slate-700 bg-white dark:bg-slate-800 text-brandNavy dark:text-slate-200 font-medium"
                        >
                            {[...Array(active.years ?? 4)].map((_, i) => (
                                <option key={i} value={i + 1}>Year {i + 1}</option>
                            ))}
                        </select>
                        {yearPicked && [1, 2].map((sem) => (
                            <button key={sem} onClick={() => setSemFilter(sem)}
                                className={`px-3 py-1.5 rounded transition-colors ${semFilter === sem ? 'bg-brandGold text-white' : 'bg-lightBg dark:bg-slate-800 text-brandNavy dark:text-slate-300'}`}>
                                Sem {sem}
                            </button>
                        ))}
                    </div>

                    {visible.map((subject) => (
                        <SubjectRow key={subject.id} subject={subject} allSubjects={subjects}
                            onChanged={loadSubjects} onEdit={editSubject} />
                    ))}
                </div>
            )}

            {draft && (
                <SubjectEditor
                    draft={draft}
                    setDraft={setDraft}
                    allSubjects={subjects}
                    programId={active?.id}
                    yearLevel={yearFilter}
                    semester={semFilter}
                    onClose={() => setDraft(null)}
                    onSaved={loadSubjects}
                />
            )}
        </div>
    );
}

const el = document.getElementById('curriculum-root');
if (el) createRoot(el).render(<ErrorBoundary><CurriculumApp /></ErrorBoundary>);
