function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

export default function NewSemesterForm({ old, errors, csrfToken, actionUrl }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-xl p-5 space-y-3">
            <div>
                <h2 className="text-sm font-extrabold text-brandNavy dark:text-white">Start New Semester</h2>
                <p className="text-xs text-brandNavy/50 dark:text-slate-400">
                    Creates a fresh, Pending clearance for every active College student (Associate and Bachelor programs — TESDA is not affected) and advances the current term. This cannot be undone from this screen.
                </p>
            </div>
            <form
                action={actionUrl}
                method="POST"
                onSubmit={(e) => {
                    if (!window.confirm('Start a new semester? This creates a fresh clearance for every active College student.')) {
                        e.preventDefault();
                        return false;
                    }
                    return lockSubmit(e.currentTarget, 'Starting…');
                }}
                className="flex flex-wrap items-end gap-3"
            >
                <input type="hidden" name="_token" value={csrfToken} />
                <div>
                    <label className="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1">School Year</label>
                    <input
                        type="text" name="school_year" required defaultValue={old.school_year}
                        className="w-32 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded-lg px-2 py-1.5 text-xs"
                    />
                </div>
                <div>
                    <label className="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1">Semester</label>
                    <select
                        name="semester" required defaultValue={old.semester ?? '1'}
                        className="bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded-lg px-2 py-1.5 text-xs"
                    >
                        <option value="1">1st Semester</option>
                        <option value="2">2nd Semester</option>
                    </select>
                </div>
                <button type="submit" className="text-[11px] font-bold text-white bg-brandGreen hover:bg-emerald-700 px-4 py-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Start New Semester
                </button>
            </form>
            {errors.semester && (
                <p className="text-xs text-red-600 font-semibold">{errors.semester}</p>
            )}
        </div>
    );
}
