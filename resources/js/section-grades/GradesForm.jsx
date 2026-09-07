function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

function statusClass(status) {
    if (status === 'Passed') return 'text-brandGreen';
    if (status === 'Failed') return 'text-red-500';
    return 'text-brandNavy/30 dark:text-slate-600';
}

export default function GradesForm({ students, csrfToken, actionUrl }) {
    return (
        <form
            action={actionUrl}
            method="POST"
            className="bg-white dark:bg-panelDark rounded-2xl shadow-sm overflow-hidden"
            onSubmit={(e) => lockSubmit(e.currentTarget, 'Saving…')}
        >
            <input type="hidden" name="_token" value={csrfToken} />

            {students.length === 0 ? (
                <p className="text-sm text-slate-500 dark:text-slate-400 p-6">No students are enrolled in this section yet.</p>
            ) : (
                <>
                    <table className="w-full text-sm">
                        <thead className="bg-lightBg dark:bg-slate-900/40 text-left text-[10px] uppercase tracking-wider text-brandNavy/50 dark:text-slate-500">
                            <tr>
                                <th className="px-6 py-3">Student</th>
                                <th className="px-6 py-3">Student No.</th>
                                <th className="px-6 py-3 w-32">Grade</th>
                                <th className="px-6 py-3 w-24">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                            {students.map((student) => (
                                <tr key={student.id}>
                                    <td className="px-6 py-3 font-semibold text-brandNavy dark:text-slate-200">{student.name}</td>
                                    <td className="px-6 py-3 font-mono text-xs text-brandNavy/60 dark:text-slate-400">{student.loginId}</td>
                                    <td className="px-6 py-3">
                                        <input
                                            type="number" name={`grades[${student.id}]`}
                                            defaultValue={student.grade ?? ''}
                                            min="0" max="100" step="1" placeholder="—"
                                            className="w-20 text-center font-mono text-sm rounded py-1.5 px-2 border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 text-brandNavy dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brandGreen/30 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                        />
                                    </td>
                                    <td className="px-6 py-3">
                                        <span className={`text-xs font-bold ${statusClass(student.status)}`}>
                                            {student.status ?? 'Not Taken'}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <div className="px-6 py-4 border-t border-brandNavy/10 dark:border-slate-800 flex justify-end">
                        <button type="submit" className="flex items-center gap-2 px-6 py-2.5 bg-brandGreen hover:bg-emerald-700 text-white text-sm font-black rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <i className="fa-solid fa-floppy-disk" />Save Grades
                        </button>
                    </div>
                </>
            )}
        </form>
    );
}
