// Registrar-only: the most units an irregular student (or any change of
// matriculation) may carry in one term. Regular block loads come from the
// curriculum and aren't limited by this.
export default function MaxUnitsForm({ maxUnits, actionUrl, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6 space-y-3">
            <div>
                <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Enrollment rules</h2>
                <p className="text-sm text-brandNavy/50 dark:text-slate-400">
                    Maximum units an irregular student can enroll in per term. Also applies to add/drop requests. Regular block schedules are not affected.
                </p>
            </div>
            <form action={actionUrl} method="POST" className="flex flex-wrap items-end gap-3">
                <input type="hidden" name="_token" value={csrfToken} />
                <div>
                    <label htmlFor="max-units" className="block text-xs font-medium text-brandNavy/60 dark:text-slate-400 mb-1">Max units per term</label>
                    <input
                        id="max-units" type="number" name="max_units" min={6} max={40} required defaultValue={maxUnits}
                        className="w-24 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded px-2 py-1.5 text-sm text-brandNavy dark:text-slate-200"
                    />
                </div>
                <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors">
                    Save
                </button>
            </form>
        </div>
    );
}
