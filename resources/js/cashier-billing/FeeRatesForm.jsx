function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

const FIELDS = [
    { name: 'tuition_per_unit', ctxKey: 'tuitionPerUnit', label: 'Tuition per unit (₱)', hint: null },
    { name: 'misc_fee', ctxKey: 'miscFee', label: 'Miscellaneous fee per term (₱)', hint: null },
    {
        name: 'reservation_fee', ctxKey: 'reservationFee', label: 'Slot reservation fee (₱)',
        hint: 'Charged once, when a new applicant reserves their slot. Kept separate from tuition — never added on top of it.',
    },
    {
        name: 'tesda_tuition_fee', ctxKey: 'tesdaTuitionFee', label: 'TESDA Short-Term Program tuition (₱, flat)',
        hint: 'Flat tuition for TESDA NC students (Bookkeeping, Events Management, Food & Beverages), used instead of the regular tuition above.',
    },
    {
        name: 'down_payment_percent', ctxKey: 'downPaymentPercent', label: 'Down payment required to enroll (%)',
        hint: 'A student can self-enroll in sections once they\'ve paid this percentage of their total assessment — the remaining balance is settled later. Does not affect Cashier "Approve & Sign Off" (Officially Cleared), which still requires full payment.',
    },
];

export default function FeeRatesForm({ feeRates, errors, old, csrfToken, actionUrl }) {
    const firstError = FIELDS.map((f) => errors[f.name]).find(Boolean);

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
            <div className="p-5 border-b border-brandNavy/8 dark:border-slate-800">
                <h2 className="text-sm font-bold text-brandNavy dark:text-white"><i className="fa-solid fa-coins mr-2 text-brandGold" />Fee Rates</h2>
            </div>
            <form
                action={actionUrl}
                method="POST"
                className="p-5 space-y-4 text-xs"
                onSubmit={(e) => lockSubmit(e.currentTarget, 'Saving…')}
            >
                <input type="hidden" name="_token" value={csrfToken} />
                {firstError && (
                    <div className="p-3 rounded-lg bg-red-600/10 border border-red-600/20 text-red-600 font-bold">
                        <i className="fa-solid fa-circle-xmark mr-2" />{firstError}
                    </div>
                )}
                {FIELDS.map((f) => (
                    <div key={f.name}>
                        <label className="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">{f.label}</label>
                        <input
                            type="number" name={f.name} min="0" required
                            defaultValue={old[f.name] ?? feeRates[f.ctxKey]}
                            className="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen"
                        />
                        {f.hint && <p className="text-[10px] text-brandNavy/40 dark:text-slate-500 mt-1">{f.hint}</p>}
                    </div>
                ))}
                <button type="submit" className="px-5 py-2.5 bg-brandNavy hover:bg-brandGreen text-white font-black rounded text-[11px] uppercase tracking-wider transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Save Rates
                </button>
            </form>
        </div>
    );
}
