import { useEffect, useMemo, useState } from 'react';

const YEAR = ['', '1st Year', '2nd Year', '3rd Year', '4th Year'];
const SEM = ['', '1st Sem', '2nd Sem', 'Summer'];

const BADGES = {
    passed: ['Passed', 'border-brandGreen text-brandGreen'],
    credited: ['Credited', 'border-brandGreen text-brandGreen'],
    graded: ['Graded', 'border-brandNavy/30 text-brandNavy/60 dark:border-slate-600 dark:text-slate-400'],
    pending: ['Pending Chair', 'border-brandGold text-brandGold'],
};

const jsonHeaders = (csrfToken) => ({
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': csrfToken,
    'X-Requested-With': 'XMLHttpRequest',
});

// Registrar picks subjects a transferee/returnee already passed (from their
// TOR or old records). They go to the Chair; nothing counts until approved.
export default function CreditSubjectsModal({ student, csrfToken, onClose, onSent, onYearSet }) {
    const [overview, setOverview] = useState(null);
    const [picked, setPicked] = useState({}); // code -> grade string
    const [note, setNote] = useState('');
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const [settingYear, setSettingYear] = useState(false);

    const load = () => fetch(student.creditsUrl, { headers: { Accept: 'application/json' } })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message ?? 'Could not load subjects.');
                setOverview(data);
            })
            .catch((e) => setError(e.message));

    useEffect(() => { load(); }, [student.creditsUrl]); // eslint-disable-line react-hooks/exhaustive-deps

    // Uses the same Registrar year-level route as the Year button (audited, with a reason).
    const setYear = (yearLevel) => {
        setSettingYear(true);
        setError('');
        fetch(student.yearEdit.url, {
            method: 'POST',
            headers: jsonHeaders(csrfToken),
            body: JSON.stringify({ year_level: yearLevel, reason: 'Placed from credited subjects' }),
        })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message ?? 'Could not change the year level.');
                onYearSet(data.yearLevel, data.message);
                return load();
            })
            .catch((e) => setError(e.message))
            .finally(() => setSettingYear(false));
    };
    const placement = overview?.placement;
    const canSetYear = placement && student.yearEdit && placement.suggestedYear !== placement.currentYear
        && student.yearEdit.options.includes(placement.suggestedYear);

    const groups = useMemo(() => {
        const map = new Map();
        (overview?.subjects ?? []).forEach((s) => {
            const key = `${YEAR[s.yearLevel] ?? `Year ${s.yearLevel}`} · ${SEM[s.semester] ?? `Sem ${s.semester}`}`;
            if (!map.has(key)) map.set(key, []);
            map.get(key).push(s);
        });
        return [...map.entries()];
    }, [overview]);

    const count = Object.keys(picked).length;
    const toggle = (code) => setPicked((cur) => {
        const next = { ...cur };
        if (code in next) delete next[code]; else next[code] = '';
        return next;
    });

    const submit = () => {
        setSubmitting(true);
        setError('');
        fetch(student.creditsUrl, {
            method: 'POST',
            headers: jsonHeaders(csrfToken),
            body: JSON.stringify({
                subjects: Object.entries(picked).map(([code, grade]) => ({ code, grade: grade.trim() || null })),
                note: note.trim() || null,
            }),
        })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                    throw new Error(first ?? data.message ?? 'Could not send. Please try again.');
                }
                onSent(data.message);
            })
            .catch((e) => {
                setError(e.message);
                setSubmitting(false);
            });
    };

    return (
        <div className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget && !submitting) onClose(); }}>
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-xl max-h-[90vh] flex flex-col text-left">
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-start justify-between gap-3">
                    <div>
                        <p className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Credit subjects</p>
                        <p className="text-xs text-brandNavy/50 dark:text-slate-400">
                            {student.name} · {student.major} · <span className="font-semibold text-brandGold">{student.applicantType}</span>
                        </p>
                    </div>
                    <button type="button" onClick={onClose} disabled={submitting} aria-label="Close" className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white disabled:opacity-40">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>

                {placement && (
                    <div className="px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg/60 dark:bg-slate-900/40 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
                        <span className="text-brandNavy/60 dark:text-slate-400">Year: <strong className="text-brandNavy dark:text-white">{placement.currentYear ?? '—'}</strong></span>
                        <span className="text-brandNavy/60 dark:text-slate-400">Suggested: <strong className="text-brandGreen dark:text-[#7FD39A]">{placement.suggestedYear}</strong></span>
                        {canSetYear && (
                            <button type="button" onClick={() => setYear(placement.suggestedYear)} disabled={settingYear}
                                className="ui-btn-primary py-1 bg-brandGreen hover:bg-[#247039] text-white disabled:opacity-50">
                                {settingYear ? 'Setting…' : `Set ${placement.suggestedYear}`}
                            </button>
                        )}
                        {placement.backSubjects > 0 ? (
                            <span className="ml-auto font-semibold text-brandGold"><i className="fa-solid fa-triangle-exclamation mr-1" />{placement.backSubjects} back subject{placement.backSubjects > 1 ? 's' : ''} · Irregular</span>
                        ) : (
                            <span className="ml-auto font-semibold text-brandGreen dark:text-[#7FD39A]"><i className="fa-solid fa-circle-check mr-1" />Regular</span>
                        )}
                    </div>
                )}

                <div className="p-5 overflow-y-auto space-y-5 flex-1">
                    {overview?.lastRejected && (
                        <p className="text-xs text-red-600 dark:text-red-400 bg-red-500/10 border border-red-500/20 rounded px-3 py-2">
                            <i className="fa-solid fa-rotate-left mr-1" />Chair rejected {overview.lastRejected.codes.join(', ')} ({overview.lastRejected.at}): {overview.lastRejected.remarks}
                        </p>
                    )}
                    {!overview && !error && <p className="text-sm text-brandNavy/50 dark:text-slate-400">Loading…</p>}

                    {groups.map(([label, subjects]) => (
                        <section key={label}>
                            <h4 className="text-[11px] font-bold uppercase tracking-wider text-brandNavy/50 dark:text-slate-400 mb-1.5">{label}</h4>
                            <ul className="rounded-md border border-brandNavy/10 dark:border-slate-700 divide-y divide-brandNavy/8 dark:divide-slate-800">
                                {subjects.map((s) => {
                                    const open = s.state === 'available';
                                    const checked = s.code in picked;
                                    const [badge, badgeClass] = BADGES[s.state] ?? [];
                                    return (
                                        <li key={s.code} className={`flex items-center gap-3 px-3 py-2 ${checked ? 'bg-brandGreen/5' : ''}`}>
                                            <label className={`flex items-center gap-3 flex-1 min-w-0 ${open ? 'cursor-pointer' : 'opacity-70'}`}>
                                                <input type="checkbox" disabled={!open} checked={checked} onChange={() => toggle(s.code)} className="accent-brandGreen w-4 h-4 shrink-0" />
                                                <span className="min-w-0">
                                                    <span className="font-mono text-xs text-brandNavy/60 dark:text-slate-400 mr-2">{s.code}</span>
                                                    <span className="text-sm text-brandNavy dark:text-slate-200">{s.title}</span>
                                                </span>
                                            </label>
                                            {checked ? (
                                                <input value={picked[s.code]} onChange={(e) => setPicked((cur) => ({ ...cur, [s.code]: e.target.value }))}
                                                    maxLength={10} placeholder="Grade" aria-label={`Grade for ${s.code}`}
                                                    className="w-20 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2 py-1 text-xs text-brandNavy dark:text-slate-200" />
                                            ) : badge ? (
                                                <span className={`ui-badge-outline ${badgeClass} shrink-0`}>{badge}{s.grade ? ` · ${s.grade}` : ''}</span>
                                            ) : null}
                                        </li>
                                    );
                                })}
                            </ul>
                        </section>
                    ))}
                </div>

                <div className="p-5 border-t border-brandNavy/10 dark:border-slate-800 space-y-3">
                    <input value={note} onChange={(e) => setNote(e.target.value)} maxLength={255} placeholder="Note (e.g. From TOR, LSPU)"
                        className="w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600" />
                    {error && <p className="text-sm text-red-600 bg-red-500/10 border border-red-500/20 rounded px-3 py-2">{error}</p>}
                    <div className="flex gap-2">
                        <button type="button" onClick={onClose} disabled={submitting}
                            className="ui-btn-primary flex-1 justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 disabled:opacity-40">
                            Cancel
                        </button>
                        <button type="button" onClick={submit} disabled={count === 0 || submitting}
                            className="ui-btn-primary flex-1 justify-center bg-brandGreen hover:bg-[#247039] text-white disabled:opacity-40 disabled:cursor-not-allowed">
                            {submitting ? 'Sending…' : `Send ${count || ''} to Chair`}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
