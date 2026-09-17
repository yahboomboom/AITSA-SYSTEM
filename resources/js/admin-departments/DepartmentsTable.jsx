function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

function confirmToggle(e, isActive, name) {
    const msg = isActive
        ? `Deactivate "${name}"? New clearance routing will no longer include this department until it is reactivated.`
        : `Activate "${name}" again?`;
    if (!window.confirm(msg)) {
        e.preventDefault();
        return false;
    }
    return lockSubmit(e.currentTarget, 'Please wait…');
}

export default function DepartmentsTable({ departments, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Department</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Officers</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Status</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Add officer</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {departments.length === 0 && (
                            <tr>
                                <td colSpan={5} className="border-brandNavy/8 dark:border-slate-800 py-8 text-center text-brandNavy/40 dark:text-slate-500">No departments yet. Add one above.</td>
                            </tr>
                        )}
                        {departments.map((department) => (
                            <tr key={department.id}>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandNavy dark:text-white">{department.name}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/60 dark:text-slate-400">{department.officersCount}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    {department.isActive ? (
                                        <span className="ui-badge-outline border-brandGreen text-brandGreen">Active</span>
                                    ) : (
                                        <span className="ui-badge-outline border-brandNavy/20 text-brandNavy/50 dark:border-slate-700 dark:text-slate-500">Inactive</span>
                                    )}
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    <form
                                        action={department.officersUrl}
                                        method="POST"
                                        className="flex gap-2"
                                        onSubmit={(e) => lockSubmit(e.currentTarget, 'Creating…')}
                                    >
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <input
                                            type="text" name="name" required maxLength={100} placeholder="Officer name"
                                            className="w-32 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-xs text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors"
                                        />
                                        <input
                                            type="text" name="login_id" required maxLength={50} placeholder="Login ID"
                                            className="w-24 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-xs text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40 transition-colors"
                                        />
                                        <button type="submit" className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                            Create
                                        </button>
                                    </form>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-right">
                                    <form
                                        action={department.toggleUrl}
                                        method="POST"
                                        onSubmit={(e) => confirmToggle(e, department.isActive, department.name)}
                                    >
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <button type="submit" className="ui-btn-primary bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                            {department.isActive ? 'Deactivate' : 'Activate'}
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
