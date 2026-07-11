import React, { useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import api from './lib/api';
import StatusCard from './enrollment/StatusCard';
import RegularView from './enrollment/RegularView';
import IrregularPicker from './enrollment/IrregularPicker';

function EnrollmentApp() {
    const [ctx, setCtx] = useState(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const [resubmitting, setResubmitting] = useState(false);

    const load = useCallback(() => {
        setLoading(true);
        api.get('/enrollment/context')
            .then((res) => setCtx(res.data))
            .catch(() => setError('Could not load enrollment data. Please refresh the page.'))
            .finally(() => setLoading(false));
    }, []);

    useEffect(load, [load]);

    const submit = (sectionIds) => {
        setSubmitting(true);
        setError(null);
        const body = ctx.student.type === 'irregular' ? { section_ids: sectionIds } : {};
        api.post('/enrollment', body)
            .then(() => { setResubmitting(false); load(); })
            .catch((err) => setError(err.response?.data?.message ?? 'Something went wrong. Please try again.'))
            .finally(() => setSubmitting(false));
    };

    if (loading) return <p className="text-sm text-slate-500">Loading your enrollment…</p>;
    if (!ctx) return <p className="text-sm text-red-600">{error}</p>;

    const { term, student, clearance_complete, enrollment, block, catalogue } = ctx;

    return (
        <div className="space-y-6">
            <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 className="text-xl font-bold text-brandNavy dark:text-slate-100">Enrollment — A.Y. {term.school_year}, Semester {term.semester}</h1>
                    <p className="text-xs text-slate-500">
                        {student.name} ({student.login_id}) · {student.program_name ?? student.program} · {student.year_level} ·{' '}
                        <span className={student.type === 'regular' ? 'text-brandGreen font-semibold' : 'text-amber-600 font-semibold'}>
                            {student.type === 'regular' ? 'Regular' : 'Irregular'}
                        </span>
                    </p>
                </div>
            </div>

            {!clearance_complete && (
                <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm p-6">
                    <p className="text-sm font-semibold text-amber-600">
                        <i className="fa-solid fa-lock mr-2" />
                        Enrollment is locked until your clearance is fully approved.
                    </p>
                    <a href="/clearance" className="inline-block mt-3 text-sm text-brandNavy dark:text-slate-200 underline">
                        View my clearance status
                    </a>
                </div>
            )}

            {clearance_complete && enrollment && !(enrollment.status === 'rejected' && resubmitting) && (
                <StatusCard enrollment={enrollment} onResubmit={() => setResubmitting(true)} />
            )}

            {clearance_complete && (!enrollment || (enrollment.status === 'rejected' && resubmitting)) && (
                student.type === 'regular'
                    ? <RegularView block={block} submitting={submitting} error={error} onConfirm={() => submit()} />
                    : <IrregularPicker catalogue={catalogue ?? []} submitting={submitting} error={error} onSubmit={submit} />
            )}
        </div>
    );
}

const el = document.getElementById('enrollment-root');
if (el) createRoot(el).render(<EnrollmentApp />);
