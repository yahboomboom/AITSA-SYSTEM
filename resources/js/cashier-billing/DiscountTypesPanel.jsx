import { useState } from 'react';

function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.dataset.originalLabel = btn.textContent;
        btn.textContent = busyLabel;
    }
    return true;
}

export default function DiscountTypesPanel({ discountTypes, errors, old, csrfToken, addUrl, onToggled }) {
    const firstError = errors.name || errors.percent;
    const [pendingToggleId, setPendingToggleId] = useState(null);

    const handleToggle = async (type) => {
        if (pendingToggleId) return; // avoid double-clicks firing overlapping requests
        setPendingToggleId(type.id);
        try {
            const res = await fetch(type.toggleUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            if (!res.ok) return;
            const data = await res.json();
            onToggled(data.id, data.isActive);
        } catch {
            // Network hiccup — the switch simply won't move; the cashier can retry.
        } finally {
            setPendingToggleId(null);
        }
    };

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800">
                <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                    <i className="fa-solid fa-percent mr-2 text-brandGreen" />Discount types{' '}
                    <span className="font-normal text-brandNavy/40 dark:text-slate-500">(applies to tuition only)</span>
                </h2>
            </div>
            <div className="p-5 space-y-4 text-sm">
                <form
                    action={addUrl}
                    method="POST"
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(e) => lockSubmit(e.currentTarget, 'Adding…')}
                >
                    <input type="hidden" name="_token" value={csrfToken} />
                    {firstError && (
                        <div className="w-full p-3 rounded bg-red-600/10 border border-red-600/20 text-red-600 font-medium">
                            <i className="fa-solid fa-circle-xmark mr-2" />{firstError}
                        </div>
                    )}
                    <div className="flex-1 min-w-32">
                        <label className="block font-medium text-brandNavy/60 dark:text-slate-400 text-xs mb-1.5">Name</label>
                        <input
                            type="text" name="name" required maxLength={100} placeholder="e.g. Academic Scholar"
                            defaultValue={old.name ?? ''}
                            className="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen"
                        />
                    </div>
                    <div className="w-20">
                        <label className="block font-medium text-brandNavy/60 dark:text-slate-400 text-xs mb-1.5">%</label>
                        <input
                            type="number" name="percent" min="1" max="100" required
                            defaultValue={old.percent ?? ''}
                            className="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen"
                        />
                    </div>
                    <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Add
                    </button>
                </form>

                <div className="divide-y divide-brandNavy/8 dark:divide-slate-800">
                    {discountTypes.length === 0 && (
                        <p className="py-3 text-brandNavy/40 dark:text-slate-500">No discount types yet.</p>
                    )}
                    {discountTypes.map((type) => (
                        <div key={type.id} className="py-2.5 flex items-center justify-between gap-2">
                            <div>
                                <span className="font-medium text-brandNavy dark:text-slate-200">{type.name}</span>
                                <span className="text-brandGreen font-semibold ml-2">{type.percent}%</span>
                                <span className="text-brandNavy/40 dark:text-slate-500 ml-2">{type.studentsCount} student(s)</span>
                                {!type.isActive && (
                                    <span className="text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 bg-brandNavy/5 dark:bg-slate-800 rounded-full px-2 py-0.5 ml-2">Inactive</span>
                                )}
                            </div>
                            <div className="flex items-center gap-3">
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={type.isActive}
                                    disabled={pendingToggleId === type.id}
                                    title={type.isActive ? 'Deactivate' : 'Activate'}
                                    aria-label={type.isActive ? `Deactivate ${type.name}` : `Activate ${type.name}`}
                                    onClick={() => handleToggle(type)}
                                    className={`w-10 h-[22px] rounded-full transition-colors relative flex-shrink-0 cursor-pointer disabled:cursor-wait disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brandGreen focus-visible:ring-offset-2 dark:focus-visible:ring-offset-panelDark ${type.isActive ? 'bg-brandGreen' : 'bg-brandNavy/20 dark:bg-slate-700'}`}
                                >
                                    <span
                                        className={`absolute top-0.5 left-0.5 w-[18px] h-[18px] rounded-full bg-white shadow transition-transform duration-150 ${type.isActive ? 'translate-x-[18px]' : 'translate-x-0'}`}
                                    />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}