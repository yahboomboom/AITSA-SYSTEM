function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

export default function StudentDiscountTable({ students, discountTypes, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800">
                <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white"><i className="fa-solid fa-user-tag mr-2 text-brandNavy dark:text-slate-300" />Student discounts</h2>
            </div>
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-400">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Program / Year</th>
                            <th className="border-brandNavy/8 dark:border-slate-800" colSpan={2}>Discount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {students.length === 0 && (
                            <tr>
                                <td colSpan={4} className="border-brandNavy/8 dark:border-slate-800 py-10 text-center text-brandNavy/40 dark:text-slate-500">No student accounts found.</td>
                            </tr>
                        )}
                        {students.map((student) => (
                            <tr key={student.id}>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    <span className="font-medium text-brandNavy dark:text-white block">{student.name}</span>
                                    <span className="font-mono text-brandNavy/60 dark:text-slate-400">{student.loginId ?? '—'}</span>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/60 dark:text-slate-400">{student.major ?? '—'} · {student.yearLevel ?? '—'}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800" colSpan={2}>
                                    <form
                                        action={student.assignUrl}
                                        method="POST"
                                        className="flex items-center justify-between gap-2"
                                        onSubmit={(e) => lockSubmit(e.currentTarget, 'Saving…')}
                                    >
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <select
                                            name="discount_type_id"
                                            defaultValue={student.discountTypeId ?? ''}
                                            className="bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2 rounded focus:outline-none focus:border-brandGreen"
                                        >
                                            <option value="">— None —</option>
                                            {discountTypes.map((type) => (
                                                <option key={type.id} value={type.id}>{type.name} ({type.percent}%)</option>
                                            ))}
                                        </select>
                                        <button type="submit" className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
