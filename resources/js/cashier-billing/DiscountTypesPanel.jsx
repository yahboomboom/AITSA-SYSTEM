function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

export default function DiscountTypesPanel({ discountTypes, errors, old, csrfToken, addUrl }) {
    const firstError = errors.name || errors.percent;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
            <div className="p-5 border-b border-brandNavy/8 dark:border-slate-800">
                <h2 className="text-sm font-bold text-brandNavy dark:text-white">
                    <i className="fa-solid fa-percent mr-2 text-brandGreen" />Discount Types{' '}
                    <span className="font-normal text-brandNavy/40 dark:text-slate-500">(applies to tuition only)</span>
                </h2>
            </div>
            <div className="p-5 space-y-4 text-xs">
                <form
                    action={addUrl}
                    method="POST"
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(e) => lockSubmit(e.currentTarget, 'Adding…')}
                >
                    <input type="hidden" name="_token" value={csrfToken} />
                    {firstError && (
                        <div className="w-full p-3 rounded-lg bg-red-600/10 border border-red-600/20 text-red-600 font-bold">
                            <i className="fa-solid fa-circle-xmark mr-2" />{firstError}
                        </div>
                    )}
                    <div className="flex-1 min-w-32">
                        <label className="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">Name</label>
                        <input
                            type="text" name="name" required maxLength={100} placeholder="e.g. Academic Scholar"
                            defaultValue={old.name ?? ''}
                            className="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen"
                        />
                    </div>
                    <div className="w-20">
                        <label className="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">%</label>
                        <input
                            type="number" name="percent" min="1" max="100" required
                            defaultValue={old.percent ?? ''}
                            className="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen"
                        />
                    </div>
                    <button type="submit" className="px-4 py-2.5 bg-brandGreen hover:bg-emerald-600 text-white font-black rounded text-[11px] uppercase tracking-wider transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Add
                    </button>
                </form>

                <div className="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                    {discountTypes.length === 0 && (
                        <p className="py-3 text-brandNavy/40 dark:text-slate-500">No discount types yet.</p>
                    )}
                    {discountTypes.map((type) => (
                        <div key={type.id} className="py-2.5 flex items-center justify-between gap-2">
                            <div>
                                <span className="font-bold text-brandNavy dark:text-slate-200">{type.name}</span>
                                <span className="text-brandGreen font-black ml-2">{type.percent}%</span>
                                <span className="text-brandNavy/40 dark:text-slate-500 ml-2">{type.studentsCount} student(s)</span>
                            </div>
                            <form
                                action={type.deleteUrl}
                                method="POST"
                                onSubmit={(e) => {
                                    if (!window.confirm(`Remove ${type.name}? Students with this discount will lose it.`)) {
                                        e.preventDefault();
                                        return false;
                                    }
                                    return lockSubmit(e.currentTarget, 'Removing…');
                                }}
                            >
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" className="w-7 h-7 rounded bg-red-600/10 text-red-600 hover:bg-red-600 hover:text-white transition-colors">
                                    <i className="fa-solid fa-trash-can text-[10px]" />
                                </button>
                            </form>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
