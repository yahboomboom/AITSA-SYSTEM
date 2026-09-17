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
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="ui-table">
                        <thead>
                            <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-400">
                                <th className="border-brandNavy/8 dark:border-slate-800">Curriculum</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Level</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Total slots</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Sections</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Per section</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Taken / left</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {curricula.map((c) => (
                                <tr key={c.id}>
                                    <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandNavy dark:text-white">{c.programName}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/60 dark:text-slate-400">{c.level}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <input
                                            type="number" name="total_slots" min="1" defaultValue={c.totalSlots}
                                            form={`slot-form-${c.id}`}
                                            className="w-24 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded px-2 py-1 text-sm"
                                        />
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <input
                                            type="number" name="sections" min="1" defaultValue={c.sections}
                                            form={`slot-form-${c.id}`}
                                            className="w-16 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded px-2 py-1 text-sm"
                                        />
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/60 dark:text-slate-400">{c.perSection} seats each</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <span className={`font-medium ${c.slotsLeft <= 0 ? 'text-red-500' : 'text-brandGreen'}`}>
                                            {c.taken} taken · {c.slotsLeft} left
                                        </span>
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <button
                                            type="submit" form={`slot-form-${c.id}`}
                                            className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Save
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
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
