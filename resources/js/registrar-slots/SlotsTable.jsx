function lockSubmitter(submitter, busyLabel) {
    if (submitter) {
        submitter.disabled = true;
        submitter.textContent = busyLabel;
    }
    return true;
}

export default function SlotsTable({ curricula, csrfToken }) {
    return (
        <>
            <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-xl overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-lightBg dark:bg-slate-900/40 text-[11px] uppercase tracking-wider text-brandNavy/50 dark:text-slate-400">
                        <tr>
                            <th className="text-left px-5 py-3 font-bold">Curriculum</th>
                            <th className="text-left px-5 py-3 font-bold">Level</th>
                            <th className="text-left px-5 py-3 font-bold">Total Slots</th>
                            <th className="text-left px-5 py-3 font-bold">Sections</th>
                            <th className="text-left px-5 py-3 font-bold">Per Section</th>
                            <th className="text-left px-5 py-3 font-bold">Taken / Left</th>
                            <th className="text-left px-5 py-3 font-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                        {curricula.map((c) => (
                            <tr key={c.id}>
                                <td className="px-5 py-3 font-bold text-brandNavy dark:text-white">{c.programName}</td>
                                <td className="px-5 py-3 text-xs text-brandNavy/60 dark:text-slate-400">{c.level}</td>
                                <td className="px-5 py-3">
                                    <input
                                        type="number" name="total_slots" min="1" defaultValue={c.totalSlots}
                                        form={`slot-form-${c.id}`}
                                        className="w-24 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded-lg px-2 py-1 text-xs"
                                    />
                                </td>
                                <td className="px-5 py-3">
                                    <input
                                        type="number" name="sections" min="1" defaultValue={c.sections}
                                        form={`slot-form-${c.id}`}
                                        className="w-16 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded-lg px-2 py-1 text-xs"
                                    />
                                </td>
                                <td className="px-5 py-3 text-xs text-brandNavy/60 dark:text-slate-400">{c.perSection} seats each</td>
                                <td className="px-5 py-3 text-xs">
                                    <span className={`font-bold ${c.slotsLeft <= 0 ? 'text-red-500' : 'text-brandGreen'}`}>
                                        {c.taken} taken · {c.slotsLeft} left
                                    </span>
                                </td>
                                <td className="px-5 py-3">
                                    <button
                                        type="submit" form={`slot-form-${c.id}`}
                                        className="text-[11px] font-bold text-white bg-brandNavy hover:bg-brandNavy/90 px-3 py-1.5 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Save
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* One real <form> per curriculum row, kept outside the table for valid HTML —
                a <form> can't legally wrap <td> elements as a direct child of <tr>, the
                browser's parser foster-parents it out of the table and silently detaches
                it from its own inputs. Each input/button above points here via form="...". */}
            {curricula.map((c) => (
                <form
                    key={c.id}
                    id={`slot-form-${c.id}`}
                    action={c.updateUrl}
                    method="POST"
                    hidden
                    onSubmit={(e) => lockSubmitter(e.nativeEvent.submitter, 'Saving…')}
                >
                    <input type="hidden" name="_token" value={csrfToken} />
                </form>
            ))}
        </>
    );
}
