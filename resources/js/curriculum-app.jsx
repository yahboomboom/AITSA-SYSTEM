import React, { useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import api from './lib/api';
import SubjectRow from './curriculum/SubjectRow';
import FacultyLoading from './curriculum/FacultyLoading';

const SCHOOL_YEAR = '2026-2027'; // matches Setting school_year; sections are created for this term

function CurriculumApp() {
    const [programs, setPrograms] = useState([]);
    const [active, setActive] = useState(null);
    const [subjects, setSubjects] = useState([]);
    const [adding, setAdding] = useState(false);
    const [draft, setDraft] = useState(null);
    const [error, setError] = useState(null);
    const [yearFilter, setYearFilter] = useState(1);
    const [semFilter, setSemFilter] = useState(1);
    const [windowOpen, setWindowOpen] = useState(false);
    const [faculty, setFaculty] = useState([]);
    const [rooms, setRooms] = useState([]);
    const [view, setView] = useState('curriculum'); // 'curriculum' | 'loading'

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

    const loadLists = useCallback(() => {
        api.get('/admin/faculty').then((res) => setFaculty(res.data.faculty));
        api.get('/admin/rooms').then((res) => setRooms(res.data.rooms));
    }, []);

    useEffect(loadLists, [loadLists]);
    useEffect(() => { if (view === 'loading') loadLists(); }, [view, loadLists]);

    const createSubject = () => {
        setError(null);
        api.post('/admin/subjects', {
            ...draft, program_id: active.id, units: Number(draft.units),
            year_level: yearFilter, semester: semFilter, prerequisite_ids: [],
        }).then(() => { setAdding(false); setDraft(null); loadSubjects(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Check the fields and try again.'));
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
                            className={`px-4 py-2 rounded-lg text-sm font-semibold
                                ${active?.id === p.id ? 'bg-brandNavy text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                            {p.code}
                        </button>
                    ))}
                </div>
                <div className="flex gap-2">
                    <button onClick={() => setView('curriculum')}
                        className={`px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider
                            ${view === 'curriculum' ? 'bg-brandNavy text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                        Curriculum
                    </button>
                    <button onClick={() => setView('loading')}
                        className={`px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider
                            ${view === 'loading' ? 'bg-brandNavy text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                        Faculty Loading
                    </button>
                </div>
                <button onClick={toggleWindow}
                    className={`px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider
                        ${windowOpen ? 'bg-brandGreen text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                    Change of Matriculation: {windowOpen ? 'OPEN' : 'CLOSED'}
                </button>
            </div>

            {view === 'loading' && <FacultyLoading faculty={faculty} />}

            {view === 'curriculum' && active && (
                <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
                    <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-1">{active.name}</h2>
                    <div className="flex flex-wrap gap-2 my-3 text-xs">
                        {[...Array(active.years ?? 4)].map((_, i) => (
                            <button key={i} onClick={() => setYearFilter(i + 1)}
                                className={`px-3 py-1.5 rounded ${yearFilter === i + 1 ? 'bg-brandGreen text-white' : 'bg-slate-100 dark:bg-slate-800'}`}>
                                Year {i + 1}
                            </button>
                        ))}
                        {[1, 2].map((sem) => (
                            <button key={sem} onClick={() => setSemFilter(sem)}
                                className={`px-3 py-1.5 rounded ${semFilter === sem ? 'bg-brandGold text-white' : 'bg-slate-100 dark:bg-slate-800'}`}>
                                Sem {sem}
                            </button>
                        ))}
                    </div>

                    {visible.map((subject) => (
                        <SubjectRow key={subject.id} subject={subject} allSubjects={subjects}
                            schoolYear={SCHOOL_YEAR} faculty={faculty} rooms={rooms}
                            onChanged={loadSubjects} onListsChanged={loadLists} />
                    ))}

                    {adding ? (
                        <div className="flex flex-wrap gap-2 text-xs mt-2">
                            <input placeholder="Code" value={draft?.code ?? ''} onChange={(e) => setDraft({ ...draft, code: e.target.value })}
                                className="w-24 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <input placeholder="Title" value={draft?.title ?? ''} onChange={(e) => setDraft({ ...draft, title: e.target.value })}
                                className="flex-1 min-w-48 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <input type="number" placeholder="Units" value={draft?.units ?? 3} onChange={(e) => setDraft({ ...draft, units: e.target.value })}
                                className="w-16 px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                            <select value={draft?.mode ?? 'F2F'} onChange={(e) => setDraft({ ...draft, mode: e.target.value })}
                                className="px-2 py-1.5 rounded border border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                                <option>F2F</option><option>Online</option>
                            </select>
                            <button onClick={createSubject} className="px-3 py-1.5 rounded bg-brandGreen text-white font-semibold">Add</button>
                            <button onClick={() => setAdding(false)} className="px-3 py-1.5 rounded bg-slate-200 dark:bg-slate-700">Cancel</button>
                            {error && <p className="w-full text-red-600">{error}</p>}
                        </div>
                    ) : (
                        <button onClick={() => { setAdding(true); setDraft({ code: '', title: '', units: 3, mode: 'F2F' }); }}
                            className="mt-2 text-sm text-brandGreen font-semibold hover:underline">
                            + Add Subject to Year {yearFilter}, Sem {semFilter}
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

const el = document.getElementById('curriculum-root');
if (el) createRoot(el).render(<CurriculumApp />);
