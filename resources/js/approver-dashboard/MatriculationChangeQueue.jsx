export default function MatriculationChangeQueue({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                <i className="fa-solid fa-arrows-rotate mr-2 text-brandGold" />Change of Matriculation Requests
            </h2>

            {rows.length === 0 ? (
                <p className="text-sm text-slate-400">No change requests awaiting approval.</p>
            ) : (
                rows.map((row) => (
                    <div key={row.id} className="border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-4">
                        <div className="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <p className="font-semibold text-brandNavy dark:text-slate-100">{row.studentName} ({row.studentId})</p>
                                <p className="text-xs text-slate-500">{row.major} — {row.yearLevel} — filed {row.filedAgo}</p>
                            </div>
                            <div className="flex gap-2">
                                <form method="POST" action={row.approveUrl}>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <button className="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">Approve</button>
                                </form>
                                <form method="POST" action={row.rejectUrl} className="flex gap-2">
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input
                                        name="remarks"
                                        required
                                        maxLength={500}
                                        placeholder="Reason for rejection"
                                        className="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm"
                                    />
                                    <button className="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:opacity-90">Reject</button>
                                </form>
                            </div>
                        </div>
                        <ul className="mt-3 space-y-1 text-sm">
                            {row.items.map((item, i) => (
                                <li key={i} className="border-t border-slate-100 dark:border-slate-800 pt-1">
                                    {item.action === 'add' ? (
                                        <>
                                            <span className="font-bold text-brandGreen uppercase text-xs mr-2">Add</span>
                                            <span className="font-mono">{item.subjectCode}</span>
                                            {' '}(Block {item.blockLabel}, {item.scheduleLabel}, {item.room})
                                        </>
                                    ) : item.action === 'drop' ? (
                                        <>
                                            <span className="font-bold text-red-600 uppercase text-xs mr-2">Drop</span>
                                            <span className="font-mono">{item.subjectCode}</span>
                                            {' '}(Block {item.blockLabel}, {item.scheduleLabel})
                                        </>
                                    ) : (
                                        <>
                                            <span className="font-bold text-brandGold uppercase text-xs mr-2">Swap</span>
                                            <span className="font-mono">{item.subjectCode}</span>
                                            {' '}Block {item.replacedBlockLabel} → Block {item.blockLabel}
                                            {' '}({item.scheduleLabel}, {item.room})
                                        </>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                ))
            )}
        </div>
    );
}
