import { useState } from 'react';

function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

export default function DiscountTypesPanel({ discountTypes, errors, old, csrfToken, addUrl, onToggled }) {
    const firstError = errors.name || errors.percent;
    const [pendingId, setPendingId] = useState(null);
    const [notice, setNotice] = useState(null); // { ok: boolean, text: string }

    // Toggles in the background (no page reload) so the page stays where the cashier is scrolled.
    async function handleToggle(type) {
        if (pendingId) return;
        if (type.isActive && type.studentsCount > 0 &&
            !window.confirm(`Deactivate ${type.name}? ${type.studentsCount} student(s) will stop receiving this discount until it is re-activated.`)) {
            return;
        }

        setPendingId(type.id);
        setNotice(null);
        try {
            const res = await fetch(type.toggleUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Request failed');
            const data = await res.json();
            onToggled(type.id, !!data.isActive);
            setNotice({ ok: true, text: data.message ?? 'Discount type updated.' });
        } catch {
            setNotice({ ok: false, text: 'Could not update the discount type. Please try again.' });
        } finally {
            setPendingId(null);
        }
    }

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

<<<<<<< Updated upstream
                <div className="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
=======
                {notice && (
                    <div className={`p-3 rounded border text-xs font-medium ${notice.ok ? 'bg-brandGreen/10 border-brandGreen/20 text-brandGreen' : 'bg-red-600/10 border-red-600/20 text-red-600'}`}>
                        {notice.text}
                    </div>
                )}

                <div className="divide-y divide-brandNavy/8 dark:divide-slate-800">
>>>>>>> Stashed changes
                    {discountTypes.length === 0 && (
                        <p className="py-3 text-brandNavy/40 dark:text-slate-500">No discount types yet.</p>
                    )}
                    {discountTypes.map((type) => (
                        <div key={type.id} className="py-2.5 flex items-center justify-between gap-2">
                            <div>
<<<<<<< Updated upstream
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
=======
                                <span className={`font-medium ${type.isActive ? 'text-brandNavy dark:text-slate-200' : 'text-brandNavy/40 dark:text-slate-500 line-through'}`}>{type.name}</span>
                                <span className="text-brandGreen font-semibold ml-2">{type.percent}%</span>
                                <span className="text-brandNavy/40 dark:text-slate-500 ml-2">{type.studentsCount} student(s)</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className={`text-xs font-medium ${type.isActive ? 'text-brandGreen' : 'text-brandNavy/40 dark:text-slate-500'}`}>
                                    {type.isActive ? 'Active' : 'Inactive'}
                                </span>
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={type.isActive}
                                    aria-label={`${type.isActive ? 'Deactivate' : 'Activate'} ${type.name}`}
                                    title={type.isActive ? 'Click to deactivate' : 'Click to activate'}
                                    disabled={pendingId === type.id}
                                    onClick={() => handleToggle(type)}
                                    className={`relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-brandGreen/40 disabled:opacity-50 ${type.isActive ? 'bg-brandGreen' : 'bg-slate-300 dark:bg-slate-700'}`}
                                >
                                    <span className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${type.isActive ? 'translate-x-6' : 'translate-x-1'}`} />
>>>>>>> Stashed changes
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}