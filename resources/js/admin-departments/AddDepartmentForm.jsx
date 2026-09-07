function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

export default function AddDepartmentForm({ csrfToken, actionUrl }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl p-6">
            <h3 className="text-sm font-bold text-brandNavy dark:text-white mb-4">Add Department</h3>
            <form
                action={actionUrl}
                method="POST"
                className="flex gap-3"
                onSubmit={(e) => lockSubmit(e.currentTarget, 'Adding…')}
            >
                <input type="hidden" name="_token" value={csrfToken} />
                <input
                    type="text" name="name" required maxLength={100} placeholder="e.g. Library"
                    className="flex-1 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40"
                />
                <button type="submit" className="px-5 py-2.5 bg-brandGreen hover:bg-emerald-600 text-white font-bold text-xs rounded uppercase tracking-wider disabled:opacity-50 disabled:cursor-not-allowed">
                    Add
                </button>
            </form>
        </div>
    );
}
