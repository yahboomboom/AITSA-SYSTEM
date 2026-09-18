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
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6 space-y-3">
            <div>
                <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Start new semester</h2>
                <p className="text-sm text-brandNavy/50 dark:text-slate-400">
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
                    <label className="block text-xs font-medium text-brandNavy/60 dark:text-slate-400 mb-1">School year</label>
                    <input
                        type="text" name="school_year" required defaultValue={old.school_year}
                        className="w-32 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded px-2 py-1.5 text-sm"
                    />
                </div>
                <div>
                    <label className="block text-xs font-medium text-brandNavy/60 dark:text-slate-400 mb-1">Semester</label>
                    <select
                        name="semester" required defaultValue={old.semester ?? '1'}
                        className="bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded px-2 py-1.5 text-sm"
                    >
                        <option value="1">1st semester</option>
                        <option value="2">2nd semester</option>
                    </select>
                </div>
                <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Start new semester
                </button>
            </form>
            {errors.semester && (
                <p className="text-sm text-red-600 font-medium">{errors.semester}</p>
            )}
        </div>
    );
}
