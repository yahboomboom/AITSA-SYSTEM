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
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                    <thead>
                        <tr className="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                            <th className="p-4">Department</th>
                            <th className="p-4">Officers</th>
                            <th className="p-4">Status</th>
                            <th className="p-4">Add Officer</th>
                            <th className="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                        {departments.length === 0 && (
                            <tr>
                                <td colSpan={5} className="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">No departments yet. Add one above.</td>
                            </tr>
                        )}
                        {departments.map((department) => (
                            <tr key={department.id}>
                                <td className="p-4 font-bold text-brandNavy dark:text-white">{department.name}</td>
                                <td className="p-4 text-brandNavy/60 dark:text-slate-400">{department.officersCount}</td>
                                <td className="p-4">
                                    {department.isActive ? (
                                        <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGreen/10 text-brandGreen border border-brandGreen/20 rounded">Active</span>
                                    ) : (
                                        <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandNavy/10 text-brandNavy/50 dark:text-slate-500 border border-brandNavy/10 rounded">Inactive</span>
                                    )}
                                </td>
                                <td className="p-4">
                                    <form
                                        action={department.officersUrl}
                                        method="POST"
                                        className="flex gap-2"
                                        onSubmit={(e) => lockSubmit(e.currentTarget, 'Creating…')}
                                    >
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <input
                                            type="text" name="name" required maxLength={100} placeholder="Officer name"
                                            className="w-32 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none"
                                        />
                                        <input
                                            type="text" name="login_id" required maxLength={50} placeholder="Login ID"
                                            className="w-24 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none"
                                        />
                                        <button type="submit" className="px-3 py-1.5 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-bold rounded disabled:opacity-50 disabled:cursor-not-allowed">
                                            Create
                                        </button>
                                    </form>
                                </td>
                                <td className="p-4 text-right">
                                    <form
                                        action={department.toggleUrl}
                                        method="POST"
                                        onSubmit={(e) => confirmToggle(e, department.isActive, department.name)}
                                    >
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <button type="submit" className="px-3 py-1.5 bg-lightBg dark:bg-slate-800 hover:bg-brandNavy/10 text-brandNavy dark:text-slate-300 text-[11px] font-bold rounded border border-brandNavy/10 dark:border-slate-700 disabled:opacity-50 disabled:cursor-not-allowed">
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
