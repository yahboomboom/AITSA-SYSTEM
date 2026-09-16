import React, { useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import api from './lib/api';
import SchedulingSubjectRow from './approver/SchedulingSubjectRow';
import FacultyLoading from './curriculum/FacultyLoading';

const SCHOOL_YEAR = '2026-2027'; // matches Setting school_year; sections are created for this term

function SchedulingApp() {
    const [programs, setPrograms] = useState([]);
    const [active, setActive] = useState(null);
    const [subjects, setSubjects] = useState([]);
    const [yearFilter, setYearFilter] = useState(1);
    const [semFilter, setSemFilter] = useState(1);
    const [faculty, setFaculty] = useState([]);
    const [rooms, setRooms] = useState([]);
    const [view, setView] = useState('sections'); // 'sections' | 'loading'

    useEffect(() => {
        api.get('/admin/programs').then((res) => {
            const enrollable = res.data.programs.filter((p) => p.is_enrollable);
            setPrograms(enrollable);
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

    const visible = subjects.filter((s) => s.year_level === yearFilter && s.semester === semFilter);

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
                    <button onClick={() => setView('sections')}
                        className={`px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider
                            ${view === 'sections' ? 'bg-brandNavy text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                        Sections
                    </button>
                    <button onClick={() => setView('loading')}
                        className={`px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider
                            ${view === 'loading' ? 'bg-brandNavy text-white' : 'bg-white dark:bg-panelDark text-brandNavy dark:text-slate-200 shadow-sm'}`}>
                        Faculty Loading
                    </button>
                </div>
            </div>

            {view === 'loading' && <FacultyLoading faculty={faculty} />}

            {view === 'sections' && active && (
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
                        <SchedulingSubjectRow key={subject.id} subject={subject} allSubjects={subjects}
                            schoolYear={SCHOOL_YEAR} faculty={faculty} rooms={rooms}
                            onChanged={loadSubjects} onListsChanged={loadLists} />
                    ))}

                    {visible.length === 0 && (
                        <p className="text-sm text-slate-500">No subjects in Year {yearFilter}, Sem {semFilter} yet — ask the Registrar to add one to the curriculum.</p>
                    )}
                </div>
            )}
        </div>
    );
}

const el = document.getElementById('approver-scheduling-root');
if (el) createRoot(el).render(<ErrorBoundary><SchedulingApp /></ErrorBoundary>);
