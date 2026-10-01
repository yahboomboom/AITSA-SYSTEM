import { useState } from 'react';
import WithdrawModal from './WithdrawModal';

export default function NoShowsCard({ rows, thresholdDays, withdrawUrl, thresholdUrl, csrfToken }) {
    const [selected, setSelected] = useState([]);
    const [modalStudents, setModalStudents] = useState(null);

    const toggle = (id) => setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
    const allSelected = rows.length > 0 && selected.length === rows.length;

    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
            <div className="flex items-start justify-between flex-wrap gap-3 mb-4">
                <div>
                    <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100">
                        <i className="fa-solid fa-user-clock mr-2 text-brandGold" />Possible No-Shows
                    </h2>
                    <p className="text-xs text-slate-500 mt-1">
                        Reserved students with no enrollment this term, reserved more than {thresholdDays} {thresholdDays === 1 ? 'day' : 'days'} ago.
                    </p>
                </div>
                <form method="POST" action={thresholdUrl} className="flex items-center gap-2">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <label className="text-xs text-slate-500" htmlFor="no-show-days">Flag after</label>
                    <input id="no-show-days" name="days" type="number" min={1} max={365} defaultValue={thresholdDays}
                        className="w-16 bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-sm text-brandNavy dark:text-slate-200 px-2 py-1 rounded" />
                    <span className="text-xs text-slate-500">days</span>
                    <button className="text-xs font-semibold text-brandGreen hover:underline">Save</button>
                </form>
            </div>

            {rows.length === 0 ? (
                <p className="text-sm text-slate-400">No reserved students are overdue. Nothing to review.</p>
            ) : (
                <>
                    <div className="flex items-center justify-between flex-wrap gap-2 mb-3">
                        <label className="flex items-center gap-2 text-xs text-slate-500">
                            <input type="checkbox" checked={allSelected} onChange={() => setSelected(allSelected ? [] : rows.map((r) => r.id))} />
                            Select all
                        </label>
                        <button
                            type="button"
                            disabled={selected.length === 0}
                            onClick={() => setModalStudents(rows.filter((r) => selected.includes(r.id)))}
                            className="px-4 py-2 rounded-lg bg-red-500 text-white text-sm font-semibold hover:bg-red-600 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Mark selected as withdrawn ({selected.length})
                        </button>
                    </div>
                    {rows.map((row) => (
                        <div key={row.id} className="flex items-center justify-between flex-wrap gap-3 border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-3">
                            <label className="flex items-center gap-3">
                                <input type="checkbox" checked={selected.includes(row.id)} onChange={() => toggle(row.id)} />
                                <span>
                                    <span className="block font-semibold text-brandNavy dark:text-slate-100">{row.name}</span>
                                    <span className="block text-xs text-slate-500">
                                        {row.loginId} · {row.program} · reserved {row.reservedOnFormatted} ({row.daysSince} days ago)
                                    </span>
                                </span>
                            </label>
                            <button type="button" onClick={() => setModalStudents([row])}
                                className="px-4 py-2 rounded-lg border border-red-500 text-red-600 text-sm font-semibold hover:bg-red-500 hover:text-white transition-colors">
                                Mark as No-Show
                            </button>
                        </div>
                    ))}
                </>
            )}

            {modalStudents && (
                <WithdrawModal students={modalStudents} actionUrl={withdrawUrl} csrfToken={csrfToken} onClose={() => setModalStudents(null)} />
            )}
        </div>
    );
}
