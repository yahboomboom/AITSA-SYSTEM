const ACTION_TONE = { add: 'border-brandGreen text-brandGreen', drop: 'border-red-500 text-red-600', swap: 'border-brandGold text-brandGold' };

export default function MatriculationChangeQueue({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6 mt-8">
            <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-slate-100 mb-4">
                <i className="fa-solid fa-arrows-rotate mr-2 text-brandGold" />Change of matriculation requests
            </h2>

            {rows.length === 0 ? (
                <p className="text-sm text-brandNavy/50 dark:text-slate-400">No change requests awaiting approval.</p>
            ) : (
                rows.map((row) => (
                    <div key={row.id} className="border border-brandNavy/10 dark:border-slate-700 rounded p-4 mb-4">
                        <div className="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <p className="font-medium text-brandNavy dark:text-slate-100">{row.studentName} ({row.studentId})</p>
                                <p className="text-xs text-brandNavy/50 dark:text-slate-500">{row.major} — {row.yearLevel} — filed {row.filedAgo}</p>
                            </div>
                            <div className="flex gap-2">
                                <form method="POST" action={row.approveUrl}>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <button className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors">Approve</button>
                                </form>
                                <form method="POST" action={row.rejectUrl} className="flex gap-2">
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <input
                                        name="remarks"
                                        required
                                        maxLength={500}
                                        placeholder="Reason for rejection"
                                        className="px-3 py-1.5 rounded border border-brandNavy/10 dark:border-slate-600 dark:bg-slate-800 text-sm outline-none focus:border-brandNavy dark:focus:border-slate-500"
                                    />
                                    <button className="ui-btn-primary bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors">Reject</button>
                                </form>
                            </div>
                        </div>
                        <div className="mt-3 divide-y divide-brandNavy/8 dark:divide-slate-800 text-sm">
                            {row.items.map((item, i) => (
                                <div key={i} className="py-2 flex items-center gap-2">
                                    <span className={`ui-badge-outline ${ACTION_TONE[item.action]}`}>{item.action}</span>
                                    <span className="text-brandNavy/80 dark:text-slate-300">
                                        {item.action === 'add' && (
                                            <>
                                                <span className="font-mono">{item.subjectCode}</span>
                                                {' '}(Block {item.blockLabel}, {item.scheduleLabel}, {item.room})
                                            </>
                                        )}
                                        {item.action === 'drop' && (
                                            <>
                                                <span className="font-mono">{item.subjectCode}</span>
                                                {' '}(Block {item.blockLabel}, {item.scheduleLabel})
                                            </>
                                        )}
                                        {item.action === 'swap' && (
                                            <>
                                                <span className="font-mono">{item.subjectCode}</span>
                                                {' '}Block {item.replacedBlockLabel} → Block {item.blockLabel}
                                                {' '}({item.scheduleLabel}, {item.room})
                                            </>
                                        )}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                ))
            )}
        </div>
    );
}
